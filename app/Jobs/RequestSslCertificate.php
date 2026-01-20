<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\SslService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RequestSslCertificate implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $timeout = 300; // SSL can take a while

    /**
     * Create a new job instance.
     *
     * @param Website $website The website to request SSL for
     */
    public function __construct(
        public Website $website
    ) {}

    /**
     * Execute the job.
     *
     * @param SslService $sslService The SSL service
     * @return void
     */
    public function handle(SslService $sslService): void
    {
        Log::info('Starting SSL certificate request', [
            'website_id' => $this->website->id,
            'domain' => $this->website->domain
        ]);

        try {
            // Check if webroot directory exists before requesting SSL
            $fullPath = rtrim($this->website->root_path, '/') . '/' . ltrim($this->website->working_directory ?? '/', '/');
            
            if (!file_exists($this->website->root_path)) {
                throw new \Exception('Website root directory does not exist. Please deploy your application first using webhook or file manager.');
            }
            
            if (!file_exists($fullPath)) {
                throw new \Exception('Working directory does not exist. Please deploy your application first using webhook or file manager.');
            }

            // Update status to pending
            $this->website->update([
                'ssl_status' => 'pending'
            ]);

            // Request SSL certificate
            $result = $sslService->requestCertificate($this->website);

            if ($result['success']) {
                Log::info('SSL certificate obtained from certbot', [
                    'website_id' => $this->website->id,
                    'domain' => $this->website->domain
                ]);

                // Verify certificate actually exists before enabling SSL
                if (!$sslService->certificateExists($this->website->domain)) {
                    throw new \Exception('SSL certificate files not found after certbot request. Certificate may not have been issued.');
                }

                Log::info('SSL certificate files verified', [
                    'website_id' => $this->website->id,
                    'domain' => $this->website->domain
                ]);

                // Update website status
                $this->website->update([
                    'ssl_status' => 'active',
                    'ssl_enabled' => true
                ]);

                // IMPORTANT: Re-deploy Nginx config with SSL to overwrite any certbot modifications
                // Certbot may have modified the config with wrong certificate paths
                Log::info('Re-deploying Nginx config to ensure correct SSL certificate paths', [
                    'website_id' => $this->website->id,
                    'domain' => $this->website->domain
                ]);
                
                dispatch(new DeployNginxConfig($this->website));
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            Log::error('Failed to request SSL certificate', [
                'website_id' => $this->website->id,
                'domain' => $this->website->domain,
                'error' => $e->getMessage()
            ]);

            // Update website status
            $this->website->update([
                'ssl_status' => 'failed',
                'ssl_enabled' => false
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception The exception that caused the failure
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RequestSslCertificate job failed', [
            'website_id' => $this->website->id,
            'error' => $exception->getMessage()
        ]);

        $this->website->update([
            'ssl_status' => 'failed',
            'ssl_enabled' => false
        ]);
    }
}
