<?php

use App\Models\Categoria;
use App\Models\Ingrediente;
use App\Models\Order;
use App\Models\Producto;
use App\Models\User;
use App\Services\MercadoPagoService;
use App\Services\OrderAccessService;
use App\Services\ProductPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Net\MPHttpClient;
use MercadoPago\Net\MPResponse;

function akilaProduct(bool $custom = true): Producto
{
    $category = Categoria::create(['nombre' => 'Sushi']);
    $product = Producto::create(['nombre' => 'Roll', 'descripcion' => 'Sushi', 'precio' => 5000,
        'categoria_id' => $category->id, 'personalizable' => $custom, 'unidades' => 10]);
    $wrapper = Ingrediente::create(['nombre' => 'Nori', 'tipo' => 'envoltura']);
    $protein = Ingrediente::create(['nombre' => 'Pollo', 'tipo' => 'proteina']);
    $vegetable = Ingrediente::create(['nombre' => 'Palta', 'tipo' => 'vegetal']);
    $product->ingredientes()->sync([$wrapper->id => ['cantidad_permitida' => 1],
        $protein->id => ['cantidad_permitida' => 1], $vegetable->id => ['cantidad_permitida' => 1]]);

    return $product;
}

function akilaOrder(?User $user = null): Order
{
    $order = Order::create(['cliente_nombre' => 'Cliente', 'cliente_telefono' => '912345678',
        'metodo_entrega' => 'pickup', 'subtotal' => 5000, 'delivery_cost' => 0, 'total' => 5000,
        'status' => 'pendiente', 'payment_status' => 'pending']);
    if ($user) {
        $order->user()->associate($user)->save();
    }

    return $order;
}

function akilaPayment(Order $order, array $changes = []): array
{
    return array_replace(['id' => 12345, 'external_reference' => (string) $order->id, 'status' => 'approved',
        'transaction_amount' => 5000, 'currency_id' => 'CLP', 'date_last_updated' => now()->toIso8601String()], $changes);
}

function akilaWebhookHeaders(string $id = '12345'): array
{
    $ts = (string) time();
    $manifest = "id:{$id};request-id:test-request;ts:{$ts};";

    return ['x-request-id' => 'test-request', 'x-signature' => 'ts='.$ts.',v1='.hash_hmac('sha256', $manifest, 'test-secret')];
}

beforeEach(function () {
    config(['services.mercadopago.webhook_secret' => 'test-secret']);
    // The SDK is never called in this suite, including unexpected checkout paths.
    $this->gateway = Mockery::mock(MercadoPagoService::class);
    $this->app->instance(MercadoPagoService::class, $this->gateway);
});

test('base price and quantity are calculated on server', function () {
    $quote = app(ProductPricingService::class)->quote(akilaProduct(), [], 2);
    expect($quote['precio_unit'])->toEqual(5000)->and($quote['total'])->toEqual(10000)->and($quote['recargo'])->toBe(0);
});

test('valid substitution is charged independently of client supplied prices', function () {
    $product = akilaProduct();
    $salmon = Ingrediente::create(['nombre' => 'Salmón', 'tipo' => 'proteina']);
    $quote = app(ProductPricingService::class)->quote($product, ['Proteínas' => [$salmon->id],
        'price' => 1, 'price_adjustment' => 0, 'total' => 1]);
    expect($quote['recargo'])->toBe(1000)->and($quote['precio_unit'])->toEqual(6000);
});

test('returning to original ingredients has no surcharge', function () {
    $quote = app(ProductPricingService::class)->quote(akilaProduct(), ['price_adjustment' => 999999]);
    expect($quote['recargo'])->toBe(0)->and($quote['precio_unit'])->toEqual(5000);
});

test('invalid or wrong type ingredient is rejected', function ($kind) {
    $product = akilaProduct();
    $id = $kind === 'missing' ? 99999 : Ingrediente::where('tipo', 'vegetal')->first()->id;
    expect(fn () => app(ProductPricingService::class)->quote($product, ['Proteínas' => [$id]]))->toThrow(ValidationException::class);
})->with(['missing', 'wrong_type']);

