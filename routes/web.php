<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SetupController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('admin.access:setup')->group(function () {
        Route::get('setup', [SetupController::class, 'create'])->name('setup');
        Route::post('setup', [SetupController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('setup.store');
    });

    Route::middleware('admin.access:unauthenticated')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['admin.access:authenticated', 'auth:admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    });
});
