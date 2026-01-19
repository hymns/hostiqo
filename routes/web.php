<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\ArtisanController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CloudflareController;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeploymentController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\FirewallController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ServerHealthController;
use App\Http\Controllers\ServiceManagerController;
use App\Http\Controllers\SupervisorProgramController;
use App\Http\Controllers\SystemdController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WebhookHandlerController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WordPressDeploymentController;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

// Public Health Check Endpoints (Rate Limited)
Route::get('ping', [HealthCheckController::class, 'ping'])->name('ping')->middleware('throttle:30,1');
Route::get('health', [HealthCheckController::class, 'health'])->name('health')->middleware('throttle:10,1');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes (Require Authentication)
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Server Health
    Route::get('server-health', [ServerHealthController::class, 'index'])->name('server-health');

    // Webhooks Management
    Route::resource('webhooks', WebhookController::class);
    Route::post('webhooks/{webhook}/generate-ssh-key', [WebhookController::class, 'generateSshKey'])
        ->name('webhooks.generate-ssh-key');
    Route::post('webhooks/{webhook}/toggle', [WebhookController::class, 'toggle'])
        ->name('webhooks.toggle');

    // Deployments
    Route::get('deployments', [DeploymentController::class, 'index'])->name('deployments.index');
    Route::get('deployments/{deployment}', [DeploymentController::class, 'show'])->name('deployments.show');
    Route::post('webhooks/{webhook}/deploy', [DeploymentController::class, 'trigger'])
        ->name('deployments.trigger');

    // Database Management
    Route::get('databases-recheck-permissions', [DatabaseController::class, 'recheckPermissions'])
        ->name('databases.recheck-permissions');
    Route::resource('databases', DatabaseController::class);
    Route::get('databases/{database}/change-password', [DatabaseController::class, 'showChangePasswordForm'])
        ->name('databases.change-password');
    Route::put('databases/{database}/change-password', [DatabaseController::class, 'changePassword'])
        ->name('databases.update-password');

    // Queue Management
    Route::get('queues', [QueueController::class, 'index'])->name('queues.index');
    Route::post('queues/dispatch-test', [QueueController::class, 'dispatchTest'])->name('queues.dispatch-test');
    Route::get('queues/pending', [QueueController::class, 'pending'])->name('queues.pending');
    Route::get('queues/failed', [QueueController::class, 'failed'])->name('queues.failed');
    Route::get('queues/job/{id}', [QueueController::class, 'showJob'])->name('queues.show-job');
    Route::get('queues/failed-job/{uuid}', [QueueController::class, 'showFailedJob'])->name('queues.show-failed-job');
    Route::delete('queues/job/{id}', [QueueController::class, 'deleteJob'])->name('queues.delete-job');
    Route::delete('queues/failed-job/{uuid}', [QueueController::class, 'deleteFailedJob'])->name('queues.delete-failed-job');
    Route::post('queues/failed-job/{uuid}/retry', [QueueController::class, 'retryFailedJob'])->name('queues.retry-failed-job');
    Route::post('queues/retry-all-failed', [QueueController::class, 'retryAllFailed'])->name('queues.retry-all-failed');
    Route::delete('queues/clear-failed', [QueueController::class, 'clearFailed'])->name('queues.clear-failed');

    // Website Management (Virtual Hosts)
    Route::resource('websites', WebsiteController::class);
    Route::post('websites/{website}/toggle-ssl', [WebsiteController::class, 'toggleSsl'])
        ->name('websites.toggle-ssl');
    Route::post('websites/{website}/redeploy', [WebsiteController::class, 'redeploy'])
        ->name('websites.redeploy');
    
    // PM2 Process Control (Node.js)
    Route::post('websites/{website}/pm2-start', [WebsiteController::class, 'pm2Start'])
        ->name('websites.pm2-start');
    Route::post('websites/{website}/pm2-stop', [WebsiteController::class, 'pm2Stop'])
        ->name('websites.pm2-stop');
    Route::post('websites/{website}/pm2-restart', [WebsiteController::class, 'pm2Restart'])
        ->name('websites.pm2-restart');
    
    // 1-Click App Deployment (Dynamic - supports WordPress, Drupal, etc)
    // Legacy WordPress routes (kept for backwards compatibility)
    Route::get('websites/{website}/wordpress', [WordPressDeploymentController::class, 'show'])
        ->name('websites.wordpress.show');
    Route::post('websites/{website}/wordpress/install', [WordPressDeploymentController::class, 'install'])
        ->name('websites.wordpress.install');
    Route::get('websites/{website}/wordpress/status', [WordPressDeploymentController::class, 'checkStatus'])
        ->name('websites.wordpress.status');
    
    // Future: Dynamic app deployment routes
    // Route::post('websites/{website}/apps/{app}/install', [AppDeploymentController::class, 'install'])
    //     ->name('websites.apps.install')->where('app', 'wordpress|drupal|laravel|symfony');
    
    // Cloudflare DNS Management
    Route::post('websites/{website}/dns-sync', [CloudflareController::class, 'sync'])
        ->name('websites.dns-sync');
    Route::delete('websites/{website}/dns-remove', [CloudflareController::class, 'remove'])
        ->name('websites.dns-remove');
    Route::get('cloudflare/verify-token', [CloudflareController::class, 'verifyToken'])
        ->name('cloudflare.verify-token');
    Route::get('cloudflare/server-ip', [CloudflareController::class, 'getServerIp'])
        ->name('cloudflare.server-ip');

    // Firewall Management
    Route::get('firewall', [FirewallController::class, 'index'])->name('firewall.index');
    Route::post('firewall', [FirewallController::class, 'store'])->name('firewall.store');
    Route::delete('firewall/{firewallRule}', [FirewallController::class, 'destroy'])->name('firewall.destroy');
    Route::post('firewall/{firewallRule}/toggle', [FirewallController::class, 'toggle'])->name('firewall.toggle');
    Route::post('firewall/enable', [FirewallController::class, 'enable'])->name('firewall.enable');
    Route::post('firewall/disable', [FirewallController::class, 'disable'])->name('firewall.disable');
    Route::post('firewall/reset', [FirewallController::class, 'reset'])->name('firewall.reset');

    // Cron Jobs
    Route::resource('cron-jobs', CronJobController::class);
    Route::post('cron-jobs/{cronJob}/toggle', [CronJobController::class, 'toggle'])->name('cron-jobs.toggle');

    // Supervisor Programs
    Route::resource('supervisor', SupervisorProgramController::class)->parameters([
        'supervisor' => 'supervisorProgram'
    ]);
    Route::post('supervisor/{supervisorProgram}/start', [SupervisorProgramController::class, 'start'])->name('supervisor.start');
    Route::post('supervisor/{supervisorProgram}/stop', [SupervisorProgramController::class, 'stop'])->name('supervisor.stop');
    Route::post('supervisor/{supervisorProgram}/restart', [SupervisorProgramController::class, 'restart'])->name('supervisor.restart');
    Route::post('supervisor/{supervisorProgram}/deploy', [SupervisorProgramController::class, 'deploy'])->name('supervisor.deploy');

    // Systemd Services
    Route::resource('systemd', SystemdController::class);
    Route::post('systemd/{systemd}/start', [SystemdController::class, 'start'])->name('systemd.start');
    Route::post('systemd/{systemd}/stop', [SystemdController::class, 'stop'])->name('systemd.stop');
    Route::post('systemd/{systemd}/restart', [SystemdController::class, 'restart'])->name('systemd.restart');

    // Alerts
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('alerts/create', [AlertController::class, 'create'])->name('alerts.create');
    Route::post('alerts', [AlertController::class, 'store'])->name('alerts.store');
    Route::get('alerts/{alertRule}/edit', [AlertController::class, 'edit'])->name('alerts.edit');
    Route::put('alerts/{alertRule}', [AlertController::class, 'update'])->name('alerts.update');
    Route::delete('alerts/{alertRule}', [AlertController::class, 'destroy'])->name('alerts.destroy');
    Route::post('alerts/{alertRule}/toggle', [AlertController::class, 'toggle'])->name('alerts.toggle');
    Route::post('alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::delete('alerts/{alert}/delete', [AlertController::class, 'deleteAlert'])->name('alerts.delete');

    // Log Viewer
    Route::get('logs', [LogViewerController::class, 'index'])->name('logs.index');
    Route::post('logs/clear', [LogViewerController::class, 'clear'])->name('logs.clear');

    // File Manager
    Route::get('files', [FileManagerController::class, 'index'])->name('files.index');
    Route::get('files/edit', [FileManagerController::class, 'edit'])->name('files.edit');
    Route::post('files/update', [FileManagerController::class, 'update'])->name('files.update');
    Route::post('files/delete', [FileManagerController::class, 'destroy'])->name('files.delete');
    Route::post('files/create-directory', [FileManagerController::class, 'createDirectory'])->name('files.create-directory');
    Route::post('files/create-file', [FileManagerController::class, 'createFile'])->name('files.create-file');
    Route::post('files/rename', [FileManagerController::class, 'rename'])->name('files.rename');
    Route::post('files/chmod', [FileManagerController::class, 'chmod'])->name('files.chmod');
    Route::post('files/upload', [FileManagerController::class, 'upload'])->name('files.upload');
    Route::get('files/download', [FileManagerController::class, 'download'])->name('files.download');

    // Service Manager
    Route::get('services', [ServiceManagerController::class, 'index'])->name('services.index');
    Route::get('services/status', [ServiceManagerController::class, 'status'])->name('services.status');
    Route::post('services/start', [ServiceManagerController::class, 'start'])->name('services.start');
    Route::post('services/stop', [ServiceManagerController::class, 'stop'])->name('services.stop');
    Route::post('services/restart', [ServiceManagerController::class, 'restart'])->name('services.restart');
    Route::post('services/reload', [ServiceManagerController::class, 'reload'])->name('services.reload');
    Route::get('services/logs', [ServiceManagerController::class, 'logs'])->name('services.logs');

    // Artisan Commands
    Route::get('artisan', [ArtisanController::class, 'index'])->name('artisan.index');
    Route::post('artisan/optimize', [ArtisanController::class, 'optimize'])->name('artisan.optimize');
    Route::post('artisan/cache-clear', [ArtisanController::class, 'cacheClear'])->name('artisan.cache-clear');
    Route::post('artisan/config-clear', [ArtisanController::class, 'configClear'])->name('artisan.config-clear');
    Route::post('artisan/config-cache', [ArtisanController::class, 'configCache'])->name('artisan.config-cache');
    Route::post('artisan/route-clear', [ArtisanController::class, 'routeClear'])->name('artisan.route-clear');
    Route::post('artisan/route-cache', [ArtisanController::class, 'routeCache'])->name('artisan.route-cache');
    Route::post('artisan/view-clear', [ArtisanController::class, 'viewClear'])->name('artisan.view-clear');
    Route::post('artisan/view-cache', [ArtisanController::class, 'viewCache'])->name('artisan.view-cache');
    Route::post('artisan/clear-all', [ArtisanController::class, 'clearAll'])->name('artisan.clear-all');
    Route::post('artisan/optimize-production', [ArtisanController::class, 'optimizeProduction'])->name('artisan.optimize-production');
});

// Webhook Handler (API endpoint for Git providers - No Auth Required)
Route::post('webhook/{webhook}/{token}', [WebhookHandlerController::class, 'handle'])
    ->name('webhook.handle');