test('explicit swappable options restrict combinations', function () {
    $product = akilaProduct();
    $allowed = Ingrediente::create(['nombre' => 'Camarón', 'tipo' => 'proteina']);
    $other = Ingrediente::create(['nombre' => 'Salmón', 'tipo' => 'proteina']);
    $product->swappables()->attach($allowed, ['tipo' => 'proteina']);
    expect(fn () => app(ProductPricingService::class)->quote($product, ['Proteínas' => [$other->id]]))->toThrow(ValidationException::class);
});

test('ingredient count and wrapper cannot be forged', function ($input) {
    expect(fn () => app(ProductPricingService::class)->quote(akilaProduct(), $input))->toThrow(ValidationException::class);
})->with(['missing proteins' => [['Proteínas' => []]], 'invalid wrapper' => [['envoltura_id' => 9999]], 'conflicting aliases' => [['base_id' => 1, 'envoltura_id' => 2]]]);

test('non customizable product rejects exclusions', function () {
    expect(fn () => app(ProductPricingService::class)->quote(akilaProduct(false), ['cream_cheese' => true]))->toThrow(ValidationException::class);
});

test('legacy cut quantities are normalized to rolls', function () {
    $product = akilaProduct();
    $protein = Ingrediente::where('tipo', 'proteina')->first();
    $product->ingredientes()->updateExistingPivot($protein->id, ['cantidad_permitida' => 20]);
    $defaults = app(ProductPricingService::class)->defaults($product);
    expect($defaults['Proteínas'])->toBe([$protein->id, $protein->id]);
});

test('adding cart lines returns unit count and merges configurations', function () {
    $product = akilaProduct();
    $this->postJson('/cart/add', ['product_id' => $product->id, 'unidades' => 2])->assertOk()->assertJsonPath('cart_count', 2);
    $this->postJson('/cart/add', ['product_id' => $product->id, 'unidades' => 3])->assertOk()->assertJsonPath('cart_count', 5);
    expect(session('cart'))->toHaveCount(1);
    $this->get('/cart')->assertOk();
});

test('cart preserves normalized personalization and backend price', function () {
    $product = akilaProduct();
    $salmon = Ingrediente::create(['nombre' => 'Salmón', 'tipo' => 'proteina']);
    $this->postJson('/cart/add', ['product_id' => $product->id, 'unidades' => 1,
        'Proteínas' => [$salmon->id], 'cream_cheese' => true, 'price_adjustment' => -9999])->assertOk();
    $line = array_values(session('cart'))[0];
    expect($line['precio_unit'])->toEqual(6000)->and($line['configuration']['cream_cheese'])->toBeTrue();
});

test('cart rejects invalid quantities', function ($quantity) {
    $product = akilaProduct();
    $this->postJson('/cart/add', ['product_id' => $product->id, 'unidades' => $quantity])->assertUnprocessable();
})->with([-1, 0, 51, 1000000]);

test('cart update recalculates current catalog price', function () {
    $product = akilaProduct();
    $line = app(ProductPricingService::class)->quote($product, []);
    $product->update(['precio' => 7000]);
    $this->withSession(['cart' => [$line['hash'] => $line]])->post('/cart/update', ['hash' => $line['hash'], 'unidades' => 2])->assertRedirect();
    expect(session('cart')[$line['hash']]['total'])->toEqual(14000);
});

test('product menu and modal render with shared configuration', function () {
    $product = akilaProduct();
    $this->get('/menu')->assertOk()->assertSee('productModal-'.$product->id);
    $this->get('/producto/modal/'.$product->id)->assertOk();
});

test('users cannot view or download another users order even with a signature', function () {
    $order = akilaOrder(User::factory()->create());
    $this->actingAs(User::factory()->create());
    $this->get('/checkout/thank-you/'.$order->id)->assertForbidden();
    $this->get(URL::temporarySignedRoute('checkout.download', now()->addHour(), ['order' => $order->id]))->assertForbidden();
});

test('guest cannot enumerate orders or payment returns', function () {
    $order = akilaOrder();
    $this->get('/checkout/thank-you/'.$order->id)->assertForbidden();
    $this->get('/checkout/boleta/'.$order->id)->assertForbidden();
    $this->get('/checkout/success?external_reference='.$order->id.'&status=approved')->assertForbidden();
});

test('signed guest access expires', function () {
    $order = akilaOrder();
    $url = URL::temporarySignedRoute('checkout.thankyou', now()->addMinute(), ['order' => $order->id]);
    $this->get($url)->assertOk();
    $this->travel(2)->minutes();
    $this->get($url)->assertForbidden();
});

