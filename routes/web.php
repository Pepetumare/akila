<?php

use App\Http\Controllers\Admin\CategoriaController as AdminCategoriaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IngredienteController as AdminIngredienteController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductoController as AdminProductoController;
use App\Http\Controllers\CartController;
// Admin namespaced controllers
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ───────────────────────────────────────────────────────────────
// Public
// ───────────────────────────────────────────────────────────────

Route::view('/', 'home')->name('home');

Route::get('/menu', [ProductoController::class, 'index'])
    ->name('menu');

Route::get('/producto/modal/{id}', [ProductoController::class, 'modal'])
    ->name('producto.modal');

// ───────────────────────────────────────────────────────────────
// Cart
// ───────────────────────────────────────────────────────────────

Route::prefix('cart')
    ->name('cart.')
    ->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('add', [CartController::class, 'add'])->name('add');
        Route::post('quote', [CartController::class, 'quote'])->middleware('throttle:60,1')->name('quote');
        Route::post('update', [CartController::class, 'update'])->name('update');
        Route::post('remove', [CartController::class, 'remove'])->name('remove');
        Route::post('clear', [CartController::class, 'clear'])->name('clear');
    });

// ───────────────────────────────────────────────────────────────
// Checkout + Mercado Pago callbacks
// ───────────────────────────────────────────────────────────────

Route::prefix('checkout')
    ->name('checkout.')
    ->group(function () {
        // show summary, store order, download PDF
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/', [CheckoutController::class, 'store'])->block(30, 30)->name('store');
        Route::get('thank-you/{order}', [CheckoutController::class, 'thankYou'])->name('thankyou');
        Route::get('boleta/{order}', [CheckoutController::class, 'download'])->name('download');

        // Mercado Pago redirects
        Route::get('success', [MercadoPagoController::class, 'success'])
            ->name('success');
        Route::get('failure', [MercadoPagoController::class, 'failure'])
            ->name('failure');
        Route::get('pending', [MercadoPagoController::class, 'pending'])
            ->name('pending');

        // Webhook (no CSRF)
        Route::post('webhook', [MercadoPagoController::class, 'webhook'])
            ->name('webhook');
    });

// ───────────────────────────────────────────────────────────────
// Authentication (login, register, etc.)
// ───────────────────────────────────────────────────────────────

require __DIR__.'/auth.php';

// ───────────────────────────────────────────────────────────────
// Admin panel (auth + admin middleware)
// ───────────────────────────────────────────────────────────────

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::resource('categorias', AdminCategoriaController::class);
        Route::resource('ingredientes', AdminIngredienteController::class);
        Route::resource('productos', AdminProductoController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('orders', AdminOrderController::class)
            ->only(['index', 'show', 'update']);
        Route::get('orders/{order}/boleta', [AdminOrderController::class, 'download'])->name('orders.download');
    });

// ───────────────────────────────────────────────────────────────
// User profile
// ───────────────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('profile/pedidos', [ProfileController::class, 'orders'])->name('profile.orders');
});
