<?php

use App\Http\Controllers\ActCompleteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriveWebhookController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\BasicAuthMiddleware;
use App\Http\Middleware\VerifyWebhookSecret;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyWebhookSecret::class)->group(function () {
    Route::post('/webhook/airtable', [WebhookController::class, 'handle']);
    Route::post('/webhook/drive', [DriveWebhookController::class, 'handle']);
    Route::get('/webhook/act-statuses', [DriveWebhookController::class, 'actStatuses']);
    Route::post('/webhook/act-complete', [ActCompleteController::class, 'handle']);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(BasicAuthMiddleware::class);
