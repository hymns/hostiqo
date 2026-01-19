<?php

namespace App\Services\Nginx;

use App\Contracts\NginxInterface;
use App\Models\Website;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

abstract class AbstractNginxService implements NginxInterface
{
    protected string $sitesAvailable;
    protected string $sitesEnabled;
    protected string $configTestCmd;
    protected string $reloadCmd;

    abstract public function getOsFamily(): string;
    abstract public function getPhpFpmSocketPath(string $phpVersion, string $poolName, ?string $customPool = null): string;
    abstract protected function getFastcgiConfig(): string;

    /**
     * Generate Nginx configuration for a website.
     *
     * @param Website $website The website model
     * @return string The generated Nginx configuration
     */
    public function generateConfig(Website $website): string
    {
        return match($website->project_type) {
            'php' => $this->generatePhpConfig($website),
            'reverse-proxy' => $this->generateReverseProxyConfig($website),
            'static' => $this->generateStaticConfig($website),
            default => $this->generateStaticConfig($website),
        };
    }

    /**
     * Generate PHP site configuration.
     *
     * @param Website $website The website model
     * @return string The PHP site configuration
     */
    protected function generatePhpConfig(Website $website): string
    {
        $workingDir = $website->working_directory ?? '';
        $documentRoot = rtrim($website->root_path, '/') . ($workingDir ? '/' . $workingDir : '');
        
        $sslConfig = $website->ssl_enabled ? $this->getSslConfig($website->domain) : '';
        $wwwRedirectConfig = $this->getWwwRedirectConfig($website);
        $securityHeaders = $this->getSecurityHeaders();
        
        $poolName = $website->php_pool_name ?? str_replace('.', '_', $website->domain);
        $socketPath = $this->getPhpFpmSocketPath($website->php_version, $poolName, $website->php_pool_name);
        $logDir = '/var/log/nginx';
        $fastcgiConfig = $this->getFastcgiConfig();

        return <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$website->domain} www.{$website->domain};

{$sslConfig}
{$wwwRedirectConfig}

    root {$documentRoot};
    index index.php index.html index.htm;

    # Logging
    access_log {$logDir}/{$website->domain}-access.log;
    error_log {$logDir}/{$website->domain}-error.log;

    # Security: Limit request body size
    client_max_body_size 100M;
    client_body_buffer_size 128k;

    # Security: Timeouts
    client_body_timeout 12;
    client_header_timeout 12;
    keepalive_timeout 15;
    send_timeout 10;

{$securityHeaders}

    # Allow Let's Encrypt ACME challenge
    location ^~ /.well-known/acme-challenge/ {
        allow all;
        root {$documentRoot};
        try_files \$uri =404;
    }