test('owner and purchasing guest can see their order', function () {
    $owner = User::factory()->create();
    $order = akilaOrder($owner);
    $this->actingAs($owner)->get('/checkout/thank-you/'.$order->id)->assertOk();
});

test('invoice uses private disk with historical compatibility', function () {
    Storage::fake('local');
    Storage::fake('public');
    $order = akilaOrder();
    $path = 'boletas/boleta-'.$order->id.'.pdf';
    Storage::disk('public')->put($path, '%PDF-historical');
    $this->get(app(OrderAccessService::class)->downloadUrl($order))->assertOk()->assertDownload();
    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertExists($path);
});

test('return parameters alone never confirm payment', function ($route) {
    $order = akilaOrder();
    $this->withSession(['guest_orders' => [$order->id]])->get('/checkout/'.$route.'?external_reference='.$order->id.'&status=approved')->assertOk();
    expect($order->fresh()->payment_status)->toBe('pending');
})->with(['success', 'failure', 'pending']);

test('verified approved payment updates only payment state', function () {
    $order = akilaOrder();
    $this->gateway->shouldReceive('getPayment')->once()->with('12345')->andReturn(akilaPayment($order));
    $this->withSession(['guest_orders' => [$order->id]])->get('/checkout/success?external_reference='.$order->id.'&payment_id=12345&status=rejected')->assertOk();
    expect($order->fresh()->payment_status)->toBe('approved')->and($order->fresh()->status)->toBe('pendiente');
});

test('invalid payment facts do not update order', function ($changes) {
    $order = akilaOrder();
    $this->gateway->shouldReceive('getPayment')->once()->andReturn(akilaPayment($order, $changes));
    $this->withSession(['guest_orders' => [$order->id]])->get('/checkout/success?external_reference='.$order->id.'&payment_id=12345')->assertOk();
    expect($order->fresh()->payment_status)->toBe('pending')->and($order->fresh()->mp_payment_id)->toBeNull();
})->with(['amount' => [['transaction_amount' => 1]], 'currency' => [['currency_id' => 'USD']], 'reference' => [['external_reference' => '999999']], 'id' => [['id' => 99]], 'status' => [['status' => 'invented']]]);

test('repeated signed webhook has only one state transition', function () {
    $order = akilaOrder();
    $this->gateway->shouldReceive('getPayment')->twice()->with('12345')->andReturn(akilaPayment($order));
    $events = 0;
    Order::updated(function () use (&$events) {
        $events++;
    });
    for ($i = 0; $i < 2; $i++) {
        $this->postJson('/checkout/webhook?data.id=12345', ['type' => 'payment', 'data' => ['id' => '12345']], akilaWebhookHeaders())->assertOk();
    }
    expect($order->fresh()->payment_status)->toBe('approved')->and($events)->toBe(1);
});

test('invalid webhook signature is rejected without api call', function () {
    $this->postJson('/checkout/webhook?data.id=12345', ['type' => 'payment'], ['x-signature' => 'invalid'])->assertUnauthorized();
});

test('webhook rejects mismatched resource and incorrect payment amount', function () {
    $order = akilaOrder();
    $this->postJson('/checkout/webhook?data.id=12345', ['type' => 'payment', 'data' => ['id' => '99']], akilaWebhookHeaders())->assertBadRequest();
    $this->gateway->shouldReceive('getPayment')->once()->andReturn(akilaPayment($order, ['transaction_amount' => 1]));
    $this->postJson('/checkout/webhook?data.id=12345', ['type' => 'payment'], akilaWebhookHeaders())->assertUnprocessable();
});

test('pending payment cannot downgrade previously approved payment', function () {
    $order = akilaOrder();
    $order->forceFill(['payment_status' => 'approved', 'mp_payment_id' => '12345'])->save();
    $this->gateway->shouldReceive('getPayment')->once()->andReturn(akilaPayment($order, ['status' => 'pending']));
    $this->postJson('/checkout/webhook?data.id=12345', ['type' => 'payment'], akilaWebhookHeaders())->assertOk();
    expect($order->fresh()->payment_status)->toBe('approved');
});

