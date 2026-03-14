<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Exception;

class Fail2banService
{
    /**
     * Get fail2ban service status
     */
    public function getServiceStatus(): array
    {
        $result = Process::run('sudo /bin/systemctl is-active fail2ban');
        $isActive = trim($result->output()) === 'active';
        
        return [
            'running' => $isActive,
            'status' => $isActive ? 'active' : 'inactive',
        ];
    }

    /**
     * Get overall fail2ban status with jail list
     */
    public function getStatus(): array
    {
        $result = Process::timeout(5)->run('sudo /usr/bin/fail2ban-client status');
        
        if (!$result->successful()) {
            return [
                'running' => false,
                'jails' => [],
                'error' => $result->errorOutput() ?: 'Timeout or command failed',
            ];
        }
        
        $output = $result->output();
        $jails = [];
        
        // Parse jail list from output
        if (preg_match('/Jail list:\s*(.+)$/m', $output, $matches)) {
            $jailList = trim($matches[1]);
            if (!empty($jailList)) {
                $jails = array_map('trim', explode(',', $jailList));
            }
        }
        
        return [
            'running' => true,
            'jails' => $jails,
            'jail_count' => count($jails),
        ];
    }

    /**
     * Get detailed status for a specific jail
     */
    public function getJailStatus(string $jail): array
    {
        $result = Process::timeout(5)->run("sudo /usr/bin/fail2ban-client status {$jail}");
        
        if (!$result->successful()) {
            return [
                'name' => $jail,
                'enabled' => false,
                'error' => $result->errorOutput() ?: 'Timeout or command failed',
            ];
        }
        
        $output = $result->output();
        $status = [
            'name' => $jail,
            'enabled' => true,
            'filter' => [],
            'actions' => [],
        ];
        
        // Parse currently failed
        if (preg_match('/Currently failed:\s*(\d+)/m', $output, $matches)) {
            $status['currently_failed'] = (int) $matches[1];
        }
        
        // Parse total failed
        if (preg_match('/Total failed:\s*(\d+)/m', $output, $matches)) {
            $status['total_failed'] = (int) $matches[1];
        }
        
        // Parse currently banned
        if (preg_match('/Currently banned:\s*(\d+)/m', $output, $matches)) {
            $status['currently_banned'] = (int) $matches[1];
        }
        
        // Parse total banned
        if (preg_match('/Total banned:\s*(\d+)/m', $output, $matches)) {
            $status['total_banned'] = (int) $matches[1];
        }
        
        // Parse banned IP list
        if (preg_match('/Banned IP list:\s*(.*)$/m', $output, $matches)) {
            $ipList = trim($matches[1]);
            $status['banned_ips'] = !empty($ipList) ? array_map('trim', explode(' ', $ipList)) : [];
        }
        
        // Get jail settings
        $status['bantime'] = $this->getJailSetting($jail, 'bantime');
        $status['maxretry'] = $this->getJailSetting($jail, 'maxretry');
        $status['findtime'] = $this->getJailSetting($jail, 'findtime');
        
        return $status;
    }

    /**
     * Get a specific jail setting
     */
    protected function getJailSetting(string $jail, string $setting): ?string
    {
        $result = Process::run("sudo /usr/bin/fail2ban-client get {$jail} {$setting}");
        
        if ($result->successful()) {
            return trim($result->output());
        }
        
        return null;
    }

    /**
     * Get all jails with their status (parallel processing)
     */
    public function getAllJailsStatus(): array
    {
        $status = $this->getStatus();
        
        if (!$status['running']) {
            return [];
        }
        
        // Use parallel processing for faster execution
        $pool = Process::pool(function ($pool) use ($status) {
            foreach ($status['jails'] as $jail) {
                $pool->timeout(5)->command("sudo /usr/bin/fail2ban-client status {$jail}");
            }
        });
        
        $results = $pool->start()->wait();
        
        // Parse results
        $jailsStatus = [];
        $jailIndex = 0;
        foreach ($status['jails'] as $jail) {
            $result = $results[$jailIndex++];
            
            if (!$result->successful()) {
                $jailsStatus[] = [
                    'name' => $jail,
                    'enabled' => false,
                    'error' => $result->errorOutput() ?: 'Timeout or command failed',
                ];
                continue;
            }
            
            $output = $result->output();
            $jailStatus = [
                'name' => $jail,
                'enabled' => true,
                'filter' => [],
                'actions' => [],
            ];
            
            // Parse currently failed
            if (preg_match('/Currently failed:\s*(\d+)/m', $output, $matches)) {
                $jailStatus['currently_failed'] = (int) $matches[1];
            }
            
            // Parse total failed
            if (preg_match('/Total failed:\s*(\d+)/m', $output, $matches)) {
                $jailStatus['total_failed'] = (int) $matches[1];
            }
            
            // Parse currently banned
            if (preg_match('/Currently banned:\s*(\d+)/m', $output, $matches)) {
                $jailStatus['currently_banned'] = (int) $matches[1];
            }
            
            // Parse total banned
            if (preg_match('/Total banned:\s*(\d+)/m', $output, $matches)) {
                $jailStatus['total_banned'] = (int) $matches[1];
            }
            
            // Parse banned IP list
            if (preg_match('/Banned IP list:\s*(.+)$/ms', $output, $matches)) {
                $ipList = trim($matches[1]);
                if (!empty($ipList)) {
                    $jailStatus['banned_ips'] = array_filter(array_map('trim', explode("\n", $ipList)));
                } else {
                    $jailStatus['banned_ips'] = [];
                }
            } else {
                $jailStatus['banned_ips'] = [];
            }
            
            $jailsStatus[] = $jailStatus;
        }
        
        return $jailsStatus;
    }

