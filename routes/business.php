<?php

declare(strict_types=1);

use App\Business\Http\Controllers\AuthController;
use App\Business\Http\Controllers\BusinessController;
use App\Business\Http\Controllers\BusinessCreateController;
use App\Business\Http\Controllers\CorrespondenceController;
use App\Business\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('business')->name('business.')->group(function () {

    Route::middleware('guest:business')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:business')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/create', [BusinessCreateController::class, 'create'])->name('create');
        Route::post('/create', [BusinessCreateController::class, 'store'])->name('store');
        Route::get('/{business}', [BusinessController::class, 'show'])->name('show');
        Route::put('/{business}', [BusinessController::class, 'update'])->name('update');
        Route::delete('/{business}', [BusinessController::class, 'destroy'])->name('destroy');
        Route::post('/{business}/correspondence', [CorrespondenceController::class, 'store'])->name('correspondence.store');
        Route::patch('/{business}/correspondence/{correspondence}/received', [CorrespondenceController::class, 'markReceived'])->name('correspondence.received');
        Route::put('/{business}/correspondence/{correspondence}', [CorrespondenceController::class, 'update'])->name('correspondence.update');
        Route::delete('/{business}/correspondence/{correspondence}', [CorrespondenceController::class, 'destroy'])->name('correspondence.destroy');
    });
});
