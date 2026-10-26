<?php

declare(strict_types=1);

use App\Business\Http\Controllers\AuthController;
use App\Business\Http\Controllers\BusinessController;
use App\Business\Http\Controllers\BusinessCreateController;
use App\Business\Http\Controllers\BusinessMoveController;
use App\Business\Http\Controllers\BusinessUploadController;
use App\Business\Http\Controllers\CorrespondenceController;
use App\Business\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('business')->name('business.')->group(function () {

    Route::middleware('guest:business')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    });

    Route::middleware('auth:business')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/create', [BusinessCreateController::class, 'create'])->name('create');
        Route::post('/create', [BusinessCreateController::class, 'store'])->name('store');
        Route::get('/upload/folders', [BusinessUploadController::class, 'folders'])->name('upload.folders');
        Route::post('/upload', [BusinessUploadController::class, 'upload'])->name('upload.store');
        Route::get('/{business}', [BusinessController::class, 'show'])->name('show');
        Route::put('/{business}', [BusinessController::class, 'update'])->name('update');
        Route::delete('/{business}', [BusinessController::class, 'destroy'])->name('destroy');
        Route::post('/{business}/drive', [BusinessController::class, 'createDriveFolder'])->name('drive.create');
        Route::post('/{business}/correspondence', [CorrespondenceController::class, 'store'])->name('correspondence.store');
        Route::patch('/{business}/correspondence/{correspondence}/received', [CorrespondenceController::class, 'markReceived'])->name('correspondence.received');
        Route::put('/{business}/correspondence/{correspondence}', [CorrespondenceController::class, 'update'])->name('correspondence.update');
        Route::delete('/{business}/correspondence/{correspondence}', [CorrespondenceController::class, 'destroy'])->name('correspondence.destroy');

        Route::get('/{business}/move-files', [BusinessMoveController::class, 'files'])->name('move.files');
        Route::post('/{business}/move-files', [BusinessMoveController::class, 'move'])->name('move.store');
    });
});
