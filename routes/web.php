<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DishController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Kitchen\KitchenController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\MenuController as PublicMenuController;
use App\Http\Controllers\Public\QrImageController;
use App\Http\Controllers\Waiter\MenuController;
use App\Http\Controllers\Waiter\OrderController;
use Illuminate\Support\Facades\Route;

/**
 * Public (client) area.
 *
 * The client scans a QR code that points to /menu/{code}, which identifies
 * the table and shows the digital menu without requiring any authentication.
 * The home page offers two paths: clients reach the menu (scanning a QR code),
 * while staff are redirected to their dashboard if already authenticated.
 */
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/menu/{code}', [PublicMenuController::class, 'show'])
    ->name('menu.show');

/**
 * QR code images.
 *
 * /qr/menu/{code} renders the QR that opens the public menu for a table, and
 * /qr/personal renders the QR that opens the staff login page.
 */
Route::get('/qr/menu/{code}', [QrImageController::class, 'menu']);
Route::get('/qr/personal', [QrImageController::class, 'personal']);

/**
 * Staff authentication.
 */
Route::get('/login', [LoginController::class, 'create'])
    ->name('login');

Route::post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:login')
    ->name('login.store');

Route::post('/logout', [LoginController::class, 'destroy'])
    ->name('logout');

/**
 * Internal (staff) areas.
 *
 * These panels require authentication and are restricted by role. A user who
 * reaches a panel of another role is redirected to their own dashboard.
 */
Route::prefix('waiter')->middleware(['auth', 'role:mozo'])->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('waiter.dashboard');
    Route::get('/board', [OrderController::class, 'board'])->name('waiter.board');
    Route::get('/menu', [MenuController::class, 'index'])->name('waiter.menu');
    Route::get('/pedidos/create', [OrderController::class, 'create'])->name('waiter.orders.create');
    Route::post('/pedidos', [OrderController::class, 'store'])->name('waiter.orders.store');
    Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('waiter.orders.show');
    Route::get('/pedidos/{order}/reenviar', [OrderController::class, 'resendForm'])->name('waiter.orders.resend');
    Route::patch('/pedidos/{order}/reenviar', [OrderController::class, 'resend'])->name('waiter.orders.resend.update');
    Route::patch('/pedidos/{order}/estado', [OrderController::class, 'updateStatus'])->name('waiter.orders.status');
});

Route::prefix('kitchen')->middleware(['auth', 'role:cocina'])->group(function () {
    Route::get('/', [KitchenController::class, 'index'])->name('kitchen.dashboard');
    Route::get('/board', [KitchenController::class, 'board'])->name('kitchen.board');
    Route::get('/pedidos/{order}', [KitchenController::class, 'show'])->name('kitchen.orders.show');
    Route::patch('/pedidos/{order}/estado', [KitchenController::class, 'updateStatus'])->name('kitchen.orders.status');
});

Route::prefix('admin')->middleware(['auth', 'role:administrador'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');

    Route::resource('categorias', CategoryController::class)
        ->parameters(['categorias' => 'category'])
        ->except('show')
        ->names('admin.categories');

    Route::resource('platos', DishController::class)
        ->parameters(['platos' => 'dish'])
        ->except('show')
        ->names('admin.dishes');
});
