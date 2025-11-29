<?php

use App\Jobs\CheckSslCertificates;
use App\Jobs\RenewSslCertificates;
use App\Jobs\SystemMonitorJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhook/*',
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // System monitoring - configurable interval
        if (config('monitoring.enabled', true)) {
            $interval = config('monitoring.interval_minutes', 2);
            $schedule->job(new SystemMonitorJob())
                ->cron("*/{$interval} * * * *")
                ->name('system-monitor')
                ->withoutOverlapping();
        }

        // SSL certificate renewal - runs daily at 2:30 AM
        $schedule->job(new RenewSslCertificates())
            ->dailyAt('02:30')
            ->name('ssl-renewal')
            ->withoutOverlapping();

        // SSL certificate check - runs daily at 3:00 AM (after renewal)
        $schedule->job(new CheckSslCertificates())
            ->dailyAt('03:00')
            ->name('ssl-check')
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
