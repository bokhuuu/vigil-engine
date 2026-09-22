<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriveWebhookController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\BasicAuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/airtable', [WebhookController::class, 'handle']);
Route::post('/webhook/drive', [DriveWebhookController::class, 'handle']);
Route::get('/webhook/act-status', [DriveWebhookController::class, 'actStatus']);

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(BasicAuthMiddleware::class);
