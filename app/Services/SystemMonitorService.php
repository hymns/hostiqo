<?php

namespace App\Services;

use App\Models\SystemMetric;
use Illuminate\Support\Facades\Process;

class SystemMonitorService
{
    /**
     * Collect current system metrics.
     */
    public function collectMetrics(): array
    {
        return [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'memory_total' => $this->getMemoryTotal(),
            'memory_used' => $this->getMemoryUsed(),
            'disk_total' => $this->getDiskTotal(),
            'disk_used' => $this->getDiskUsed(),
            'disk_read_bytes' => $this->getDiskReadBytes(),
            'disk_write_bytes' => $this->getDiskWriteBytes(),
            'network_rx_bytes' => $this->getNetworkRxBytes(),
            'network_tx_bytes' => $this->getNetworkTxBytes(),
            'db_connections' => $this->getDbConnections(),
            'recorded_at' => now(),
        ];
    }

    /**
     * Collect and store metrics to database.
     */
    public function record(): SystemMetric
    {
        $metrics = $this->collectMetrics();
        
        return SystemMetric::create($metrics);
    }

    /**
     * Get CPU core count.
     */
    public function getCpuCores(): int
    {
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                // macOS
                $result = Process::run(['sysctl', '-n', 'hw.ncpu']);
                if ($result->successful()) {
                    return (int) trim($result->output());
                }
            } else {
                // Linux
                $result = Process::run(['nproc']);
                if ($result->successful()) {
                    return (int) trim($result->output());
                }
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get CPU cores: ' . $e->getMessage());
        }