test('checkout price change requires review without creating order', function () {
    $product = akilaProduct();
    $line = app(ProductPricingService::class)->quote($product, []);
    $product->update(['precio' => 9000]);
    $this->withSession(['cart' => [$line['hash'] => $line]])->post('/checkout', [
        'cliente_nombre' => 'Cliente', 'cliente_telefono' => '912345678', 'metodo_entrega' => 'pickup',
    ])->assertRedirect(route('cart.index'))->assertSessionHasErrors();
    expect(Order::count())->toBe(0)->and(session('cart')[$line['hash']]['precio_unit'])->toEqual(9000);
});

test('failed preference preserves cart and retry reuses order', function () {
    $product = akilaProduct();
    $line = app(ProductPricingService::class)->quote($product, []);
    $this->gateway->shouldReceive('createPreference')->twice()->andThrow(new RuntimeException('unavailable'));
    $this->withSession(['cart' => [$line['hash'] => $line]]);
    for ($i = 0; $i < 2; $i++) {
        $this->post('/checkout', ['cliente_nombre' => 'Cliente', 'cliente_telefono' => '912345678', 'metodo_entrega' => 'pickup'])->assertRedirect()->assertSessionHasErrors();
    }
    expect(Order::count())->toBe(1)->and(session('cart'))->not->toBeEmpty();
});

test('user mass assignment permits password and blocks admin privileges', function () {
    $user = new User(['name' => 'Cliente', 'email' => 'cliente@example.com', 'password' => 'password123', 'is_admin' => true]);
    expect($user->is_admin)->toBeNull()->and(password_verify('password123', $user->password))->toBeTrue();
});

test('header counts purchased units instead of different lines', function () {
    $product = akilaProduct();
    $line = app(ProductPricingService::class)->quote($product, [], 7);
    $this->withSession(['cart' => [$line['hash'] => $line]])->get('/')->assertOk()->assertSee('Carrito');
    expect(collect(session('cart'))->sum('unidades'))->toBe(7);
    $html = $this->get('/')->getContent();
    expect($html)->toMatch('/text-xs">7<\/span>/');
});

test('new invoices are private and unauthorized downloads do not generate them', function () {
    Storage::fake('local');
    Storage::fake('public');
    $order = akilaOrder();
    $path = 'boletas/boleta-'.$order->id.'.pdf';
    $this->get('/checkout/boleta/'.$order->id)->assertForbidden();
    Storage::disk('local')->assertMissing($path);
    $this->get(app(OrderAccessService::class)->downloadUrl($order))->assertOk();
    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
});

test('historical invoice command preserves content and removes public exposure', function () {
    Storage::fake('local');
    Storage::fake('public');
    $path = 'boletas/boleta-12.pdf';
    Storage::disk('public')->put($path, '%PDF-original');
    $this->artisan('invoices:privatize', ['--dry-run' => true])->assertSuccessful();
    Storage::disk('public')->assertExists($path);
    $this->artisan('invoices:privatize')->assertSuccessful();
    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
    expect(Storage::disk('local')->get($path))->toBe('%PDF-original');
    expect(Storage::disk('local')->files('boletas/legacy-originals'))->toHaveCount(1);
    $this->artisan('invoices:privatize')->assertSuccessful();
});

test('administrator access is limited to administrative order routes', function () {
    $order = akilaOrder(User::factory()->create());
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $this->actingAs($admin)->get('/checkout/thank-you/'.$order->id)->assertForbidden();
    $this->get('/admin/orders/'.$order->id)->assertOk();
    $this->actingAs(User::factory()->create())->get('/admin/orders/'.$order->id)->assertRedirect(route('home'));
});

test('signed guest return tolerates appended provider parameters without trusting them', function () {
    $order = akilaOrder();
    $url = URL::temporarySignedRoute('checkout.success', now()->addHour(), ['order' => $order->id]);
    $this->get($url.'&status=approved&external_reference=999')->assertOk();
    expect($order->fresh()->payment_status)->toBe('pending');
});

test('payment verification failure leaves the current cart intact', function () {
    $order = akilaOrder();
    $cart = ['snapshot' => ['unidades' => 1, 'total' => 5000]];
    $this->gateway->shouldReceive('getPayment')->once()->andThrow(new RuntimeException('unavailable'));
    $this->withSession(['guest_orders' => [$order->id], 'cart' => $cart])
        ->get('/checkout/success?external_reference='.$order->id.'&payment_id=12345')->assertOk();
    expect(session('cart'))->toBe($cart)->and($order->fresh()->payment_status)->toBe('pending');
});

