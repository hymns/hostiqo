<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Update existing database records with default type and port values.
 * 
 * This seeder is run during the update process to ensure existing
 * installations have proper type and port values set for all databases.
 */
class UpdateExistingDatabasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Check if type column exists (migration may not have run yet)
        if (!Schema::hasColumn('databases', 'type')) {
            $this->command->warn('Type column does not exist yet, skipping seeder');
            return;
        }
        
        // Update all databases without a type to mysql
        $updated = DB::table('databases')
            ->whereNull('type')
            ->orWhere('type', '')
            ->update([
                'type' => 'mysql',
                'port' => 3306,
            ]);
        
        if ($updated > 0) {
            $this->command->info("Updated {$updated} existing database(s) with default type=mysql");
        } else {
            $this->command->info('No databases needed updating');
        }
        
        // Ensure config file exists
        $this->ensureConfigFile();
    }
    
    /**
     * Ensure /etc/hostiqo/config.json exists with default values.
     *
     * @return void
     */
    protected function ensureConfigFile(): void
    {
        $configFile = '/etc/hostiqo/config.json';
        
        if (!file_exists($configFile)) {
            $defaultConfig = [
                'databases' => [
                    'mysql' => [
                        'installed' => true,
                        'version' => null,
                    ],
                    'postgresql' => [
                        'installed' => false,
                        'version' => null,
                    ],
                    'mongodb' => [
                        'installed' => false,
                        'version' => null,
                    ],
                ],
            ];
            
            // Create directory if it doesn't exist
            $dir = dirname($configFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Write config file
            file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT));
            chmod($configFile, 0644);
            
            $this->command->info('Created /etc/hostiqo/config.json with default configuration');
        } else {
            $this->command->info('Config file already exists');
        }
    }
}
