<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeploymentController;
use App\Http\Controllers\QueueController;
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
});

// Webhook Handler (API endpoint for Git providers - No Auth Required)
Route::post('webhook/{webhook}/{token}', [WebhookHandlerController::class, 'handle'])
    ->name('webhook.handle');
