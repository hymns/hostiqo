<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class HostiqoUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostiqo:update 
                            {--force : Force update without confirmation}
                            {--no-backup : Skip database backup}
                            {--sudoers : Refresh sudoers configuration after update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Hostiqo to the latest version (run with sudo)';

    /**
     * Get the web user based on OS.
     */
    protected function getWebUser(): string
    {
        // Check if nginx user exists (RHEL-based)
        $result = Process::run('id -u nginx 2>/dev/null');
        if ($result->successful()) {
            return 'nginx';
        }
        
        return 'www-data';
    }

    /**
     * Run command as web user.
     */
    protected function runAsWebUser(string $command, ?string $path = null): \Illuminate\Contracts\Process\ProcessResult
    {
        $webUser = $this->getWebUser();
        $path = $path ?? base_path();
        
        return Process::path($path)->run("sudo -u {$webUser} {$command}");
    }

    /**
     * Execute the console command.
     *
     * @return int Exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║       Hostiqo Update Utility             ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        $webUser = $this->getWebUser();
        $this->info("Web user: {$webUser}");
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
                chown($backupPath, $webUser);
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

        // Step 3: Pull latest code (as web user)
        $this->warn('Step 3/7: Pulling latest code from repository...');
        $result = $this->runAsWebUser('git pull origin master');
        
        if ($result->failed()) {
            $this->error('✗ Git pull failed:');
            $this->error($result->errorOutput());
            Artisan::call('up');
            return 1;
        }
        $this->info('✓ Code updated successfully');

        // Step 4: Install/update dependencies (as web user)
        $this->warn('Step 4/7: Updating Composer dependencies...');
        $result = $this->runAsWebUser('composer install --no-dev --optimize-autoloader --no-interaction');
        
        if ($result->failed()) {
            $this->warn('⚠ Composer install had issues, check manually');
        } else {
            $this->info('✓ Composer dependencies updated');
        }

        // Step 5: Run migrations (as web user)
        $this->warn('Step 5/7: Running database migrations...');
        $this->runAsWebUser('php artisan migrate --force');
        $this->info('✓ Migrations completed');

        // Step 6: Build assets (as web user)
        $this->warn('Step 6/7: Building frontend assets...');
        $this->runAsWebUser('npm install');
        $result = $this->runAsWebUser('npm run build');
        
        if ($result->successful()) {
            $this->info('✓ Frontend assets built');
        } else {
            $this->warn('⚠ Asset build had issues, check manually');
        }

        // Step 7: Clear and optimize caches (as web user)
        $this->warn('Step 7/7: Optimizing application...');
        $this->runAsWebUser('php artisan optimize:clear');
        $this->runAsWebUser('php artisan config:cache');
        $this->runAsWebUser('php artisan route:cache');
        $this->runAsWebUser('php artisan view:cache');
        $this->info('✓ Application optimized');

        // Disable maintenance mode
        Artisan::call('up');
        
        // Sudoers refresh (already running as root)
        if ($this->option('sudoers')) {
            $this->info('');
            $this->warn('Refreshing sudoers configuration...');
            
            $script = base_path('scripts/install.sh');
            $result = Process::path(base_path())->run("bash {$script} --phase2");
            
            if ($result->successful()) {
                $this->info('✓ Sudoers configuration refreshed');
            } else {
                $this->warn('⚠ Sudoers refresh failed:');
                $this->line($result->errorOutput());
            }
        }

        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║     ✓ Hostiqo updated successfully!      ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        return 0;
    }
}
