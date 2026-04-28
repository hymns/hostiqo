<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Bootstrap 5 for pagination
        Paginator::useBootstrapFive();
        
        // Load database configuration from /etc/hostiqo/config.json
        $this->loadDatabaseConfig();
    }
    
    /**
     * Load database configuration from system config file.
     *
     * @return void
     */
    protected function loadDatabaseConfig(): void
    {
        $configFile = '/etc/hostiqo/config.json';
        
        if (file_exists($configFile)) {
            try {
                $config = json_decode(file_get_contents($configFile), true);
                
                if (isset($config['databases'])) {
                    config(['hostiqo.databases' => array_merge(
                        config('hostiqo.databases', []),
                        $config['databases']
                    )]);
                }
            } catch (\Exception $e) {
                // Silently fail if config file is invalid
                logger()->warning('Failed to load Hostiqo config: ' . $e->getMessage());
            }
        }
    }
}
