<?php

namespace App\Services\ServiceManager;

class DebianServiceManagerService extends AbstractServiceManagerService
{
    /**
     * Create a new DebianServiceManagerService instance.
     */
    public function __construct()
    {
        $this->supportedServices = $this->buildServiceList();
    }

    /**
     * {@inheritdoc}
     */
    public function getOsFamily(): string
    {
        return 'debian';
    }

    /**
     * Build the list of supported services for Debian/Ubuntu.
     *
     * @return array<string, array> List of supported services
     */
    protected function buildServiceList(): array
    {
        return [
            // Web Server
            'nginx' => [
                'name' => 'Nginx',
                'service' => 'nginx',
                'supports_reload' => true,
                'icon' => 'hexagon'
            ],
            
            // PHP-FPM versions
            'php86-fpm' => [
                'name' => 'PHP 8.6 FPM',
                'service' => 'php8.6-fpm',
                'supports_reload' => true,
                'icon' => 'code'
            ],
            'php85-fpm' => [
                'name' => 'PHP 8.5 FPM',
                'service' => 'php8.5-fpm',
                'supports_reload' => true,
                'icon' => 'code'
            ],
            'php84-fpm' => [
                'name' => 'PHP 8.4 FPM',
                'service' => 'php8.4-fpm',
                'supports_reload' => true,
                'icon' => 'code'
            ],
            'php83-fpm' => [
                'name' => 'PHP 8.3 FPM',
                'service' => 'php8.3-fpm',
                'supports_reload' => true,
                'icon' => 'code'
            ],
            'php82-fpm' => [
                'name' => 'PHP 8.2 FPM',
                'service' => 'php8.2-fpm',
                'supports_reload' => true,
                'icon' => 'code'
            ],
            'php81-fpm' => [
                'name' => 'PHP 8.1 FPM',
                'service' => 'php8.1-fpm',
                'supports_reload' => true,
                'icon' => 'code'
            ],
            
            // Database
            'mysql' => [
                'name' => 'MySQL',
                'service' => 'mysql',
                'supports_reload' => true,
                'icon' => 'database'
            ],
            'mariadb' => [
                'name' => 'MariaDB',
                'service' => 'mariadb',
                'supports_reload' => true,
                'icon' => 'database'
            ],
            
            // Cache
            'redis' => [
                'name' => 'Redis',
                'service' => 'redis-server',
                'supports_reload' => false,
                'icon' => 'layers'
            ],
            
            // Process Manager
            'supervisor' => [
                'name' => 'Supervisor',
                'service' => 'supervisor',
                'supports_reload' => true,
                'icon' => 'activity'
            ],
            
            // Firewall
            'ufw' => [
                'name' => 'UFW Firewall',
                'service' => 'ufw',
                'supports_reload' => false,
                'icon' => 'shield'
            ],
            
            // SSH
            'ssh' => [
                'name' => 'SSH Server',
                'service' => 'ssh',
                'supports_reload' => true,
                'icon' => 'terminal'
            ],
            
            // Cron
            'cron' => [
                'name' => 'Cron',
                'service' => 'cron',
                'supports_reload' => true,
                'icon' => 'clock'
            ],
        ];
    }
}
