<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\CloudflareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CloudflareController extends Controller
{
    private CloudflareService $cloudflare;

    public function __construct(CloudflareService $cloudflare)
    {
        $this->cloudflare = $cloudflare;
    }

    /**
     * Sync DNS record for a website
     */
    public function sync(Request $request, Website $website)
    {
        if (!$this->cloudflare->isConfigured()) {
            return back()->with('error', 'Cloudflare is not configured. Please add CLOUDFLARE_API_TOKEN to .env');
        }

        // Get server IP
        $serverIp = $this->cloudflare->getServerIp();
        
        if (!$serverIp) {
            return back()->with('error', 'Failed to detect server IP address');
        }

        try {
            // Update DNS status to pending
            $website->update([
                'dns_status' => 'pending',
                'dns_error' => null,
            ]);

            // Get zone ID if not exists
            if (!$website->cloudflare_zone_id) {
                $zoneId = $this->cloudflare->getZoneId($website->domain);
                
                if (!$zoneId) {
                    throw new \Exception("Cloudflare zone not found for domain: {$website->domain}");
                }
                
                $website->cloudflare_zone_id = $zoneId;
            }

            // Create or update DNS record
            if ($website->cloudflare_record_id) {
                // Update existing record
                $result = $this->cloudflare->updateDnsRecord(
                    $website->cloudflare_zone_id,
                    $website->cloudflare_record_id,
                    $website->domain,
                    $serverIp,
                    config('services.cloudflare.proxied', false)
                );
            } else {
                // Create new record
                $result = $this->cloudflare->createDnsRecord(
                    $website->cloudflare_zone_id,
                    $website->domain,
                    $serverIp,
                    config('services.cloudflare.proxied', false)
                );

                if ($result['success']) {
                    $website->cloudflare_record_id = $result['record_id'];
                }
            }

            if ($result['success']) {
                // Also create/update www subdomain record
                $wwwDomain = "www.{$website->domain}";
                
                if ($website->cloudflare_www_record_id) {
                    // Update existing www record
                    $wwwResult = $this->cloudflare->updateDnsRecord(
                        $website->cloudflare_zone_id,
                        $website->cloudflare_www_record_id,
                        $wwwDomain,
                        $serverIp,
                        config('services.cloudflare.proxied', false)
                    );
                } else {
                    // Create new www record
                    $wwwResult = $this->cloudflare->createDnsRecord(
                        $website->cloudflare_zone_id,
                        $wwwDomain,
                        $serverIp,
                        config('services.cloudflare.proxied', false)
                    );

                    if ($wwwResult['success']) {
                        $website->cloudflare_www_record_id = $wwwResult['record_id'];
                    }
                }

                $website->update([
                    'cloudflare_zone_id' => $website->cloudflare_zone_id,
                    'cloudflare_record_id' => $website->cloudflare_record_id,
                    'cloudflare_www_record_id' => $website->cloudflare_www_record_id,
                    'server_ip' => $serverIp,
                    'dns_status' => 'active',
                    'dns_error' => null,
                    'dns_last_synced_at' => now(),
                ]);

                Log::info('DNS synced successfully', [
                    'website_id' => $website->id,
                    'domain' => $website->domain,
                    'www_domain' => $wwwDomain,
                    'ip' => $serverIp,
                    'www_success' => $wwwResult['success'] ?? false,
                ]);

                $message = "DNS records synced successfully. {$website->domain} → {$serverIp}";
                if (isset($wwwResult['success']) && $wwwResult['success']) {
                    $message .= " and {$wwwDomain} → {$serverIp}";
                }

                return back()->with('success', $message);
            } else {
                throw new \Exception($result['error'] ?? 'Failed to sync DNS record');
            }
        } catch (\Exception $e) {
            Log::error('DNS sync failed', [
                'website_id' => $website->id,
                'domain' => $website->domain,
                'error' => $e->getMessage(),
            ]);

            $website->update([
                'dns_status' => 'failed',
                'dns_error' => $e->getMessage(),
            ]);

            return back()->with('error', 'DNS sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove DNS record for a website
     */
    public function remove(Website $website)
    {
        if (!$this->cloudflare->isConfigured()) {
            return back()->with('error', 'Cloudflare is not configured');
        }

        if (!$website->cloudflare_zone_id || !$website->cloudflare_record_id) {
            return back()->with('error', 'No DNS record found for this website');
        }

        try {
            // Delete main domain record
            $result = $this->cloudflare->deleteDnsRecord(
                $website->cloudflare_zone_id,
                $website->cloudflare_record_id
            );

            // Also delete www subdomain record if exists
            $wwwDeleted = false;
            if ($website->cloudflare_www_record_id) {
                $wwwResult = $this->cloudflare->deleteDnsRecord(
                    $website->cloudflare_zone_id,
                    $website->cloudflare_www_record_id
                );
                $wwwDeleted = $wwwResult['success'] ?? false;
            }

            if ($result['success']) {
                $website->update([
                    'cloudflare_zone_id' => null,
                    'cloudflare_record_id' => null,
                    'cloudflare_www_record_id' => null,
                    'dns_status' => 'none',
                    'dns_error' => null,
                    'server_ip' => null,
                ]);

                Log::info('DNS records removed', [
                    'website_id' => $website->id,
                    'domain' => $website->domain,
                    'www_deleted' => $wwwDeleted,
                ]);

                $message = 'DNS record removed successfully';
                if ($wwwDeleted) {
                    $message = 'DNS records removed successfully (including www subdomain)';
                }

                return back()->with('success', $message);
            } else {
                throw new \Exception($result['error'] ?? 'Failed to remove DNS record');
            }
        } catch (\Exception $e) {
            Log::error('Failed to remove DNS record', [
                'website_id' => $website->id,
                'domain' => $website->domain,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to remove DNS record: ' . $e->getMessage());
        }
    }

    /**
     * Verify Cloudflare API token
     */
    public function verifyToken(Request $request)
    {
        if (!$this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare is not configured',
            ]);
        }

        $result = $this->cloudflare->verifyToken();

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] 
                ? 'Cloudflare API token is valid' 
                : 'Invalid Cloudflare API token: ' . ($result['error'] ?? 'Unknown error'),
            'data' => $result['data'] ?? null,
        ]);
    }

    /**
     * Get server IP
     */
    public function getServerIp(Request $request)
    {
        $ip = $this->cloudflare->getServerIp();

        if ($ip) {
            return response()->json([
                'success' => true,
                'ip' => $ip,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to detect server IP',
        ]);
    }
}
