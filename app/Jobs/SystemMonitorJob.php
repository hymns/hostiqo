<?php

namespace App\Jobs;

use App\Models\SystemMetric;
use App\Services\SystemMonitorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SystemMonitorJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     *
     * @param SystemMonitorService $monitorService The system monitor service
     * @return void
     */
    public function handle(SystemMonitorService $monitorService): void
    {
        try {
            // Record current metrics
            $monitorService->record();

            // Clean up old metrics based on retention period
            $retentionHours = config('monitoring.retention_hours', 24);
            SystemMetric::deleteOldMetrics($retentionHours);

            Log::channel('monitoring')->info('System metrics recorded successfully');
        } catch (\Exception $e) {
            Log::channel('monitoring')->error('Failed to record system metrics: ' . $e->getMessage());
            throw $e;
        }
    }
}
