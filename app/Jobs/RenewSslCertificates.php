<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class RenewSslCertificates implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting SSL certificate renewal check...');

        try {
            // Run certbot renew command
            // --quiet: Only show errors
            // --no-self-upgrade: Don't upgrade certbot itself
            // --deploy-hook: Reload Nginx after successful renewal
            $result = Process::run(
                'sudo certbot renew --quiet --no-self-upgrade --deploy-hook "sudo systemctl reload nginx"'
            );

            if ($result->successful()) {
                $output = trim($result->output());
                
                if (!empty($output)) {
                    Log::info('SSL renewal output: ' . $output);
                }
                
                Log::info('SSL certificate renewal check completed successfully.');
            } else {
                Log::error('SSL certificate renewal failed: ' . $result->errorOutput());
            }
        } catch (\Exception $e) {
            Log::error('SSL certificate renewal error: ' . $e->getMessage());
        }
    }
}
