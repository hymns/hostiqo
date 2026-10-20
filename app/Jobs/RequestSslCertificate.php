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

    public $tries = 3;
    public $timeout = 300; // SSL can take a while

    /**
     * Seconds to wait before retrying a failed challenge.
     *
     * A new site often fails its first challenge while DNS or a CDN in front of
     * it (e.g. Cloudflare) has not reached this server yet, so retrying at once
     * rarely helps.
     *
     * @var array<int, int>
     */
    protected const RETRY_DELAYS = [60, 180];

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
                $error = $result['error'] ?? 'Unknown error';

                // Retry later instead of throwing, so a challenge that only fails
                // until DNS settles does not leave stack traces in the log
                if ($this->attempts() < $this->tries) {
                    $delay = self::RETRY_DELAYS[$this->attempts() - 1] ?? self::RETRY_DELAYS[count(self::RETRY_DELAYS) - 1];

                    Log::warning('SSL challenge failed, retrying', [
                        'website_id' => $this->website->id,
                        'domain' => $this->website->domain,
                        'attempt' => $this->attempts(),
                        'retry_in' => $delay,
                        'error' => $error,
                    ]);

                    $this->release($delay);
                    return;
                }

                $this->fail(new \Exception("SSL certificate request failed for {$this->website->domain}:\n{$error}"));
            }
        } catch (\Exception $e) {
            // Missing directories or files: retrying will not help
            $this->fail($e);
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
