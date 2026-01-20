<?php

namespace App\Services\Nginx;
use App\Models\Website;

class DebianNginxService extends AbstractNginxService
{
    /**
     * Create a new DebianNginxService instance.
     */
    public function __construct()
    {
        $this->sitesAvailable = '/etc/nginx/sites-available';
        $this->sitesEnabled = '/etc/nginx/sites-enabled';
        $this->configTestCmd = 'sudo /usr/sbin/nginx -t';
        $this->reloadCmd = 'sudo /bin/systemctl reload nginx';
    }

    /**
     * {@inheritdoc}
     */
    public function getOsFamily(): string
    {
        return 'debian';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpFpmSocketPath(string $phpVersion, string $poolName, ?string $customPool = null): string
    {
        if ($customPool) {
            return "/var/run/php/php{$phpVersion}-fpm-{$poolName}.sock";
        }
        return "/var/run/php/php{$phpVersion}-fpm.sock";
    }

    /**
     * {@inheritdoc}
     */
    protected function getFastcgiConfig(): string
    {
        return '        include snippets/fastcgi-php.conf;';
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigFilename(Website $website): string
    {
        return $website->domain;
    }
}
