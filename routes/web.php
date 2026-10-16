<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DishController;
use App\Http\Controllers\Admin\IngredientController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Kitchen\KitchenController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\MenuController as PublicMenuController;
use App\Http\Controllers\Public\QrImageController;
use App\Http\Controllers\Waiter\MenuController;
use App\Http\Controllers\Waiter\OrderController;
use App\Http\Controllers\Waiter\QrCodeController;
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
 * Dish detail.
 *
 * Reached from the "Ver plato" link on a dish card. It stays inside the same
 * QR code so the link only resolves for the table that opened the menu, and an
 * unavailable dish is a 404 for the same reason it is hidden from the menu.
 */
Route::get('/menu/{code}/plato/{dish}', [PublicMenuController::class, 'showDish'])
    ->name('menu.dish.show');

/**
 * QR code images.
 *
 * /qr/menu/{code} renders the QR that opens the public menu for a table, and
 * /qr/personal renders the QR that opens the staff login page.
 */
Route::get('/qr/menu/{code}', [QrImageController::class, 'menu'])->name('qr.menu');
Route::get('/qr/personal', [QrImageController::class, 'personal'])->name('qr.personal');

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

    /*
     * The scanner posts the value the camera decoded and gets back the table it
     * belongs to. It lives inside the waiter group, so a client that only read a
     * public menu QR cannot reach it and a non-waiter account is turned away by
     * the same middleware that guards the rest of the panel.
     */
    Route::post('/qr/resolver', [QrCodeController::class, 'resolve'])->name('waiter.qr.resolve');
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

    /*
     * The 3D model is one row per dish, so it is uploaded and replaced from the
     * dish form and only removed on its own route.
     */
    Route::delete('platos/{dish}/modelo', [DishController::class, 'destroyModel'])
        ->name('admin.dishes.model.destroy');

    Route::resource('ingredientes', IngredientController::class)
        ->parameters(['ingredientes' => 'ingredient'])
        ->except('show')
        ->names('admin.ingredients');

    Route::resource('mesas', TableController::class)
        ->parameters(['mesas' => 'table'])
        ->except('show')
        ->names('admin.tables');

    // A table always keeps its QR code; a new code is minted on demand so the
    // printed sticker can be replaced without deleting the table.
    Route::post('mesas/{table}/qr', [TableController::class, 'regenerateQr'])
        ->name('admin.tables.qr.regenerate');

    /*
     * Orders are read-only here. The waiter and the kitchen own the flow, and
     * letting an administrator move an order would put a third pair of hands on
     * a state machine the other two panels already share.
     */
    Route::get('pedidos', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('pedidos/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');

    /*
     * Accounts are deactivated, never deleted: an order has to keep pointing at
     * the person who took it, so there is no destroy route on purpose.
     */
    Route::resource('usuarios', UserController::class)
        ->parameters(['usuarios' => 'user'])
        ->except(['show', 'destroy'])
        ->names('admin.users');
});
