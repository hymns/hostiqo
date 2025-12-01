<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    private string $apiToken;
    private string $apiUrl = 'https://api.cloudflare.com/client/v4';

    public function __construct()
    {
        $this->apiToken = config('services.cloudflare.api_token', '');
    }

    /**
     * Get Cloudflare zone ID by domain name
     */
    public function getZoneId(string $domain): ?string
    {
        try {
            // Extract root domain (remove subdomain if exists)
            $rootDomain = $this->extractRootDomain($domain);

            $response = Http::withToken($this->apiToken)
                ->get("{$this->apiUrl}/zones", [
                    'name' => $rootDomain,
                    'status' => 'active',
                ]);

            if ($response->successful() && $response->json('success')) {
                $zones = $response->json('result', []);
                
                if (!empty($zones)) {
                    return $zones[0]['id'];
                }
            }

            Log::error('Cloudflare zone not found', [
                'domain' => $domain,
                'root_domain' => $rootDomain,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get Cloudflare zone ID', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create DNS A record
     */
    public function createDnsRecord(string $zoneId, string $domain, string $ipAddress, bool $proxied = false): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->post("{$this->apiUrl}/zones/{$zoneId}/dns_records", [
                    'type' => 'A',
                    'name' => $domain,
                    'content' => $ipAddress,
                    'ttl' => 1, // Auto (Cloudflare proxy)
                    'proxied' => $proxied,
                ]);

            if ($response->successful() && $response->json('success')) {
                $record = $response->json('result');
                
                Log::info('DNS record created successfully', [
                    'domain' => $domain,
                    'ip' => $ipAddress,
                    'record_id' => $record['id'],
                ]);

                return [
                    'success' => true,
                    'record_id' => $record['id'],
                    'data' => $record,
                ];
            }

            $errors = $response->json('errors', []);
            $errorMessage = $this->formatErrors($errors);

            Log::error('Failed to create DNS record', [
                'domain' => $domain,
                'ip' => $ipAddress,
                'errors' => $errors,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('Exception creating DNS record', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update DNS A record
     */
    public function updateDnsRecord(string $zoneId, string $recordId, string $domain, string $ipAddress, bool $proxied = false): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->put("{$this->apiUrl}/zones/{$zoneId}/dns_records/{$recordId}", [
                    'type' => 'A',
                    'name' => $domain,
                    'content' => $ipAddress,
                    'ttl' => 1,
                    'proxied' => $proxied,
                ]);

            if ($response->successful() && $response->json('success')) {
                Log::info('DNS record updated successfully', [
                    'domain' => $domain,
                    'ip' => $ipAddress,
                    'record_id' => $recordId,
                ]);

                return [
                    'success' => true,
                    'data' => $response->json('result'),
                ];
            }

            $errors = $response->json('errors', []);
            $errorMessage = $this->formatErrors($errors);

            Log::error('Failed to update DNS record', [
                'domain' => $domain,
                'ip' => $ipAddress,
                'record_id' => $recordId,
                'errors' => $errors,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('Exception updating DNS record', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete DNS record
     */
    public function deleteDnsRecord(string $zoneId, string $recordId): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->delete("{$this->apiUrl}/zones/{$zoneId}/dns_records/{$recordId}");

            if ($response->successful() && $response->json('success')) {
                Log::info('DNS record deleted successfully', [
                    'record_id' => $recordId,
                ]);

                return [
                    'success' => true,
                ];
            }

            $errors = $response->json('errors', []);
            $errorMessage = $this->formatErrors($errors);

            Log::error('Failed to delete DNS record', [
                'record_id' => $recordId,
                'errors' => $errors,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('Exception deleting DNS record', [
                'record_id' => $recordId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get DNS record details
     */
    public function getDnsRecord(string $zoneId, string $recordId): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->apiUrl}/zones/{$zoneId}/dns_records/{$recordId}");

            if ($response->successful() && $response->json('success')) {
                return [
                    'success' => true,
                    'data' => $response->json('result'),
                ];
            }

            return [
                'success' => false,
                'error' => $this->formatErrors($response->json('errors', [])),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List DNS records for a zone
     */
    public function listDnsRecords(string $zoneId, array $filters = []): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->apiUrl}/zones/{$zoneId}/dns_records", $filters);

            if ($response->successful() && $response->json('success')) {
                return [
                    'success' => true,
                    'data' => $response->json('result', []),
                ];
            }

            return [
                'success' => false,
                'error' => $this->formatErrors($response->json('errors', [])),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get server's public IP address
     */
    public function getServerIp(): ?string
    {
        try {
            // Try multiple services to get public IP
            $services = [
                'https://api.ipify.org',
                'https://icanhazip.com',
                'https://ifconfig.me/ip',
            ];

            foreach ($services as $service) {
                try {
                    $response = Http::timeout(5)->get($service);
                    
                    if ($response->successful()) {
                        $ip = trim($response->body());
                        
                        // Validate IP address
                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            Log::info('Server IP detected', ['ip' => $ip, 'service' => $service]);
                            return $ip;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            Log::warning('Failed to detect server IP from all services');
            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting server IP', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if Cloudflare is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiToken);
    }

    /**
     * Verify API token
     */
    public function verifyToken(): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->apiUrl}/user/tokens/verify");

            if ($response->successful() && $response->json('success')) {
                return [
                    'success' => true,
                    'data' => $response->json('result'),
                ];
            }

            return [
                'success' => false,
                'error' => $this->formatErrors($response->json('errors', [])),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract root domain from subdomain
     */
    private function extractRootDomain(string $domain): string
    {
        // Remove protocol if exists
        $domain = preg_replace('#^https?://#', '', $domain);
        
        // Remove path if exists
        $domain = explode('/', $domain)[0];
        
        // Split by dots
        $parts = explode('.', $domain);
        
        // If domain has more than 2 parts, take last 2 (root domain)
        // Exception: domains like .co.uk, .com.my
        if (count($parts) > 2) {
            return implode('.', array_slice($parts, -2));
        }
        
        return $domain;
    }

    /**
     * Format Cloudflare API errors
     */
    private function formatErrors(array $errors): string
    {
        if (empty($errors)) {
            return 'Unknown error occurred';
        }

        $messages = array_map(function ($error) {
            return $error['message'] ?? 'Unknown error';
        }, $errors);

        return implode(', ', $messages);
    }
}