    # Main location
    location / {
        # Rate limiting with Cloudflare bypass (10 req/s, burst 20)
        limit_req zone=cloudflare_bypass burst=20 nodelay;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP processing
    location ~ \.php$ {
{$fastcgiConfig}
        fastcgi_pass unix:{$socketPath};
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_read_timeout 300;
    }

    # Security: Deny access to hidden files (except .well-known)
    location ~ /\.(?!well-known) {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Security: Deny access to sensitive files
    location ~* \.(env|log|md|sql|sqlite|conf|ini|bak|old|tmp|swp)$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt|tar|gz|woff|woff2|ttf|svg|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
NGINX;
    }

    /**
     * Generate reverse proxy configuration.
     *
     * @param Website $website The website model
     * @return string The reverse proxy configuration
     */
    protected function generateReverseProxyConfig(Website $website): string
    {
        $workingDir = $website->working_directory ?? '';
        $documentRoot = rtrim($website->root_path, '/') . ($workingDir ? '/' . $workingDir : '');
        
        $sslConfig = $website->ssl_enabled ? $this->getSslConfig($website->domain) : '';
        $wwwRedirectConfig = $this->getWwwRedirectConfig($website);
        $securityHeaders = $this->getSecurityHeaders();
        $logDir = '/var/log/nginx';
        $port = $website->port ?? 3000;
        $runtime = $website->runtime ?? 'Unknown';

        return <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$website->domain} www.{$website->domain};

{$sslConfig}
{$wwwRedirectConfig}

    # Logging
    access_log {$logDir}/{$website->domain}-access.log;
    error_log {$logDir}/{$website->domain}-error.log;

{$securityHeaders}

    # Allow Let's Encrypt ACME challenge
    location ^~ /.well-known/acme-challenge/ {
        allow all;
        root {$documentRoot};
        try_files \$uri =404;
    }

    # Reverse Proxy to {$runtime} application
    location / {
        # Rate limiting with Cloudflare bypass (10 req/s, burst 20)
        limit_req zone=cloudflare_bypass burst=20 nodelay;
        
        proxy_pass http://127.0.0.1:{$port};
        proxy_http_version 1.1;
        
        # WebSocket support
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Proxy headers
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header X-Forwarded-Host \$host;
        proxy_set_header X-Forwarded-Port \$server_port;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Buffering
        proxy_buffering on;
        proxy_buffer_size 4k;
        proxy_buffers 8 4k;
        proxy_busy_buffers_size 8k;
    }

    # Security: Deny access to hidden files (except .well-known)
    location ~ /\.(?!well-known) {
        deny all;
        access_log off;
        log_not_found off;
    }
}
NGINX;
    }

    /**
     * Generate static site configuration.
     *
     * @param Website $website The website model
     * @return string The static site configuration
     */
    protected function generateStaticConfig(Website $website): string
    {
        $workingDir = $website->working_directory ?? '';
        $documentRoot = rtrim($website->root_path, '/') . ($workingDir ? '/' . $workingDir : '');
        
        $sslConfig = $website->ssl_enabled ? $this->getSslConfig($website->domain) : '';
        $wwwRedirectConfig = $this->getWwwRedirectConfig($website);
        $securityHeaders = $this->getSecurityHeaders();
        $logDir = '/var/log/nginx';

        return <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$website->domain} www.{$website->domain};

{$sslConfig}
{$wwwRedirectConfig}

    root {$documentRoot};
    index index.html index.htm;

    # Logging
    access_log {$logDir}/{$website->domain}-access.log;
    error_log {$logDir}/{$website->domain}-error.log;

