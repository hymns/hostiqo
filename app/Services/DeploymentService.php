<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Webhook;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DeploymentService
{
    public function __construct(
        protected SshKeyService $sshKeyService
    ) {
    }

    /**
     * Execute deployment for a webhook.
     *
     * @param Webhook $webhook The webhook to deploy
     * @param array $payload Optional payload data from webhook
     * @return Deployment The deployment record
     */
    public function deploy(Webhook $webhook, array $payload = []): Deployment
    {
        $deployment = Deployment::create([
            'webhook_id' => $webhook->id,
            'status' => 'processing',
            'commit_hash' => $payload['commit_hash'] ?? null,
            'commit_message' => $payload['commit_message'] ?? null,
            'author' => $payload['author'] ?? null,
            'started_at' => now(),
        ]);

        try {
            $output = $this->executeDeployment($webhook);

            $deployment->update([
                'status' => 'completed',
                'output' => $output,
                'completed_at' => now(),
            ]);

            $webhook->update(['last_deployed_at' => now()]);
        } catch (\Exception $e) {
            Log::error('Deployment failed: ' . $e->getMessage(), [
                'webhook_id' => $webhook->id,
                'deployment_id' => $deployment->id,
            ]);

            $deployment->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $deployment;
    }

    /**
     * Prepare command to run as specific user if configured.
     *
     * @param array $command The command array
     * @param string|null $deployUser The user to run command as
     * @return array The prepared command array
     */
    protected function prepareCommandAsUser(array $command, ?string $deployUser = null): array
    {
        if (!$deployUser || $deployUser === get_current_user()) {
            return $command;
        }

        return array_merge(['sudo', '-u', $deployUser], $command);
    }

    /**
     * Resolve the home directory path for the deployment user.
     *
     * This ensures script execution uses the correct HOME and COMPOSER_HOME,
     * especially when running as www-data or a specific user.
     *
     * @param string $deployUser The user configured for deployment
     * @return string Filesystem path to that user's home directory
     */
    private function resolveHomeDir(string $deployUser): string
    {
        return match ($deployUser) {
            'root' => '/root',
            'www-data' => '/var/www',
            default => '/home/' . $deployUser,
        };
    }

    /**
     * Run a deploy script (pre or post) in an isolated environment.
     *
     * @param string $script The script content to execute
     * @param string $localPath The deployment directory
     * @param string|null $deployUser The user to run the script as
     * @param string $label Label for logging (e.g. "pre-deploy" or "post-deploy")
     * @return array Output lines from the script execution
     */
    private function runDeployScript(string $script, string $localPath, ?string $deployUser, string $label): array
    {
        $output = ["\nRunning {$label} script..."];

        // Normalize line endings and trim each line to remove trailing spaces
        $normalizedScript = str_replace(["\r\n", "\r"], "\n", $script);
        $cleanedScript = implode("\n", array_map('trim', explode("\n", $normalizedScript)));

        $homeDir = $this->resolveHomeDir($deployUser ?? get_current_user());

        // Wrap full script with env -i to isolate environment completely
        // Set PWD explicitly to ensure Laravel loads .env from correct directory
        $envPrefix = "env -i HOME={$homeDir} COMPOSER_HOME={$homeDir}/.composer PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin PWD={$localPath}";
        $wrappedScript = $envPrefix . ' bash -c ' . escapeshellarg("cd {$localPath} && " . $cleanedScript);

        $command = $this->prepareCommandAsUser(['bash', '-c', $wrappedScript], $deployUser);

        $result = Process::path($localPath)->timeout(300)->run($command);

        $output[] = $result->output();

        if ($result->failed()) {
            $output[] = "Warning: {$label} script failed: " . $result->errorOutput();
        }

        return $output;
    }

    /**
     * Execute the deployment process.
     *
     * @param Webhook $webhook The webhook to deploy
     * @return string The deployment output
     * @throws \Exception If deployment fails
     */
    protected function executeDeployment(Webhook $webhook): string
    {
        $localPath = $webhook->local_path;
        $branch = $webhook->branch;
        $deployUser = $webhook->deploy_user;
        $output = [];

        // Setup SSH key if available
        $sshKey = $webhook->sshKey;
        $keyPath = null;
        $gitSshCommand = '';

        if ($sshKey) {
            $keyPath = $this->sshKeyService->saveTempPrivateKey($sshKey);
            $gitSshCommand = "ssh -i {$keyPath} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null";
        }

        try {
            // Log deploy user if specified
            if ($deployUser) {
                $output[] = "Running deployment as user: {$deployUser}\n";
            }

            // Check if directory exists but is not a git repo — remove so we can clone fresh
            if (File::isDirectory($localPath) && !File::isDirectory($localPath . '/.git')) {
                $output[] = "Directory exists but is not a git repository. Removing: {$localPath}";
                $result = Process::run("sudo rm -rf {$localPath}");

                if ($result->failed()) {
                    throw new \Exception("Failed to remove directory: " . $result->errorOutput());
                }
            }

            if ($webhook->pre_deploy_script) {
                array_push($output, ...$this->runDeployScript($webhook->pre_deploy_script, $localPath, $deployUser, 'pre-deploy'));
            }

            if (!File::isDirectory($localPath)) {
                // Clone repository
                $output[] = "Cloning repository...";
                $command = $this->prepareCommandAsUser(
                    ['git', 'clone', '-b', $branch, $webhook->repository_url, $localPath],
                    $deployUser
                );

                $result = Process::env(['GIT_SSH_COMMAND' => $gitSshCommand])->run($command);
                $output[] = $result->output();

                if ($result->failed()) {
                    throw new \Exception("Git clone failed: " . $result->errorOutput());
                }
            } else {
                // Pull latest changes
                $output[] = "Pulling latest changes...";

                $command = $this->prepareCommandAsUser(['git', 'fetch', 'origin', $branch], $deployUser);
                $result = Process::path($localPath)->env(['GIT_SSH_COMMAND' => $gitSshCommand])->run($command);
                $output[] = $result->output();

                if ($result->failed()) {
                    throw new \Exception("Git fetch failed: " . $result->errorOutput());
                }

                // Reset to origin
                $command = $this->prepareCommandAsUser(
                    ['git', 'reset', '--hard', "origin/{$branch}"],
                    $deployUser
                );
                $result = Process::path($localPath)->run($command);
                $output[] = $result->output();

                if ($result->failed()) {
                    throw new \Exception("Git reset failed: " . $result->errorOutput());
                }
            }

            if ($webhook->post_deploy_script) {
                array_push($output, ...$this->runDeployScript($webhook->post_deploy_script, $localPath, $deployUser, 'post-deploy'));
            }

            $output[] = "\n✓ Deployment completed successfully!";
        } finally {
            // Clean up temporary key
            if ($keyPath) {
                $this->sshKeyService->deleteTempPrivateKey($keyPath);
            }
        }

        return implode("\n", $output);
    }

    /**
     * Parse GitHub webhook payload.
     *
     * @param array $payload The GitHub webhook payload
     * @return array{commit_hash: string|null, commit_message: string|null, author: string|null}
     */
    public function parseGithubPayload(array $payload): array
    {
        return [
            'commit_hash' => $payload['after'] ?? null,
            'commit_message' => $payload['head_commit']['message'] ?? null,
            'author' => $payload['head_commit']['author']['name'] ?? null,
        ];
    }

    /**
     * Parse GitLab webhook payload.
     *
     * @param array $payload The GitLab webhook payload
     * @return array{commit_hash: string|null, commit_message: string|null, author: string|null}
     */
    public function parseGitlabPayload(array $payload): array
    {
        return [
            'commit_hash' => $payload['checkout_sha'] ?? $payload['after'] ?? null,
            'commit_message' => $payload['commits'][0]['message'] ?? null,
            'author' => $payload['commits'][0]['author']['name'] ?? $payload['user_name'] ?? null,
        ];
    }
}