        return 1;
    }

    /**
     * Get CPU usage percentage.
     */
    protected function getCpuUsage(): float
    {
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                // macOS
                $result = Process::run(['top', '-l', '1', '-n', '0']);
                if ($result->successful()) {
                    $output = $result->output();
                    if (preg_match('/CPU usage: ([\d.]+)% user, ([\d.]+)% sys, ([\d.]+)% idle/', $output, $matches)) {
                        $user = (float) $matches[1];
                        $sys = (float) $matches[2];
                        return round($user + $sys, 2);
                    }
                }
            } else {
                // Linux
                $result = Process::run(['top', '-bn1']);
                if ($result->successful()) {
                    $output = $result->output();
                    if (preg_match('/%Cpu\(s\):\s+([\d.]+) us,\s+([\d.]+) sy/', $output, $matches)) {
                        $user = (float) $matches[1];
                        $sys = (float) $matches[2];
                        return round($user + $sys, 2);
                    }
                }
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get CPU usage: ' . $e->getMessage());
        }

        return 0.0;
    }

    /**
     * Get memory usage percentage.
     */
    protected function getMemoryUsage(): float
    {
        try {
            $total = $this->getMemoryTotal();
            $used = $this->getMemoryUsed();
            
            if ($total > 0) {
                return round(($used / $total) * 100, 2);
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get memory usage: ' . $e->getMessage());
        }

        return 0.0;
    }

    /**
     * Get disk usage percentage.
     */
    protected function getDiskUsage(): float
    {
        try {
            $total = $this->getDiskTotal();
            $used = $this->getDiskUsed();
            
            if ($total > 0) {
                return round(($used / $total) * 100, 2);
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get disk usage: ' . $e->getMessage());
        }

        return 0.0;
    }

    /**
     * Get total memory in bytes.
     */
    protected function getMemoryTotal(): int
    {
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                // macOS
                $result = Process::run(['sysctl', '-n', 'hw.memsize']);
                if ($result->successful()) {
                    return (int) trim($result->output());
                }
            } else {
                // Linux
                $result = Process::run(['grep', 'MemTotal', '/proc/meminfo']);
                if ($result->successful()) {
                    if (preg_match('/MemTotal:\s+(\d+)/', $result->output(), $matches)) {
                        return (int) $matches[1] * 1024; // Convert KB to bytes
                    }
                }
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get total memory: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Get used memory in bytes.
     */
    protected function getMemoryUsed(): int
    {
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                // macOS - use vm_stat
                $result = Process::run(['vm_stat']);
                if ($result->successful()) {
                    $output = $result->output();
                    $pageSize = 4096; // Default page size
                    
                    // Get page size
                    if (preg_match('/page size of (\d+) bytes/', $output, $matches)) {
                        $pageSize = (int) $matches[1];
                    }
                    
                    // Calculate used pages
                    $active = $wired = $compressed = 0;
                    if (preg_match('/Pages active:\s+(\d+)/', $output, $matches)) {
                        $active = (int) $matches[1];
                    }
                    if (preg_match('/Pages wired down:\s+(\d+)/', $output, $matches)) {
                        $wired = (int) $matches[1];
                    }
                    if (preg_match('/Pages occupied by compressor:\s+(\d+)/', $output, $matches)) {
                        $compressed = (int) $matches[1];
                    }
                    
                    return ($active + $wired + $compressed) * $pageSize;
                }
            } else {
                // Linux
                $result = Process::run(['free', '-b']);
                if ($result->successful()) {
                    $lines = explode("\n", $result->output());
                    if (isset($lines[1]) && preg_match('/Mem:\s+\d+\s+(\d+)/', $lines[1], $matches)) {
                        return (int) $matches[1];
                    }
                }
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get used memory: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Get total disk space in bytes.
     */
    protected function getDiskTotal(): int
    {
        try {
            $path = base_path();
            $total = disk_total_space($path);
            
            return $total !== false ? (int) $total : 0;
        } catch (\Exception $e) {
            logger()->error('Failed to get total disk space: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Get used disk space in bytes.
     */
    protected function getDiskUsed(): int
    {
        try {
            $path = base_path();
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            
            if ($total !== false && $free !== false) {
                return (int) ($total - $free);
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get used disk space: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Get cumulative disk read bytes.
     */
    protected function getDiskReadBytes(): int
    {
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                // macOS - use iostat cumulative stats
                $result = Process::run(['iostat', '-d', '-I']);
                if ($result->successful()) {
                    $output = $result->output();
                    // Extract total MB from all disks (3rd column = MB read)
                    if (preg_match_all('/([\d.]+)\s+\d+\s+([\d.]+)/', $output, $matches)) {
                        $totalMB = 0;
                        foreach ($matches[2] as $mb) {
                            $totalMB += (float) $mb;
                        }
                        return (int) ($totalMB * 1024 * 1024); // Convert MB to bytes
                    }
                }
            } else {
                // Linux - use /proc/diskstats
                if (file_exists('/proc/diskstats')) {
                    $content = file_get_contents('/proc/diskstats');
                    $totalRead = 0;
                    foreach (explode("\n", $content) as $line) {
                        // Find main disk devices (sda, nvme0n1, vda, etc.)
                        if (preg_match('/\s+(sd[a-z]|nvme\d+n\d+|vd[a-z]|xvd[a-z])\s/', $line)) {
                            $fields = preg_split('/\s+/', trim($line));
                            if (isset($fields[5])) {
                                // Field 5 is sectors read, multiply by 512 (sector size)
                                $totalRead += (int) $fields[5] * 512;
                            }
                        }
                    }
                    return $totalRead;
                }
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get disk read bytes: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Get cumulative disk write bytes.
     */
    protected function getDiskWriteBytes(): int
    {
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                // macOS - iostat doesn't show cumulative writes separately
                // Use same value as read for now (iostat -d -I shows combined I/O)
                $result = Process::run(['iostat', '-d', '-I']);
                if ($result->successful()) {
                    $output = $result->output();
                    // Use total MB as approximation
                    if (preg_match_all('/([\d.]+)\s+\d+\s+([\d.]+)/', $output, $matches)) {
                        $totalMB = 0;
                        foreach ($matches[2] as $mb) {
                            $totalMB += (float) $mb;
                        }
                        // Approximate write as 60% of total I/O
                        return (int) ($totalMB * 0.6 * 1024 * 1024);
                    }
                }
            } else {
                // Linux - use /proc/diskstats
                if (file_exists('/proc/diskstats')) {
                    $content = file_get_contents('/proc/diskstats');
                    $totalWrite = 0;
                    foreach (explode("\n", $content) as $line) {
                        // Find main disk devices
                        if (preg_match('/\s+(sd[a-z]|nvme\d+n\d+|vd[a-z]|xvd[a-z])\s/', $line)) {
                            $fields = preg_split('/\s+/', trim($line));
                            if (isset($fields[9])) {
                                // Field 9 is sectors written, multiply by 512 (sector size)
                                $totalWrite += (int) $fields[9] * 512;
                            }
                        }
                    }
                    return $totalWrite;
                }
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get disk write bytes: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Get cumulative network received bytes.
     */
    protected function getNetworkRxBytes(): int
    {
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                // macOS - use netstat, parse Ibytes column
                $result = Process::run(['netstat', '-ib']);
                if ($result->successful()) {
                    $output = $result->output();
                    $totalRx = 0;
                    foreach (explode("\n", $output) as $line) {
                        // Match en* interfaces and extract Ibytes
                        if (preg_match('/^en\d+\s+\d+\s+<Link.*?\s+(\d+)\s+\d+\s+(\d+)\s+(\d+)/', $line, $matches)) {
                            // matches[3] is Ibytes
                            $totalRx += (int) $matches[3];
                        }
                    }
                    return $totalRx;
                }
            } else {
                // Linux - use /proc/net/dev
                if (file_exists('/proc/net/dev')) {
                    $content = file_get_contents('/proc/net/dev');
                    $totalRx = 0;
                    foreach (explode("\n", $content) as $line) {
                        // Skip loopback and header lines
                        if (preg_match('/^\s*(eth|ens|enp|wl|wlan)/', $line)) {
                            $fields = preg_split('/[:\s]+/', trim($line));
                            if (isset($fields[1])) {
                                $totalRx += (int) $fields[1];
                            }
                        }
                    }
                    return $totalRx;
                }
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get network RX bytes: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Get cumulative network transmitted bytes.
     */
    protected function getNetworkTxBytes(): int
    {
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                // macOS - use netstat, parse Obytes column
                $result = Process::run(['netstat', '-ib']);
                if ($result->successful()) {
                    $output = $result->output();
                    $totalTx = 0;
                    foreach (explode("\n", $output) as $line) {
                        // Match en* interfaces and extract Obytes
                        if (preg_match('/^en\d+\s+\d+\s+<Link.*?\s+(\d+)\s+\d+\s+(\d+)\s+(\d+)\s+\d+\s+(\d+)/', $line, $matches)) {
                            // matches[4] is Obytes (0-indexed, 4th capture group)
                            if (isset($matches[4])) {
                                $totalTx += (int) $matches[4];
                            }
                        }
                    }
                    return $totalTx;
                }
            } else {
                // Linux - use /proc/net/dev
                if (file_exists('/proc/net/dev')) {
                    $content = file_get_contents('/proc/net/dev');
                    $totalTx = 0;
                    foreach (explode("\n", $content) as $line) {
                        // Skip loopback and header lines
                        if (preg_match('/^\s*(eth|ens|enp|wl|wlan)/', $line)) {
                            $fields = preg_split('/[:\s]+/', trim($line));
                            if (isset($fields[9])) {
                                $totalTx += (int) $fields[9];
                            }
                        }
                    }
                    return $totalTx;
                }
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get network TX bytes: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Get database connection count.
     */
    protected function getDbConnections(): int
    {
        try {
            // Get connection count from Laravel's DB facade
            $connection = \Illuminate\Support\Facades\DB::connection();
            $driver = $connection->getDriverName();
            
            if ($driver === 'sqlite') {
                // SQLite doesn't have connection pooling, always 1 connection per process
                return 1;
            } elseif ($driver === 'mysql') {
                // MySQL - count active connections
                $result = $connection->select('SHOW STATUS WHERE Variable_name = "Threads_connected"');
                if (!empty($result)) {
                    return (int) $result[0]->Value;
                }
            } elseif ($driver === 'pgsql') {
                // PostgreSQL - count active connections
                $result = $connection->select('SELECT count(*) as count FROM pg_stat_activity WHERE state = \'active\'');
                if (!empty($result)) {
                    return (int) $result[0]->count;
                }
            }
        } catch (\Exception $e) {
            logger()->error('Failed to get DB connections: ' . $e->getMessage());
        }

        return 0;
    }
}
