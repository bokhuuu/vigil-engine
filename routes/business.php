<?php

declare(strict_types=1);

use App\Business\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('business')->name('business.')->group(function () {

    Route::middleware('guest:business')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:business')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});
