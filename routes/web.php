<?php

use App\Http\Controllers\ActCompleteController;
use App\Http\Controllers\ActsLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardFolderController;
use App\Http\Controllers\DriveUploadController;
use App\Http\Controllers\DriveWebhookController;
use App\Http\Controllers\ExcelEntryController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\ActsAuthMiddleware;
use App\Http\Middleware\VerifyWebhookSecret;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::redirect('/business', '/business/login');

// Acts dashboard login / logout (no auth middleware)
Route::get('/dashboard/login', [ActsLoginController::class, 'showLogin'])->name('acts.login');
Route::post('/dashboard/login', [ActsLoginController::class, 'login']);
Route::post('/dashboard/logout', [ActsLoginController::class, 'logout'])->name('acts.logout');

Route::middleware(VerifyWebhookSecret::class)->group(function () {
    Route::post('/webhook/airtable', [WebhookController::class, 'handle']);
    Route::post('/webhook/drive', [DriveWebhookController::class, 'handle']);
    Route::get('/webhook/act-statuses', [DriveWebhookController::class, 'actStatuses']);
    Route::post('/webhook/act-complete', [ActCompleteController::class, 'handle']);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard');

Route::post('/dashboard/acts/{recordId}/folder', [DashboardFolderController::class, 'store'])
    ->where('recordId', 'rec[A-Za-z0-9]+')
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard.folder');

Route::get('/upload/folders', [DriveUploadController::class, 'folders'])
    ->middleware(ActsAuthMiddleware::class);
Route::post('/upload', [DriveUploadController::class, 'upload'])
    ->middleware(ActsAuthMiddleware::class);

Route::post('/dashboard/rescan', [DashboardController::class, 'rescan'])
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard.rescan');

Route::post('/dashboard/download', [DashboardController::class, 'download'])
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard.download');

Route::get('/dashboard/download/file', [DashboardController::class, 'downloadFile'])
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard.download.file');

Route::post('/dashboard/download/photos', [DashboardController::class, 'downloadPhotos'])
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard.download.photos');

Route::get('/dashboard/download/photos/file', [DashboardController::class, 'downloadPhotosFile'])
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard.download.photos.file');

Route::get('/dashboard/download/status', [DashboardController::class, 'downloadStatus'])
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard.download.status');

Route::delete('/dashboard/download/cancel', [DashboardController::class, 'downloadCancel'])
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard.download.cancel');

Route::post('/dashboard/acts/{recordId}/excel-entered', [ExcelEntryController::class, 'store'])
    ->where('recordId', 'rec[A-Za-z0-9]+')
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard.excel-entered');

Route::post('/dashboard/upload/scans', [DashboardController::class, 'uploadScans'])
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard.upload.scans');

Route::get('/dashboard/upload/status', [DashboardController::class, 'uploadStatus'])
    ->middleware(ActsAuthMiddleware::class)
    ->name('dashboard.upload.status');