test('approved payment clears only its unchanged cart snapshot', function ($changed) {
    $order = akilaOrder();
    $oldCart = ['snapshot' => ['unidades' => 1, 'total' => 5000]];
    $cart = $changed ? ['new' => ['unidades' => 2, 'total' => 10000]] : $oldCart;
    $this->gateway->shouldReceive('getPayment')->once()->andReturn(akilaPayment($order));
    $this->withSession(['guest_orders' => [$order->id], 'cart' => $cart,
        'checkout_cart' => [$order->id => hash('sha256', json_encode($oldCart))]])
        ->get('/checkout/success?external_reference='.$order->id.'&payment_id=12345')->assertOk();
    expect(session('cart'))->toBe($changed ? $cart : null);
})->with([false, true]);

test('payment for another existing order cannot be used on a return', function () {
    $order = akilaOrder();
    $other = akilaOrder();
    $this->gateway->shouldReceive('getPayment')->once()->andReturn(akilaPayment($other));
    $this->withSession(['guest_orders' => [$order->id]])
        ->get('/checkout/success?external_reference='.$order->id.'&payment_id=12345')->assertOk();
    expect($order->fresh()->payment_status)->toBe('pending')->and($other->fresh()->payment_status)->toBe('pending');
});

test('refund can update payment but old approval cannot reverse it', function () {
    $order = akilaOrder();
    $order->forceFill(['payment_status' => 'approved', 'mp_payment_id' => '12345'])->save();
    $this->gateway->shouldReceive('getPayment')->once()->andReturn(akilaPayment($order, ['status' => 'refunded']));
    $this->postJson('/checkout/webhook?data.id=12345', ['type' => 'payment'], akilaWebhookHeaders())->assertOk();
    $this->gateway->shouldReceive('getPayment')->once()->andReturn(akilaPayment($order, ['status' => 'approved']));
    $this->postJson('/checkout/webhook?data.id=12345', ['type' => 'payment'], akilaWebhookHeaders())->assertOk();
    expect($order->fresh()->payment_status)->toBe('refunded')->and($order->fresh()->status)->toBe('pendiente');
});

test('successful preference retry reuses same order and preference', function () {
    $product = akilaProduct();
    $line = app(ProductPricingService::class)->quote($product, []);
    $this->gateway->shouldReceive('createPreference')->once()->andReturn('https://sandbox.mercadopago.cl/checkout/test');
    $this->withSession(['cart' => [$line['hash'] => $line]]);
    for ($i = 0; $i < 2; $i++) {
        $this->post('/checkout', ['cliente_nombre' => 'Cliente', 'cliente_telefono' => '912345678', 'metodo_entrega' => 'pickup'])
            ->assertRedirect('https://sandbox.mercadopago.cl/checkout/test');
    }
    expect(Order::count())->toBe(1)->and(session('cart'))->not->toBeEmpty();
});

test('legacy cart is rejected instead of trusting historical session prices', function () {
    $product = akilaProduct();
    $this->withSession(['cart' => ['old' => ['producto_id' => $product->id, 'unidades' => 1, 'precio_unit' => 1, 'total' => 1]]])
        ->post('/checkout', ['cliente_nombre' => 'Cliente', 'cliente_telefono' => '912345678', 'metodo_entrega' => 'pickup'])
        ->assertRedirect(route('cart.index'))->assertSessionHasErrors();
    expect(Order::count())->toBe(0);
});

test('csrf is excluded only for webhook', function () {
    $this->app['env'] = 'production';
    $this->postJson('/checkout/webhook?data.id=12345', ['type' => 'payment'])->assertUnauthorized();
    $this->postJson('/cart/add', ['product_id' => 1, 'unidades' => 1])->assertStatus(419);
});

test('payment migration preserves historical status and rolls back its own columns', function () {
    $order = akilaOrder();
    $order->update(['status' => 'pagado']);
    $migration = require database_path('migrations/2026_10_21_000001_add_payment_verification_to_orders.php');
    $migration->down();
    $migration->up();
    expect($order->fresh()->status)->toBe('pagado')->and($order->fresh()->payment_status)->toBeNull();
});

