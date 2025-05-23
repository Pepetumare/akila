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

// Ruta para cargar el modal con HTML (producto + ingredientes)
Route::get('/producto/modal/{id}', [ProductoController::class, 'modal'])
     ->name('producto.modal');

// (Opcional) Ruta JSON para API o debug; coméntala si no la usas
// Route::get('/producto/{id}', [ProductoController::class, 'show'])->name('producto.show');

// Carrito
Route::post('/cart/add',    [CartController::class, 'add'])->name('cart.add');
Route::get('/cart',         [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear',  [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');


// Checkout
Route::get('/checkout',                    [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout',                   [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/thank-you/{order}',  [CheckoutController::class, 'thankYou'])->name('checkout.thankyou');
Route::get('/boleta/{order}',              [CheckoutController::class, 'download'])->name('checkout.download');

// Autenticación
require __DIR__ . '/auth.php';

// Panel administrativo protegido (prefijo admin)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {

        // Dashboard Admin
        Route::get('dashboard', [DashboardController::class, 'index'])
             ->name('dashboard');

        // Categorías
        Route::resource('categorias', AdminCategoriaController::class);

        // Ingredientes
        Route::resource('ingredientes', AdminIngredienteController::class);

        // Productos – sin create/edit/show (todo en modal)
        Route::resource('productos', AdminProductoController::class)
             ->except(['create', 'edit', 'show']);

        // Pedidos
        Route::resource('orders', AdminOrderController::class)
             ->only(['index', 'show', 'update']);
    });

// Perfil de usuario común
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])
         ->name('profile');
});


Route::get('/pago/success/{order}',  'MercadoPagoController@success')->name('mp.success');
Route::get('/pago/failure/{order}',  'MercadoPagoController@failure')->name('mp.failure');
Route::get('/pago/pending/{order}',  'MercadoPagoController@pending')->name('mp.pending');
Route::post('/webhooks/mercadopago', 'MercadoPagoController@webhook')->name('mp.webhook');
