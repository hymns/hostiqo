<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\NginxService;
use App\Services\PhpFpmService;
use App\Services\Pm2Service;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeployNginxConfig implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Website $website
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NginxService $nginxService, PhpFpmService $phpFpmService, Pm2Service $pm2Service): void
    {
        Log::info('Starting Nginx config deployment', [
            'website_id' => $this->website->id,
            'domain' => $this->website->domain,
            'project_type' => $this->website->project_type
        ]);

        try {
            // Deploy PHP-FPM pool configuration for PHP projects
            if ($this->website->project_type === 'php') {
                $phpFpmResult = $phpFpmService->writePoolConfig($this->website);
                
                if ($phpFpmResult['success']) {
                    Log::info('PHP-FPM pool config created', [
                        'website_id' => $this->website->id,
                        'pool_name' => $phpFpmResult['pool_name'] ?? 'N/A',
                        'socket_path' => $phpFpmResult['socket_path'] ?? 'N/A',
                        'filepath' => $phpFpmResult['filepath'] ?? 'N/A'
                    ]);

                    // Test and reload PHP-FPM with specific pool config
                    $poolConfigPath = $phpFpmResult['filepath'] ?? null;
                    $testResult = $phpFpmService->testConfig($this->website->php_version, $poolConfigPath);
                    
                    if ($testResult['success']) {
                        Log::info('PHP-FPM config test passed', [
                            'website_id' => $this->website->id,
                            'output' => $testResult['output']
                        ]);
                        $phpFpmService->reloadService($this->website->php_version);
                    } else {
                        throw new \Exception("PHP-FPM config test failed: " . ($testResult['output'] ?? 'Unknown error'));
                    }
                } else {
                    Log::warning('PHP-FPM pool config creation failed', [
                        'website_id' => $this->website->id,
                        'error' => $phpFpmResult['error'] ?? 'Unknown error'
                    ]);
                }
            }
            
            // Deploy PM2 ecosystem configuration for Node.js projects
            if ($this->website->project_type === 'node') {
                $pm2Result = $pm2Service->writeEcosystemConfig($this->website);
                
                if ($pm2Result['success']) {
                    Log::info('PM2 ecosystem config created', [
                        'website_id' => $this->website->id,
                        'filepath' => $pm2Result['filepath'] ?? 'N/A'
                    ]);
                } else {
                    Log::warning('PM2 ecosystem config creation failed', [
                        'website_id' => $this->website->id,
                        'error' => $pm2Result['error'] ?? 'Unknown error'
                    ]);
                }
            }

            // Deploy Nginx configuration
            $result = $nginxService->deploy($this->website);

            if ($result['success']) {
                Log::info('Nginx config deployed successfully', [
                    'website_id' => $this->website->id,
                    'domain' => $this->website->domain
                ]);

                // Update website status
                $this->website->update([
                    'nginx_status' => 'active'
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            Log::error('Failed to deploy Nginx config', [
                'website_id' => $this->website->id,
                'domain' => $this->website->domain,
                'error' => $e->getMessage()
            ]);

            // Update website status
            $this->website->update([
                'nginx_status' => 'failed'
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('DeployNginxConfig job failed', [
            'website_id' => $this->website->id,
            'error' => $exception->getMessage()
        ]);

        $this->website->update([
            'nginx_status' => 'failed'
        ]);
    }
}
