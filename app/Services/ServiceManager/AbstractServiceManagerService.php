<?php

namespace App\Services\ServiceManager;

use App\Contracts\ServiceManagerInterface;
use Illuminate\Support\Facades\Process;

abstract class AbstractServiceManagerService implements ServiceManagerInterface
{
    protected array $supportedServices = [];

    abstract public function getOsFamily(): string;
    abstract protected function buildServiceList(): array;

    /**
     * Get supported services.
     *
     * @return array<string, array> List of supported services
     */
    public function getSupportedServices(): array
    {
        return $this->supportedServices;
    }

    /**
     * Get available services (installed on system).
     *
     * @return array<string, array> List of available services with status
     */
    public function getAvailableServices(): array
    {
        $services = [];
        $pool = [];

        // Prepare concurrent status checks for all services
        foreach ($this->supportedServices as $key => $info) {
            $pool[$key] = fn () => Process::run("systemctl status {$info['service']} 2>&1");
        }

        // Run all systemctl status commands concurrently
        $results = Process::concurrently(function ($pool) {
            foreach ($pool as $key => $command) {
                $pool[$key] = $command();
            }
            return $pool;
        });

        // Process results
        foreach ($results as $key => $result) {
            $output = $result->output();
            
            // Service doesn't exist if output contains these messages
            $notFound = str_contains($output, 'could not be found') || 
                       str_contains($output, 'not-found') ||
                       str_contains($output, 'Unit') && str_contains($output, 'not found');
            
            if (!$notFound) {
                // Parse status from the result we already have
                $status = $this->parseServiceStatus($output);
                $services[$key] = array_merge($this->supportedServices[$key], $status);
            }
        }

        return $services;
    }

    /**
     * Get status of a specific service.
     *
     * @param string $serviceKey The service key
     * @return array{running: bool, enabled: bool, status: string, error?: string}
     */
    public function getServiceStatus(string $serviceKey): array
    {
        if (!isset($this->supportedServices[$serviceKey])) {
            return [
                'running' => false,
                'enabled' => false,
                'status' => 'unknown',
                'error' => 'Service not supported'
            ];
        }

        $serviceName = $this->supportedServices[$serviceKey]['service'];
        
        // Get detailed status from systemctl status
        $result = Process::run("systemctl status {$serviceName} 2>&1");
        $output = $result->output();
        
        return $this->parseServiceStatus($output);
    }

    /**
     * Parse systemctl status output.
     *
     * @param string $output The systemctl status output
     * @return array{running: bool, enabled: bool, status: string}
     */
    protected function parseServiceStatus(string $output): array
    {
        // Parse status from output
        // Some services like UFW use 'exited' instead of 'running'
        $isRunning = str_contains($output, 'Active: active (running)') || 
                     str_contains($output, 'Active: active (exited)');
        
        // Check enabled status - various formats in systemctl output
        // e.g., "Loaded: loaded (/lib/systemd/system/ssh.service; enabled; vendor preset: enabled)"
        $isEnabled = (bool) preg_match('/;\s*enabled[;)]/', $output);
        
        // Determine status string
        if (str_contains($output, 'Active: active (running)') || str_contains($output, 'Active: active (exited)')) {
            $status = 'running';
        } elseif (str_contains($output, 'Active: inactive (dead)')) {
            $status = 'stopped';
        } elseif (str_contains($output, 'Active: failed')) {
            $status = 'failed';
        } else {
            $status = 'unknown';
        }

        $statusData = [
            'running' => $isRunning,
            'enabled' => $isEnabled,
            'status' => $status,
        ];

        // Extract PID and get resource usage if service is running
        if ($isRunning && preg_match('/Main PID:\s+(\d+)/', $output, $matches)) {
            $pid = (int) $matches[1];
            $statusData['pid'] = $pid;
            
            // Get CPU and memory usage
            $resourceUsage = $this->getProcessResourceUsage($pid);
            if ($resourceUsage) {
                $statusData['cpu'] = $resourceUsage['cpu'];
                $statusData['memory'] = $resourceUsage['memory'];
            }
        }

        // Extract uptime
        if (preg_match('/Active: active \([^)]+\) since ([^;]+);/', $output, $matches)) {
            $statusData['uptime'] = trim($matches[1]);
        }

        return $statusData;
    }