{$securityHeaders}

    # Allow Let's Encrypt ACME challenge
    location ^~ /.well-known/acme-challenge/ {
        allow all;
        root {$documentRoot};
        try_files \$uri =404;
    }

    location / {
        try_files \$uri \$uri/ =404;
    }

    # Security: Deny access to hidden files (except .well-known)
    location ~ /\.(?!well-known) {
        deny all;
    }

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt|tar|gz|woff|woff2|ttf|svg|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
NGINX;
    }

    /**
     * Write Nginx configuration file.
     *
     * @param Website $website The website model
     * @return array{success: bool, filepath?: string, message?: string, error?: string}
     */
    public function writeConfig(Website $website): array
    {
        try {
            $config = $this->generateConfig($website);
            $filename = $website->domain . '.conf';
            $filepath = "{$this->sitesAvailable}/{$filename}";

            // Write to temporary file first
            $tempFile = tempnam(sys_get_temp_dir(), 'nginx_');
            File::put($tempFile, $config);

            // Move to nginx directory with sudo
            $result = Process::run("sudo /bin/cp {$tempFile} {$filepath}");
            @unlink($tempFile);

            if ($result->failed()) {
                throw new \Exception("Failed to write config: " . $result->errorOutput());
            }

            Process::run("sudo /bin/chmod 644 {$filepath}");

            return [
                'success' => true,
                'filepath' => $filepath,
                'message' => 'Nginx configuration written successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to write Nginx config', [
                'website_id' => $website->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete Nginx configuration file.
     *
     * @param Website $website The website model
     * @return array{success: bool, message?: string, error?: string}
     */
    public function deleteConfig(Website $website): array
    {
        try {
            $filename = $website->domain . '.conf';
            
            // Remove from sites-enabled first
            $this->disableSite($website);
            
            // Remove from sites-available
            $filepath = "{$this->sitesAvailable}/{$filename}";
            Process::run("sudo /bin/rm -f {$filepath}");

            return [
                'success' => true,
                'message' => 'Nginx configuration deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enable a site.
     *
     * @param Website $website The website model
     * @return array{success: bool, message: string, error?: string}
     */
    public function enableSite(Website $website): array
    {
        $filename = $website->domain . '.conf';
        $source = "{$this->sitesAvailable}/{$filename}";
        $target = "{$this->sitesEnabled}/{$filename}";

        // If sites-available and sites-enabled are the same (RHEL), skip symlink
        if ($this->sitesAvailable === $this->sitesEnabled) {
            return ['success' => true, 'message' => 'Site enabled (no symlink needed)'];
        }

        $result = Process::run("sudo /bin/ln -sf {$source} {$target}");

        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? 'Site enabled' : 'Failed to enable site',
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Disable a site.
     *
     * @param Website $website The website model
     * @return array{success: bool, message: string, error?: string}
     */
    public function disableSite(Website $website): array
    {
        $filename = $website->domain . '.conf';
        $target = "{$this->sitesEnabled}/{$filename}";

        // If sites-available and sites-enabled are the same (RHEL), don't remove
        if ($this->sitesAvailable === $this->sitesEnabled) {
            return ['success' => true, 'message' => 'Site disabled (no symlink to remove)'];
        }

        $result = Process::run("sudo /bin/rm -f {$target}");

        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? 'Site disabled' : 'Failed to disable site',
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Test Nginx configuration.
     *
     * @return array{success: bool, output: string}
     */
    public function testConfig(): array
    {
        $result = Process::run($this->configTestCmd);

        return [
            'success' => $result->successful(),
            'output' => $result->output() . $result->errorOutput(),
        ];
    }

    /**
     * Reload Nginx.
     *
     * @return array{success: bool, message: string, error?: string}
     */
    public function reload(): array
    {
        $result = Process::run($this->reloadCmd);

        return [
            'success' => $result->successful(),
            'message' => $result->successful() ? 'Nginx reloaded' : 'Failed to reload Nginx',
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Deploy website configuration (write, enable, test, reload).
     *
     * @param Website $website The website model
     * @return array{success: bool, message?: string, error?: string}
     */
    public function deploy(Website $website): array
    {
        try {
            // Write config
            $writeResult = $this->writeConfig($website);
            if (!$writeResult['success']) {
                return $writeResult;
            }

            // Enable site
            $enableResult = $this->enableSite($website);
            if (!$enableResult['success']) {
                return $enableResult;
            }

            // Test config
            $testResult = $this->testConfig();
            if (!$testResult['success']) {
                // Rollback - disable site
                $this->disableSite($website);
                return [
                    'success' => false,
                    'error' => 'Nginx config test failed: ' . ($testResult['output'] ?? 'Unknown error'),
                ];
            }

            // Reload nginx
            $reloadResult = $this->reload();
            if (!$reloadResult['success']) {
                return $reloadResult;
            }

            return [
                'success' => true,
                'message' => 'Website deployed successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get SSL configuration block.
     *
     * @param string $domain The domain name
     * @return string The SSL configuration block
     */
    protected function getSslConfig(string $domain): string
    {
        // Don't include external SSL config files to avoid duplicates
        // Hostiqo manages its own SSL config inline
        return <<<SSL
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    
    ssl_certificate /etc/letsencrypt/live/{$domain}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{$domain}/privkey.pem;
    
    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
    
    # SSL Session
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1440m;
    ssl_session_tickets off;
    
    # OCSP Stapling
    ssl_stapling on;
    ssl_stapling_verify on;
    ssl_trusted_certificate /etc/letsencrypt/live/{$domain}/chain.pem;
    resolver 1.1.1.1 1.0.0.1 8.8.8.8 8.8.4.4 valid=300s;
    resolver_timeout 5s;
SSL;
    }

    /**
     * Get www redirect configuration.
     *
     * @param Website $website The website model
     * @return string The www redirect configuration
     */
    protected function getWwwRedirectConfig(Website $website): string
    {
        if (!$website->www_redirect) {
            return '';
        }

        $redirectTo = $website->www_redirect === 'www' 
            ? "www.{$website->domain}" 
            : $website->domain;

        return <<<REDIRECT
    # WWW Redirect
    if (\$host != '{$redirectTo}') {
        return 301 \$scheme://{$redirectTo}\$request_uri;
    }
REDIRECT;
    }

    /**
     * Get security headers.
     *
     * @return string The security headers configuration
     */
    protected function getSecurityHeaders(): string
    {
        return <<<HEADERS
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
HEADERS;
    }
}
