<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

class HostiqoPhp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostiqo:php
                            {versions?* : PHP versions to install without prompting (e.g. 8.4 8.5)}
                            {--list : Only list PHP versions, install nothing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List PHP versions available on this server and install new ones (run with sudo)';

    /**
     * PHP versions Hostiqo can manage; what is offered depends on the server's repositories.
     *
     * @var array<int, string>
     */
    protected const SUPPORTED_VERSIONS = ['7.4', '8.0', '8.1', '8.2', '8.3', '8.4', '8.5', '8.6'];

    protected const CONFIG_FILE = '/etc/hostiqo/config.json';

    /**
     * Execute the console command.
     *
     * @return int Exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        if (!file_exists('/etc/debian_version')) {
            $this->error('hostiqo:php currently supports Debian/Ubuntu only.');
            return self::FAILURE;
        }

        if (function_exists('posix_geteuid') && posix_geteuid() !== 0) {
            $this->error('Run this command with sudo: sudo php artisan hostiqo:php');
            return self::FAILURE;
        }

        // Make sure a PHP repository is configured so every version the release can get is listed
        $this->line('Checking PHP repositories...');
        $repo = $this->runInstaller(['--php-repo']);
        if ($repo->failed()) {
            $this->warn('Could not set up a PHP repository, listing distro packages only.');
        }

        $versions = $this->detectVersions();
        $this->showTable($versions);

        if ($this->option('list')) {
            return self::SUCCESS;
        }

        // Installed versions stay listed even if their repository is gone
        $installable = array_filter($versions, fn ($v) => $v['available'] || $v['installed']);
        $installed = array_keys(array_filter($versions, fn ($v) => $v['installed']));

        $selected = $this->argument('versions')
            ? $this->argument('versions')
            : multiselect(
                label: 'Select PHP versions (installed versions are ticked)',
                options: collect($installable)->mapWithKeys(fn ($v, $version) => [
                    $version => "PHP {$version}" . ($v['installed'] ? ' (installed)' : '') . ($v['prerelease'] ? ' (pre-release)' : ''),
                ])->all(),
                default: $installed,
                scroll: count($installable),
                hint: 'Space to tick, Enter to confirm. Unticking an installed version does not remove it.',
            );

        $unknown = array_diff($selected, array_keys($installable));
        if ($unknown) {
            $this->error('Not available on this server: ' . implode(', ', $unknown));
            return self::FAILURE;
        }

        $kept = $this->argument('versions') ? [] : array_diff($installed, $selected);
        if ($kept) {
            $this->warn('PHP ' . implode(', ', $kept) . ' stays installed: removing PHP versions is not supported here.');
        }

        $toInstall = array_values(array_diff($selected, $installed));
        if (!$toInstall) {
            $this->info('Nothing new to install.');
            return self::SUCCESS;
        }

        $prerelease = array_filter($toInstall, fn ($version) => $versions[$version]['prerelease']);
        if ($prerelease && !$this->argument('versions')
            && !confirm('PHP ' . implode(', ', $prerelease) . ' is a pre-release. Install anyway?', default: false)) {
            $toInstall = array_values(array_diff($toInstall, $prerelease));
        }

        if (!$toInstall || (!$this->argument('versions') && !confirm('Install PHP ' . implode(', ', $toInstall) . '?'))) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $result = $this->runInstaller(['--php-install', implode(' ', $toInstall)], stream: true);

        // Record what is really installed now, whatever the installer managed
        $nowInstalled = array_keys(array_filter($this->detectVersions(), fn ($v) => $v['installed']));
        $this->saveConfig($nowInstalled);

        $failed = array_diff($toInstall, $nowInstalled);
        if ($result->failed() || $failed) {
            $this->error('Failed to install PHP ' . implode(', ', $failed ?: $toInstall) . '.');
            return self::FAILURE;
        }

        $this->info('✓ PHP ' . implode(', ', $toInstall) . ' installed. The new versions are now selectable for websites.');

        return self::SUCCESS;
    }

    /**
     * Check every supported PHP version against apt and dpkg.
     *
     * @return array<string, array{available: bool, installed: bool, prerelease: bool, candidate: string|null}>
     */
    protected function detectVersions(): array
    {
        $versions = [];

        foreach (self::SUPPORTED_VERSIONS as $version) {
            $package = "php{$version}-fpm";

            $policy = Process::run(['apt-cache', 'policy', $package])->output();
            $candidate = preg_match('/Candidate:\s*(\S+)/', $policy, $m) && $m[1] !== '(none)' ? $m[1] : null;

            $status = Process::run(['dpkg-query', '-W', '-f=${Status}', $package])->output();

            $versions[$version] = [
                'available' => $candidate !== null,
                'installed' => str_contains($status, 'install ok installed'),
                'prerelease' => $candidate !== null && (bool) preg_match('/~(alpha|beta|rc)/i', $candidate),
                'candidate' => $candidate,
            ];
        }

        return $versions;
    }

    /**
     * Print the PHP version table.
     *
     * @param array<string, array{available: bool, installed: bool, prerelease: bool, candidate: string|null}> $versions
     * @return void
     */
    protected function showTable(array $versions): void
    {
        $rows = [];

        foreach ($versions as $version => $v) {
            $rows[] = [
                $v['installed'] ? '<info>[x]</info>' : '[ ]',
                "PHP {$version}",
                match (true) {
                    $v['installed'] => '<info>Installed</info>',
                    $v['available'] => 'Available',
                    default => '<comment>Not available on this OS</comment>',
                },
                ($v['candidate'] ?? '-') . ($v['prerelease'] ? ' <comment>(pre-release)</comment>' : ''),
            ];
        }

        $this->table(['', 'Version', 'Status', 'Package version'], $rows);
    }

    /**
     * Save installed PHP versions to the Hostiqo config, keeping other keys.
     *
     * @param array<int, string> $installed Installed PHP versions
     * @return void
     */
    protected function saveConfig(array $installed): void
    {
        $config = file_exists(self::CONFIG_FILE)
            ? (json_decode((string) file_get_contents(self::CONFIG_FILE), true) ?: [])
            : [];

        usort($installed, 'version_compare');
        $config['php_versions'] = array_values($installed);

        if (!is_dir(dirname(self::CONFIG_FILE))) {
            mkdir(dirname(self::CONFIG_FILE), 0755, true);
        }

        file_put_contents(self::CONFIG_FILE, json_encode($config, JSON_UNESCAPED_SLASHES));
        chmod(self::CONFIG_FILE, 0644);
    }

    /**
     * Run a phase of the Hostiqo installer.
     *
     * @param array<int, string> $args Installer arguments
     * @param bool $stream Whether to print installer output as it runs
     * @return \Illuminate\Contracts\Process\ProcessResult
     */
    protected function runInstaller(array $args, bool $stream = false): \Illuminate\Contracts\Process\ProcessResult
    {
        $command = array_merge(['bash', base_path('scripts/install.sh')], $args);

        return Process::path(base_path())
            ->forever()
            ->run($command, $stream ? function (string $type, string $output) {
                $this->output->write($output);
            } : null);
    }
}