    /**
     * Get CPU and memory usage for a process.
     *
     * @param int $pid The process ID
     * @return array{cpu: string, memory: string}|null
     */
    protected function getProcessResourceUsage(int $pid): ?array
    {
        try {
            // Use ps to get CPU and memory usage
            $result = Process::run("ps -p {$pid} -o %cpu,%mem --no-headers 2>/dev/null");
            
            if (!$result->successful()) {
                return null;
            }

            $output = trim($result->output());
            if (empty($output)) {
                return null;
            }

            $parts = preg_split('/\s+/', $output);
            if (count($parts) >= 2) {
                return [
                    'cpu' => number_format((float) $parts[0], 1),
                    'memory' => number_format((float) $parts[1], 1),
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Start a service.
     *
     * @param string $serviceKey The service key
     * @return array{success: bool, message?: string, error?: string}
     */
    public function startService(string $serviceKey): array
    {
        if (!isset($this->supportedServices[$serviceKey])) {
            return ['success' => false, 'error' => 'Service not supported'];
        }

        $serviceName = $this->supportedServices[$serviceKey]['service'];
        $result = Process::run("sudo /bin/systemctl start {$serviceName}");

        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? "{$serviceName} started" : "Failed to start {$serviceName}",
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Stop a service.
     *
     * @param string $serviceKey The service key
     * @return array{success: bool, message?: string, error?: string}
     */
    public function stopService(string $serviceKey): array
    {
        if (!isset($this->supportedServices[$serviceKey])) {
            return ['success' => false, 'error' => 'Service not supported'];
        }

        $serviceName = $this->supportedServices[$serviceKey]['service'];
        $result = Process::run("sudo /bin/systemctl stop {$serviceName}");

        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? "{$serviceName} stopped" : "Failed to stop {$serviceName}",
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Restart a service.
     *
     * @param string $serviceKey The service key
     * @return array{success: bool, message?: string, error?: string}
     */
    public function restartService(string $serviceKey): array
    {
        if (!isset($this->supportedServices[$serviceKey])) {
            return ['success' => false, 'error' => 'Service not supported'];
        }

        $serviceName = $this->supportedServices[$serviceKey]['service'];
        $result = Process::run("sudo /bin/systemctl restart {$serviceName}");

        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? "{$serviceName} restarted" : "Failed to restart {$serviceName}",
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Reload a service.
     *
     * @param string $serviceKey The service key
     * @return array{success: bool, message?: string, error?: string}
     */
    public function reloadService(string $serviceKey): array
    {
        if (!isset($this->supportedServices[$serviceKey])) {
            return ['success' => false, 'error' => 'Service not supported'];
        }

        $info = $this->supportedServices[$serviceKey];
        
        if (!($info['supports_reload'] ?? false)) {
            return $this->restartService($serviceKey);
        }

        $serviceName = $info['service'];
        $result = Process::run("sudo /bin/systemctl reload {$serviceName}");

        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? "{$serviceName} reloaded" : "Failed to reload {$serviceName}",
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Get service logs using journalctl.
     *
     * @param string $serviceKey The service key
     * @param int $lines Number of log lines to retrieve
     * @return string The log content
     */
    public function getServiceLogs(string $serviceKey, int $lines = 100): string
    {
        if (!isset($this->supportedServices[$serviceKey])) {
            return "Service not supported: {$serviceKey}";
        }

        $serviceName = $this->supportedServices[$serviceKey]['service'];
        $result = Process::run("sudo /bin/journalctl -u {$serviceName} -n {$lines} --no-pager");

        if ($result->successful()) {
            return $result->output();
        }

        return "Failed to get logs: " . $result->errorOutput();
    }
}
