<?php

use App\Http\Controllers\ActCompleteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardFolderController;
use App\Http\Controllers\DriveUploadController;
use App\Http\Controllers\DriveWebhookController;
use App\Http\Controllers\ExcelEntryController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\BasicAuthMiddleware;
use App\Http\Middleware\VerifyWebhookSecret;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::redirect('/business', '/business/login');

Route::middleware(VerifyWebhookSecret::class)->group(function () {
    Route::post('/webhook/airtable', [WebhookController::class, 'handle']);
    Route::post('/webhook/drive', [DriveWebhookController::class, 'handle']);
    Route::get('/webhook/act-statuses', [DriveWebhookController::class, 'actStatuses']);
    Route::post('/webhook/act-complete', [ActCompleteController::class, 'handle']);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(BasicAuthMiddleware::class)
    ->name('dashboard');

Route::post('/dashboard/acts/{recordId}/folder', [DashboardFolderController::class, 'store'])
    ->where('recordId', 'rec[A-Za-z0-9]+')
    ->middleware(BasicAuthMiddleware::class)
    ->name('dashboard.folder');

Route::get('/upload/folders', [DriveUploadController::class, 'folders'])
    ->middleware(BasicAuthMiddleware::class);
Route::post('/upload', [DriveUploadController::class, 'upload'])
    ->middleware(BasicAuthMiddleware::class);

Route::post('/dashboard/rescan', [DashboardController::class, 'rescan'])
    ->middleware(BasicAuthMiddleware::class)
    ->name('dashboard.rescan');

Route::post('/dashboard/download', [DashboardController::class, 'download'])
    ->middleware(BasicAuthMiddleware::class)
    ->name('dashboard.download');

Route::get('/dashboard/download/file', [DashboardController::class, 'downloadFile'])
    ->middleware(BasicAuthMiddleware::class)
    ->name('dashboard.download.file');

Route::post('/dashboard/acts/{recordId}/excel-entered', [ExcelEntryController::class, 'store'])
    ->where('recordId', 'rec[A-Za-z0-9]+')
    ->middleware(BasicAuthMiddleware::class)
    ->name('dashboard.excel-entered');
