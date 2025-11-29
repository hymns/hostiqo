<?php

namespace App\Jobs;

use App\Models\Website;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class CheckSslCertificates implements ShouldQueue
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
        Log::info('Starting SSL certificate check...');

        $websites = Website::where('ssl_enabled', true)
            ->where('ssl_status', 'active')
            ->get();

        foreach ($websites as $website) {
            $this->checkCertificate($website);
        }

        Log::info('SSL certificate check completed. Checked ' . $websites->count() . ' websites.');
    }

    /**
     * Check SSL certificate for a website
     */
    protected function checkCertificate(Website $website): void
    {
        try {
            // Use openssl to get certificate info
            $command = sprintf(
                'echo | openssl s_client -servername %s -connect %s:443 2>/dev/null | openssl x509 -noout -dates -issuer',
                escapeshellarg($website->domain),
                escapeshellarg($website->domain)
            );

            $result = Process::run($command);

            if ($result->successful()) {
                $output = $result->output();
                
                // Parse certificate info
                $certInfo = $this->parseCertificateInfo($output);
                
                if ($certInfo) {
                    $website->update([
                        'ssl_issuer' => $certInfo['issuer'],
                        'ssl_issued_at' => $certInfo['not_before'],
                        'ssl_expires_at' => $certInfo['not_after'],
                        'ssl_last_checked_at' => now(),
                    ]);

                    Log::info("Updated SSL info for {$website->domain}: Expires on {$certInfo['not_after']}");
                }
            } else {
                Log::warning("Failed to check SSL certificate for {$website->domain}");
                $website->update(['ssl_last_checked_at' => now()]);
            }
        } catch (\Exception $e) {
            Log::error("Error checking SSL certificate for {$website->domain}: " . $e->getMessage());
        }
    }

    /**
     * Parse certificate information from openssl output
     */
    protected function parseCertificateInfo(string $output): ?array
    {
        try {
            $info = [];

            // Extract issuer
            if (preg_match('/issuer=(.+)/', $output, $matches)) {
                // Extract CN (Common Name) from issuer string
                if (preg_match('/CN\s*=\s*([^,\n]+)/', $matches[1], $cnMatches)) {
                    $info['issuer'] = trim($cnMatches[1]);
                } else {
                    $info['issuer'] = trim($matches[1]);
                }
            }

            // Extract not before date
            if (preg_match('/notBefore=(.+)/', $output, $matches)) {
                $info['not_before'] = \Carbon\Carbon::parse(trim($matches[1]));
            }

            // Extract not after date
            if (preg_match('/notAfter=(.+)/', $output, $matches)) {
                $info['not_after'] = \Carbon\Carbon::parse(trim($matches[1]));
            }

            return isset($info['not_after']) ? $info : null;
        } catch (\Exception $e) {
            Log::error('Failed to parse certificate info: ' . $e->getMessage());
            return null;
        }
    }
}