test('preference payload uses Chilean currency signed returns and correct total', function () {
    $product = akilaProduct();
    $order = akilaOrder();
    $order->update(['metodo_entrega' => 'delivery', 'cliente_direccion' => 'Dirección del cliente', 'delivery_cost' => 2500, 'total' => 7500]);
    $order->items()->create(['product_id' => $product->id, 'nombre' => $product->nombre, 'unidades' => 1,
        'precio_unit' => 5000, 'total' => 5000, 'detalle' => []]);
    $client = Mockery::mock(MPHttpClient::class);
    $client->shouldReceive('send')->once()->withArgs(function ($request) use ($order) {
        $payload = json_decode($request->getPayload(), true);
        expect(array_sum(array_map(fn ($item) => $item['quantity'] * $item['unit_price'], $payload['items'])))->toEqual(7500);
        expect(array_unique(array_column($payload['items'], 'currency_id')))->toBe(['CLP']);
        expect(json_encode($payload))->not->toContain('Costa Rica');
        expect($payload['external_reference'])->toBe((string) $order->id);
        foreach ($payload['back_urls'] as $url) {
            expect(Request::create($url)->hasValidSignature())->toBeTrue();
        }

        return true;
    })->andReturn(new MPResponse(201, ['init_point' => 'https://mercadopago.cl/pay', 'sandbox_init_point' => 'https://sandbox.mercadopago.cl/pay']));
    $original = MercadoPagoConfig::getHttpClient();
    MercadoPagoConfig::setHttpClient($client);
    config(['services.mercadopago.access_token' => 'TEST-fake']);
    try {
        expect((new MercadoPagoService)->createPreference($order))->toBe('https://sandbox.mercadopago.cl/pay');
    } finally {
        MercadoPagoConfig::setHttpClient($original);
        MercadoPagoConfig::setAccessToken('');
    }
});

test('stale provider payment update does not replace a newer verified state', function () {
    $order = akilaOrder();
    $order->forceFill(['mp_payment_id' => '12345', 'payment_status' => 'rejected', 'payment_updated_at' => now()])->save();
    $this->gateway->shouldReceive('getPayment')->once()->andReturn(akilaPayment($order, ['date_last_updated' => now()->subHour()->toIso8601String()]));
    $this->postJson('/checkout/webhook?data.id=12345', ['type' => 'payment'], akilaWebhookHeaders())->assertOk();
    expect($order->fresh()->payment_status)->toBe('rejected');
});

test('expired webhook signature is rejected', function () {
    $ts = (string) (time() - 1000);
    $signature = hash_hmac('sha256', "id:12345;request-id:test-request;ts:{$ts};", 'test-secret');
    $this->postJson('/checkout/webhook?data.id=12345', ['type' => 'payment'],
        ['x-request-id' => 'test-request', 'x-signature' => "ts={$ts},v1={$signature}"])->assertUnauthorized();
});

test('completed checkout releases attempt so an identical purchase can be made again', function () {
    $product = akilaProduct();
    $line = app(ProductPricingService::class)->quote($product, []);
    $this->gateway->shouldReceive('createPreference')->twice()->andReturn('https://sandbox.mercadopago.cl/checkout/test');
    $details = ['cliente_nombre' => 'Cliente', 'cliente_telefono' => '912345678', 'metodo_entrega' => 'pickup'];
    $this->withSession(['cart' => [$line['hash'] => $line]])->post('/checkout', $details)->assertRedirect();
    $order = Order::firstOrFail();
    $order->forceFill(['payment_status' => 'approved'])->save();
    $this->get('/checkout/thank-you/'.$order->id)->assertOk();
    expect(session('checkout_attempts', []))->toBeEmpty()->and(session('cart'))->toBeNull();
    $this->withSession(['cart' => [$line['hash'] => $line]])->post('/checkout', $details)->assertRedirect();
    expect(Order::count())->toBe(2);
});

test('refunded checkout is not reused for a new payment attempt', function () {
    $product = akilaProduct();
    $line = app(ProductPricingService::class)->quote($product, []);
    $this->gateway->shouldReceive('createPreference')->twice()->andReturn('https://sandbox.mercadopago.cl/checkout/test');
    $details = ['cliente_nombre' => 'Cliente', 'cliente_telefono' => '912345678', 'metodo_entrega' => 'pickup'];
    $this->withSession(['cart' => [$line['hash'] => $line]])->post('/checkout', $details)->assertRedirect();
    Order::firstOrFail()->update(['payment_status' => 'refunded']);
    $this->post('/checkout', $details)->assertRedirect();
    expect(Order::count())->toBe(2);
});
