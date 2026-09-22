<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Kitchen\KitchenController;
use App\Http\Controllers\Public\MenuController;
use App\Http\Controllers\Waiter\OrderController;
use Illuminate\Support\Facades\Route;

/**
 * Public (client) area.
 *
 * The client scans a QR code that points to /menu/{code}, which identifies
 * the table and shows the digital menu without requiring any authentication.
 */
Route::get('/menu/{code}', [MenuController::class, 'show'])
    ->name('menu.show');

/**
 * Internal (staff) areas.
 *
 * These panels require authentication and are prepared for role-based
 * authorization. Login is not part of the current scope yet.
 */
Route::prefix('waiter')->middleware('auth')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('waiter.dashboard');
});

Route::prefix('kitchen')->middleware('auth')->group(function () {
    Route::get('/', [KitchenController::class, 'index'])->name('kitchen.dashboard');
});

Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
});
