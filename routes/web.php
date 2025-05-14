<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoriaController   as AdminCategoriaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IngredienteController as AdminIngredienteController;
use App\Http\Controllers\Admin\ProductoController    as AdminProductoController;
use App\Http\Controllers\Admin\OrderController       as AdminOrderController;
use App\Http\Controllers\ProfileController;

// Rutas públicas
Route::get('/', fn() => view('home'))->name('home');

Route::get('/menu', [ProductoController::class, 'index'])->name('menu');

Route::get('/producto/{id}', [ProductoController::class, 'show'])->name('producto.show');

// Carrito
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Checkout
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/thank-you/{order}', [CheckoutController::class, 'thankYou'])->name('checkout.thankyou');
Route::get('/boleta/{order}', [CheckoutController::class, 'download'])->name('checkout.download');

// Autenticación
require __DIR__ . '/auth.php';

// Panel administrativo protegido (Prefijo admin)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {

        // Dashboard Admin
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Categorías
        Route::resource('categorias', AdminCategoriaController::class);

        // Ingredientes
        Route::resource('ingredientes', AdminIngredienteController::class);

        // Productos (Aquí está tu controlador AdminProductoController)
        Route::resource('productos', AdminProductoController::class);

        // Pedidos
        Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
    });

// Perfil usuario común
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
});
