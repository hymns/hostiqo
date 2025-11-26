<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeploymentController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WebhookHandlerController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes (Require Authentication)
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

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
});

// Webhook Handler (API endpoint for Git providers - No Auth Required)
Route::post('webhook/{webhook}/{token}', [WebhookHandlerController::class, 'handle'])
    ->name('webhook.handle');
