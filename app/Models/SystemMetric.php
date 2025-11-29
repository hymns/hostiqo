<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemMetric extends Model
{
    protected $fillable = [
        'cpu_usage',
        'memory_usage',
        'disk_usage',
        'memory_total',
        'memory_used',
        'disk_total',
        'disk_used',
        'disk_read_bytes',
        'disk_write_bytes',
        'network_rx_bytes',
        'network_tx_bytes',
        'db_connections',
        'recorded_at',
    ];

    protected $casts = [
        'cpu_usage' => 'float',
        'memory_usage' => 'float',
        'disk_usage' => 'float',
        'memory_total' => 'integer',
        'memory_used' => 'integer',
        'disk_total' => 'integer',
        'disk_used' => 'integer',
        'disk_read_bytes' => 'integer',
        'disk_write_bytes' => 'integer',
        'network_rx_bytes' => 'integer',
        'network_tx_bytes' => 'integer',
        'db_connections' => 'integer',
        'recorded_at' => 'datetime',
    ];

    /**
     * Get metrics for the last N hours.
     */
    public static function getRecentMetrics(int $hours = 24): \Illuminate\Support\Collection
    {
        return static::where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at')
            ->get();
    }

    /**
     * Get the latest metric.
     */
    public static function getLatest(): ?self
    {
        return static::latest('recorded_at')->first();
    }

    /**
     * Delete old metrics beyond retention period.
     */
    public static function deleteOldMetrics(int $hours = 24): int
    {
        return static::where('recorded_at', '<', now()->subHours($hours))->delete();
    }

    /**
     * Format bytes to human readable format.
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
