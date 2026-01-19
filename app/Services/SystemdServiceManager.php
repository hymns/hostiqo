<?php

namespace App\Services;

use App\Models\SystemdService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class SystemdServiceManager
{
    /**
     * Generate systemd service file content.
     */
    public function generateServiceFile(SystemdService $service): string
    {
        $environment = $service->environment 
            ? collect(explode("\n", $service->environment))
                ->filter()
                ->map(fn($line) => "Environment=\"" . trim($line) . "\"")
                ->join("\n")
            : '';

        return <<<SYSTEMD
[Unit]
Description={$service->description}
After=network.target

[Service]
Type={$service->type}
User={$service->user}
WorkingDirectory={$service->working_directory}
ExecStart={$service->exec_start}
Restart={$service->restart}
RestartSec={$service->restart_sec}

{$environment}

StandardOutput={$service->standard_output}
StandardError={$service->standard_error}

[Install]
WantedBy=multi-user.target
SYSTEMD;
    }

    /**
     * Write service file to systemd directory.
     */
    public function writeServiceFile(SystemdService $service): array
    {
        try {
            $content = $this->generateServiceFile($service);
            $tempFile = tempnam(sys_get_temp_dir(), 'systemd_');
            File::put($tempFile, $content);

            $servicePath = "/etc/systemd/system/{$service->name}.service";
            
            $result = Process::run("sudo /bin/cp {$tempFile} {$servicePath}");
            if ($result->failed()) {
                throw new \Exception('Failed to copy service file: ' . $result->errorOutput());
            }

            $result = Process::run("sudo /bin/chmod 644 {$servicePath}");
            if ($result->failed()) {
                throw new \Exception('Failed to set permissions: ' . $result->errorOutput());
            }

            unlink($tempFile);

            return [
                'success' => true,
                'filepath' => $servicePath,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete service file from systemd directory.
     */
    public function deleteServiceFile(SystemdService $service): array
    {
        try {
            $servicePath = "/etc/systemd/system/{$service->name}.service";
            
            $result = Process::run("sudo /bin/rm -f {$servicePath}");
            if ($result->failed()) {
                throw new \Exception('Failed to delete service file: ' . $result->errorOutput());
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reload systemd daemon.
     */
    public function daemonReload(): array
    {
        try {
            $result = Process::run('sudo /bin/systemctl daemon-reload');
            
            if ($result->failed()) {
                throw new \Exception('Failed to reload daemon: ' . $result->errorOutput());
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enable service.
     */
    public function enableService(SystemdService $service): array
    {
        try {
            $result = Process::run("sudo /bin/systemctl enable {$service->name}");
            
            if ($result->failed()) {
                throw new \Exception('Failed to enable service: ' . $result->errorOutput());
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Disable service.
     */
    public function disableService(SystemdService $service): array
    {
        try {
            $result = Process::run("sudo /bin/systemctl disable {$service->name}");
            
            if ($result->failed()) {
                throw new \Exception('Failed to disable service: ' . $result->errorOutput());
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Start service.
     */
    public function startService(SystemdService $service): array
    {
        try {
            $result = Process::run("sudo /bin/systemctl start {$service->name}");
            
            if ($result->failed()) {
                throw new \Exception('Failed to start service: ' . $result->errorOutput());
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Stop service.
     */
    public function stopService(SystemdService $service): array
    {
        try {
            $result = Process::run("sudo /bin/systemctl stop {$service->name}");
            
            if ($result->failed()) {
                throw new \Exception('Failed to stop service: ' . $result->errorOutput());
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restart service.
     */
    public function restartService(SystemdService $service): array
    {
        try {
            $result = Process::run("sudo /bin/systemctl restart {$service->name}");
            
            if ($result->failed()) {
                throw new \Exception('Failed to restart service: ' . $result->errorOutput());
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get service status.
     */
    public function getServiceStatus(SystemdService $service): array
    {
        try {
            $result = Process::run("sudo /bin/systemctl status {$service->name}");
            $output = $result->output();

            $isActive = Process::run("sudo /bin/systemctl is-active {$service->name}")->output();
            $status = trim($isActive);

            return [
                'success' => true,
                'status' => $status,
                'output' => $output,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Deploy service (write file, reload daemon, enable, start).
     */
    public function deployService(SystemdService $service): array
    {
        // Write service file
        $writeResult = $this->writeServiceFile($service);
        if (!$writeResult['success']) {
            return $writeResult;
        }

        // Reload daemon
        $reloadResult = $this->daemonReload();
        if (!$reloadResult['success']) {
            return $reloadResult;
        }

        // Enable service
        $enableResult = $this->enableService($service);
        if (!$enableResult['success']) {
            return $enableResult;
        }

        // Start service
        $startResult = $this->startService($service);
        if (!$startResult['success']) {
            return $startResult;
        }

        return [
            'success' => true,
            'message' => 'Service deployed successfully',
        ];
    }

    /**
     * Undeploy service (stop, disable, delete file, reload daemon).
     */
    public function undeployService(SystemdService $service): array
    {
        // Stop service
        $this->stopService($service);

        // Disable service
        $this->disableService($service);

        // Delete service file
        $deleteResult = $this->deleteServiceFile($service);
        if (!$deleteResult['success']) {
            return $deleteResult;
        }

        // Reload daemon
        $reloadResult = $this->daemonReload();
        if (!$reloadResult['success']) {
            return $reloadResult;
        }

        return [
            'success' => true,
            'message' => 'Service undeployed successfully',
        ];
    }
}
