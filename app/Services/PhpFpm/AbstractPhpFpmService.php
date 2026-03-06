<?php

namespace App\Services\PhpFpm;

use App\Contracts\PhpFpmInterface;
use App\Models\Website;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

abstract class AbstractPhpFpmService implements PhpFpmInterface
{
    protected string $poolDirectory;
    protected string $webServerUser;
    protected string $webServerGroup;

    abstract public function getOsFamily(): string;
    abstract public function getPoolDirectoryPath(string $phpVersion): string;
    abstract public function getSocketPath(string $phpVersion, string $poolName): string;
    abstract public function getLogPath(string $phpVersion): string;
    abstract public function getWebServerUser(): string;
    abstract public function getWebServerGroup(): string;

    /**
     * Get default PHP hardening settings.
     *
     * @return array<string, string> Default PHP settings
     */
    public function getDefaultSettings(): array
    {
        return [
            'memory_limit' => '256M',
            'max_execution_time' => '300',
            'upload_max_filesize' => '100M',
            'post_max_size' => '100M',
            'max_input_time' => '60',
            'max_input_vars' => '1000',
            'disable_functions' => implode(',', [
                'exec', 'passthru', 'shell_exec', 'system',
                'proc_open', 'popen', 'curl_exec', 'curl_multi_exec',
                'parse_ini_file', 'show_source',
            ]),
            'expose_php' => 'Off',
            'display_errors' => 'Off',
            'log_errors' => 'On',
            'allow_url_fopen' => 'On',
            'allow_url_include' => 'Off',
            'file_uploads' => 'On',
            'enable_dl' => 'Off',
        ];
    }

    /**
     * Generate pool name from website.
     *
     * @param Website $website The website model
     * @return string The generated pool name
     */
    protected function generatePoolName(Website $website): string
    {
        return Str::slug($website->domain, '_');
    }

    /**
     * Build PHP admin values for pool config.
     *
     * @param array<string, string> $settings PHP settings
     * @return string The formatted PHP admin values
     */
    protected function buildPhpAdminValues(array $settings): string
    {
        $lines = [];
        foreach ($settings as $key => $value) {
            $lines[] = "php_admin_value[{$key}] = {$value}";
        }
        return implode("\n", $lines);
    }

    /**
     * Get total system RAM in MB.
     *
     * @return int Total RAM in MB
     */
    protected function getTotalRamMb(): int
    {
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                // macOS
                $result = Process::run(['sysctl', '-n', 'hw.memsize']);
                if ($result->successful()) {
                    $bytes = (int) trim($result->output());
                    return intval($bytes / 1024 / 1024);
                }
            } else {
                // Linux - same method as SystemMonitorService
                $result = Process::run(['grep', 'MemTotal', '/proc/meminfo']);
                if ($result->successful()) {
                    if (preg_match('/MemTotal:\s+(\d+)/', $result->output(), $matches)) {
                        return intval($matches[1] / 1024); // Convert KB to MB
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get total RAM, using default', ['error' => $e->getMessage()]);
        }
        
        return 2048; // Default 2GB
    }

    /**
     * Get dynamic PHP-FPM pool settings based on server RAM.
     *
     * @return array{max_children: int, start_servers: int, min_spare: int, max_spare: int}
     */
    protected function getDynamicPoolSettings(): array
    {
        $totalRamMb = $this->getTotalRamMb();
        
        // max_children = Available RAM / ~100MB per process (conservative for per-site pools)
        $maxChildren = intval($totalRamMb / 100);
        $maxChildren = max(5, min(50, $maxChildren)); // Min 5, max 50 per site
        
        $startServers = max(2, intval($maxChildren / 4));
        $minSpare = max(1, intval($maxChildren / 10));
        $maxSpare = max(3, intval($maxChildren / 4));
        
        return [
            'max_children' => $maxChildren,
            'start_servers' => $startServers,
            'min_spare' => $minSpare,
            'max_spare' => $maxSpare,
        ];
    }

    /**
     * Generate PHP-FPM pool configuration for a website.
     *
     * @param Website $website The website model
     * @return string The generated pool configuration
     * @throws \InvalidArgumentException If website is not a PHP project
     */
    public function generatePoolConfig(Website $website): string
    {
        if ($website->project_type !== 'php') {
            throw new \InvalidArgumentException('Website is not a PHP project');
        }

        if (empty($website->php_version)) {
            throw new \InvalidArgumentException('PHP version is required for PHP projects');
        }

        $poolName = $website->php_pool_name ?? $this->generatePoolName($website);
        $settings = array_merge(
            $this->getDefaultSettings(),
            $website->php_settings ?? []
        );

        // Add open_basedir restriction
        if (!isset($settings['open_basedir'])) {
            $settings['open_basedir'] = implode(':', [
                $website->root_path,
                '/tmp',
                '/usr/share/php',
                '/usr/share/pear',
            ]);
        }

        $phpAdmin = $this->buildPhpAdminValues($settings);
        $socketPath = $this->getSocketPath($website->php_version, $poolName);
        $logDir = $this->getLogPath($website->php_version);
        $user = $this->getWebServerUser();
        $group = $this->getWebServerGroup();
        
        // Get dynamic pool settings based on server RAM
        $pool = $this->getDynamicPoolSettings();

        return <<<POOL
[{$poolName}]
user = {$user}
group = {$group}

listen = {$socketPath}
listen.owner = {$user}
listen.group = {$group}
listen.mode = 0660

; Process manager settings (auto-calculated based on server RAM)
pm = dynamic
pm.max_children = {$pool['max_children']}
pm.start_servers = {$pool['start_servers']}
pm.min_spare_servers = {$pool['min_spare']}
pm.max_spare_servers = {$pool['max_spare']}
pm.max_requests = 500

; PHP settings
{$phpAdmin}

; Security: Path isolation via open_basedir (configured above)
; Note: open_basedir restricts file operations to allowed paths only

; Access log
access.log = {$logDir}/{$poolName}-access.log
access.format = "%R - %u %t \"%m %r%Q%q\" %s %f %{mili}d %{kilo}M %C%%"

; Slow log for debugging
slowlog = {$logDir}/{$poolName}-slow.log
request_slowlog_timeout = 5s

; Error log
php_admin_value[error_log] = {$logDir}/{$poolName}-error.log
POOL;
    }

    /**
     * Test PHP-FPM configuration.
     *
     * @param string $phpVersion The PHP version
     * @param string|null $poolConfigPath Optional pool config path
     * @return array{success: bool, output?: string, error?: string}
     */
    abstract public function testConfig(string $phpVersion, ?string $poolConfigPath = null): array;

    /**
     * Restart PHP-FPM service.
     *
     * @param string $phpVersion The PHP version
     * @return array{success: bool, message?: string, error?: string}
     */
    abstract public function restart(string $phpVersion): array;

    /**
     * Reload PHP-FPM service.
     *
     * @param string $phpVersion The PHP version
     * @return array{success: bool, message?: string, error?: string}
     */
    abstract public function reload(string $phpVersion): array;
}