    /**
     * Get all banned IPs across all jails
     */
    public function getAllBannedIps(): array
    {
        // Always use the reliable method: iterate through each jail
        $status = $this->getStatus();
        $allBanned = [];
        
        if (!$status['running']) {
            return [];
        }
        
        foreach ($status['jails'] as $jail) {
            $jailStatus = $this->getJailStatus($jail);
            if (!empty($jailStatus['banned_ips'])) {
                foreach ($jailStatus['banned_ips'] as $ip) {
                    $allBanned[] = [
                        'ip' => $ip,
                        'jail' => $jail,
                    ];
                }
            }
        }
        
        return $allBanned;
    }

    /**
     * Ban an IP address in a specific jail
     */
    public function banIp(string $jail, string $ip): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'message' => 'Invalid IP address'];
        }
        
        $result = Process::run("sudo /usr/bin/fail2ban-client set {$jail} banip {$ip}");
        
        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? "IP {$ip} banned in {$jail}" : $result->errorOutput(),
        ];
    }

    /**
     * Unban an IP address from a specific jail
     */
    public function unbanIp(string $jail, string $ip): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'message' => 'Invalid IP address'];
        }
        
        $result = Process::run("sudo /usr/bin/fail2ban-client set {$jail} unbanip {$ip}");
        
        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? "IP {$ip} unbanned from {$jail}" : $result->errorOutput(),
        ];
    }

    /**
     * Start a jail
     */
    public function startJail(string $jail): array
    {
        $result = Process::run("sudo /usr/bin/fail2ban-client start {$jail}");
        
        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? "Jail {$jail} started" : $result->errorOutput(),
        ];
    }

    /**
     * Stop a jail
     */
    public function stopJail(string $jail): array
    {
        $result = Process::run("sudo /usr/bin/fail2ban-client stop {$jail}");
        
        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? "Jail {$jail} stopped" : $result->errorOutput(),
        ];
    }

    /**
     * Reload fail2ban configuration
     */
    public function reload(): array
    {
        $result = Process::run('sudo /usr/bin/fail2ban-client reload');
        
        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? 'Fail2ban reloaded' : $result->errorOutput(),
        ];
    }

    /**
     * Start fail2ban service
     */
    public function startService(): array
    {
        $result = Process::run('sudo /bin/systemctl start fail2ban');
        
        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? 'Fail2ban service started' : $result->errorOutput(),
        ];
    }

    /**
     * Stop fail2ban service
     */
    public function stopService(): array
    {
        $result = Process::run('sudo /bin/systemctl stop fail2ban');
        
        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? 'Fail2ban service stopped' : $result->errorOutput(),
        ];
    }

    /**
     * Restart fail2ban service
     */
    public function restartService(): array
    {
        $result = Process::run('sudo /bin/systemctl restart fail2ban');
        
        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? 'Fail2ban service restarted' : $result->errorOutput(),
        ];
    }

    /**
     * Get fail2ban log (last N lines)
     */
    public function getLog(int $lines = 100): string
    {
        $result = Process::run("sudo /usr/bin/tail -n {$lines} /var/log/fail2ban.log");
        
        return $result->successful() ? $result->output() : '';
    }

    /**
     * Get whitelist (ignoreip) for a jail
     */
    public function getWhitelist(string $jail): array
    {
        $result = Process::run("sudo /usr/bin/fail2ban-client get {$jail} ignoreip");
        
        if (!$result->successful()) {
            return [];
        }
        
        $output = trim($result->output());
        if (empty($output)) {
            return [];
        }
        
        // Parse the output - format varies
        $ips = preg_split('/[\s,]+/', $output);
        return array_filter($ips);
    }

    /**
     * Get summary statistics
     */
    public function getSummary(): array
    {
        $status = $this->getStatus();
        
        if (!$status['running']) {
            return [
                'running' => false,
                'total_jails' => 0,
                'total_banned' => 0,
                'jails' => [],
            ];
        }
        
        $totalBanned = 0;
        $jailsSummary = [];
        
        foreach ($status['jails'] as $jail) {
            $jailStatus = $this->getJailStatus($jail);
            $banned = $jailStatus['currently_banned'] ?? 0;
            $totalBanned += $banned;
            
            $jailsSummary[] = [
                'name' => $jail,
                'currently_banned' => $banned,
                'total_banned' => $jailStatus['total_banned'] ?? 0,
            ];
        }
        
        return [
            'running' => true,
            'total_jails' => count($status['jails']),
            'total_banned' => $totalBanned,
            'jails' => $jailsSummary,
        ];
    }
}
