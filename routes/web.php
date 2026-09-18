<?php

use App\Http\Controllers\DriveWebhookController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/airtable', [WebhookController::class, 'handle']);
Route::post('/webhook/drive', [DriveWebhookController::class, 'handle']);
