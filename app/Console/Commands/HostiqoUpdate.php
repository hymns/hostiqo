<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class HostiqoUpdate extends Command
{
    protected $signature = 'hostiqo:update 
                            {--force : Force update without confirmation}
                            {--no-backup : Skip database backup}';

    protected $description = 'Update Hostiqo to the latest version';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║       Hostiqo Update Utility             ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        if (!$this->option('force') && !$this->confirm('This will update Hostiqo to the latest version. Continue?')) {
            $this->info('Update cancelled.');
            return 0;
        }

        // Step 1: Enable maintenance mode
        $this->info('');
        $this->warn('Step 1/7: Enabling maintenance mode...');
        Artisan::call('down', ['--retry' => 60]);
        $this->info('✓ Maintenance mode enabled');

        // Step 2: Backup database (optional)
        if (!$this->option('no-backup')) {
            $this->warn('Step 2/7: Creating database backup...');
            $backupPath = storage_path('backups');
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            $backupFile = $backupPath . '/backup_' . date('Y-m-d_His') . '.sql';
            
            $dbConnection = config('database.default');
            $dbConfig = config("database.connections.{$dbConnection}");
            
            if ($dbConfig['driver'] === 'mysql') {
                $result = Process::run(sprintf(
                    'mysqldump -u%s -p%s %s > %s 2>/dev/null',
                    $dbConfig['username'],
                    $dbConfig['password'],
                    $dbConfig['database'],
                    $backupFile
                ));
                
                if ($result->successful()) {
                    $this->info("✓ Database backed up to: {$backupFile}");
                } else {
                    $this->warn('⚠ Database backup failed, continuing anyway...');
                }
            } else {
                $this->info('✓ Skipping backup (non-MySQL database)');
            }
        } else {
            $this->info('Step 2/7: Skipping database backup (--no-backup flag)');
        }

        // Step 3: Pull latest code
        $this->warn('Step 3/7: Pulling latest code from repository...');
        $result = Process::path(base_path())->run('git pull origin master');
        
        if ($result->failed()) {
            $this->error('✗ Git pull failed:');
            $this->error($result->errorOutput());
            Artisan::call('up');
            return 1;
        }
        $this->info('✓ Code updated successfully');

        // Step 4: Install/update dependencies
        $this->warn('Step 4/7: Updating Composer dependencies...');
        $result = Process::path(base_path())->run('composer install --no-dev --optimize-autoloader');
        
        if ($result->failed()) {
            $this->warn('⚠ Composer install had issues, check manually');
        } else {
            $this->info('✓ Composer dependencies updated');
        }

        // Step 5: Run migrations
        $this->warn('Step 5/7: Running database migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->info('✓ Migrations completed');

        // Step 6: Build assets
        $this->warn('Step 6/7: Building frontend assets...');
        Process::path(base_path())->run('npm install');
        $result = Process::path(base_path())->run('npm run build');
        
        if ($result->successful()) {
            $this->info('✓ Frontend assets built');
        } else {
            $this->warn('⚠ Asset build had issues, check manually');
        }

        // Step 7: Clear and optimize caches
        $this->warn('Step 7/7: Optimizing application...');
        Artisan::call('optimize:clear');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        $this->info('✓ Application optimized');

        // Disable maintenance mode
        Artisan::call('up');
        
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║     ✓ Hostiqo updated successfully!      ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        return 0;
    }
}
