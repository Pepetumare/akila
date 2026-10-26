<?php

use App\Models\GrupoOpcion;
use App\Models\Order;
use App\Models\Producto;
use App\Models\User;
use App\Services\MercadoPagoService;
use Database\Seeders\SushiAkilaMenuSeeder;
use Illuminate\Support\Facades\DB;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Net\MPHttpClient;
use MercadoPago\Net\MPResponse;
use Tests\Support\MenuAuditConfiguration;

function finalAuditOption(string $group, string $name): int
{
    return GrupoOpcion::where('slug', $group)->sole()->opciones()->where('nombre', $name)->sole()->id;
}

test('final audit pickup ignores delivery-only zone without an undefined distance', function () {
    $this->seed(SushiAkilaMenuSeeder::class);
    $product = Producto::where('slug', 'goloso')->sole();
    $this->postJson(route('cart.add'), ['product_id' => $product->id, 'unidades' => 1])->assertOk();
    $gateway = Mockery::mock(MercadoPagoService::class);
    $gateway->shouldReceive('createPreference')->once()->andReturn('https://example.test/pickup');
    $this->app->instance(MercadoPagoService::class, $gateway);
    $this->post(route('checkout.store'), ['cliente_nombre' => 'Prueba', 'cliente_telefono' => '123',
        'metodo_entrega' => 'pickup', 'zona_delivery' => 'fuera'])->assertRedirect('https://example.test/pickup');
    $order = Order::sole();
    expect($order->zona_delivery)->toBeNull()->and($order->kms_fuera)->toBeNull()
        ->and($order->delivery_cost)->toBe('0.00')->and($order->total)->toBe('10000.00');
});

test('final audit all 37 products enforce availability and 35 complete quote cart checkout and historical snapshots', function () {
    $this->seed(SushiAkilaMenuSeeder::class);
    $quotes = [];
    $subtotal = 0;
    foreach (Producto::orderBy('id')->get() as $product) {
        $payload = MenuAuditConfiguration::payload($product) + ['price' => 1, 'total' => 1, 'discount' => 999999];
        if (! $product->activo) {
            $this->get(route('producto.modal', $product->id))->assertNotFound();
            $this->postJson(route('cart.quote'), $payload)->assertUnprocessable();
            $this->postJson(route('cart.add'), $payload)->assertUnprocessable();

            continue;
        }
        $this->get(route('producto.modal', $product->id))->assertOk()->assertSee($product->nombre)
            ->assertSee($product->unidades.' '.$product->unidad_tipo->label($product->unidades));
        $quote = $this->postJson(route('cart.quote'), $payload)->assertOk()->json();
        expect($quote['precio_base'])->toEqual($product->precio)->and($quote['recargo'])->toEqual(0);
        $this->postJson(route('cart.add'), $payload)->assertOk();
        if ($product->personalizable) {
            $this->postJson(route('cart.add'), ['product_id' => $product->id, 'unidades' => 1])->assertUnprocessable();
        }
        $quotes[$product->id] = $quote;
        $subtotal += $quote['total'];
    }
    expect($quotes)->toHaveCount(35)->and(session('cart'))->toHaveCount(35);
    $this->get(route('cart.index'))->assertOk();
    $gateway = Mockery::mock(MercadoPagoService::class);
    $gateway->shouldReceive('createPreference')->once()->withArgs(fn ($order) => (float) $order->total === (float) $subtotal)
        ->andReturn('https://example.test/final-audit-all');
    $this->app->instance(MercadoPagoService::class, $gateway);
    $this->post(route('checkout.store'), ['cliente_nombre' => 'Auditoría', 'cliente_telefono' => '123', 'metodo_entrega' => 'pickup', 'total' => 1])
        ->assertRedirect('https://example.test/final-audit-all');
    $order = Order::sole();
    expect($order->items)->toHaveCount(35);
    foreach ($order->items as $item) {
        $quote = $quotes[$item->product_id];
        $snapshot = $item->snapshot;
        expect($snapshot['version'])->toBe(2)->and($snapshot['configuration'])->toBe($quote['configuration'])
            ->and($snapshot['selections'])->toBe($quote['detalle'])->and($snapshot['charges'])->toBe($quote['charges'])
            ->and((float) $snapshot['amounts']['final_unit'])->toEqual($quote['precio_unit'])
            ->and((float) $item->total)->toEqual($quote['total']);
        Producto::findOrFail($item->product_id)->update(['nombre' => 'Cambio posterior '.$item->product_id, 'precio' => 99999]);
        expect($item->fresh()->snapshot)->toBe($snapshot)->and($item->fresh()->nombre)->toBe($quote['nombre']);
    }
    expect(view('pdf.invoice', ['order' => $order])->render())->toContain('Promo Hot', 'El Emperador', 'Arrollados');
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->get(route('admin.productos.index', ['q' => 'Gohan']))->assertOk()->assertSee('Gohan Flecha')->assertSee('Gohan Orion');
});

test('final audit representative route preserves configuration surcharges and real SDK payment totals', function (string $slug, int $unit) {
    $this->seed(SushiAkilaMenuSeeder::class);
    $product = Producto::where('slug', $slug)->sole();
    $payload = MenuAuditConfiguration::payload($product);
    if ($slug === 'hand-roll-tradicional') {
        $payload['option_ids'] = [finalAuditOption('relleno-hand-roll', 'Salmón')];
    } elseif ($slug === '5-hand-rolls') {
        $payload['blocks'][0]['option_ids'][] = finalAuditOption('segundo-ingrediente', 'Champiñón');
    } elseif ($slug === 'muchupleto') {
        $payload['option_ids'] = [finalAuditOption('relleno-muchupleto', 'Salmón')];
    } elseif ($slug === 'akilaburger') {
        $payload['option_ids'] = [finalAuditOption('relleno-akilaburger', 'Atún')];
    } elseif ($slug === 'arrollados') {
        $payload['option_ids'] = GrupoOpcion::where('slug', 'sabor-arrollados')->sole()->opciones->pluck('id')->all();
    }
    $payload += ['price' => 1, 'total' => 1, 'extra_price' => -9999];
    $quote = $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('precio_unit', $unit)->json();
    $this->postJson(route('cart.add'), $payload)->assertOk();
    $this->postJson(route('cart.add'), $payload)->assertOk();
    expect(session('cart'))->toHaveCount(1)->and(session('cart')[$quote['hash']]['unidades'])->toBe(2);
    $this->post(route('cart.update'), ['hash' => $quote['hash'], 'unidades' => 3])->assertRedirect();
    expect(session('cart')[$quote['hash']]['configuration'])->toBe($quote['configuration'])
        ->and(session('cart')[$quote['hash']]['total'])->toEqual($unit * 3);
    $gateway = Mockery::mock(MercadoPagoService::class);
    $gateway->shouldReceive('createPreference')->once()->andReturn('https://example.test/final-audit');
    $this->app->instance(MercadoPagoService::class, $gateway);
    $this->post(route('checkout.store'), ['cliente_nombre' => 'Auditoría', 'cliente_telefono' => '123',
        'metodo_entrega' => 'delivery', 'zona_delivery' => 'dentro', 'cliente_direccion' => 'Prueba', 'total' => 1])
        ->assertRedirect('https://example.test/final-audit');
    $order = Order::sole();
    expect((float) $order->subtotal)->toEqual($unit * 3)->and((float) $order->total)->toEqual($unit * 3 + 2500)
        ->and($order->items->sole()->snapshot['selections'])->toBe($quote['detalle'])
        ->and($order->items->sole()->snapshot['charges'])->toBe($quote['charges']);
    $client = Mockery::mock(MPHttpClient::class);
    $client->shouldReceive('send')->once()->withArgs(function ($request) use ($order, $unit) {
        $body = json_decode($request->getPayload(), true);

        return collect($body['items'])->sum(fn ($item) => $item['unit_price'] * $item['quantity']) == $order->total
            && $body['items'][0]['unit_price'] == $unit && $body['items'][0]['quantity'] === 3
            && $body['external_reference'] === (string) $order->id;
    })->andReturn(new MPResponse(201, ['init_point' => 'https://example.test/payment', 'sandbox_init_point' => 'https://example.test/sandbox']));
    $previous = MercadoPagoConfig::getHttpClient();
    MercadoPagoConfig::setHttpClient($client);
    config(['services.mercadopago.access_token' => 'test-token']);
    try {
        (new MercadoPagoService)->createPreference($order);
    } finally {
        MercadoPagoConfig::setHttpClient($previous);
    }
    $this->post(route('cart.remove'), ['hash' => $quote['hash']])->assertRedirect();
    expect(session('cart'))->toBe([]);
})->with([
    ['30-piezas-a-eleccion', 14900], ['promo-hot', 19990], ['hand-roll-tradicional', 4990],
    ['5-hand-rolls', 19000], ['muchupleto', 6500], ['akilaburger', 7500],
    ['nikkei-jamon-serrano', 6990], ['arrollados', 5000], ['el-emperador', 49990],
]);

test('final audit direct requests enforce repetition limits for all five choice sizes', function (int $pieces) {
    $this->seed(SushiAkilaMenuSeeder::class);
    $product = Producto::where('slug', $pieces.'-piezas-a-eleccion')->sole();
    $payload = MenuAuditConfiguration::payload($product);
    $group = $product->bloques->first()->gruposOpciones->first(fn ($g) => str_starts_with($g->slug, 'relleno-'));
    foreach (['Salmón' => 2, 'Pollo' => 3] as $name => $repetitions) {
        if ($repetitions > count($payload['blocks'])) {
            continue;
        }
        $attack = $payload;
        $id = $group->opciones->firstWhere('nombre', $name)->id;
        for ($i = 0; $i < $repetitions; $i++) {
            $attack['blocks'][$i]['option_ids'] = [$id];
        }
        foreach (['cart.quote', 'cart.add'] as $route) {
            $this->postJson(route($route), $attack)->assertUnprocessable()->assertJsonValidationErrors('blocks');
        }
    }
    $attack = $payload;
    $attack['blocks'][0]['option_ids'] = $group->opciones->take(2)->pluck('id')->all();
    $this->postJson(route('cart.add'), $attack)->assertUnprocessable();
})->with([20, 30, 40, 50, 60]);

test('final audit measures category and ingredient relation queries in administration', function () {
    $this->seed(SushiAkilaMenuSeeder::class);
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    DB::enableQueryLog();
    $this->get(route('admin.productos.index'))->assertOk();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    $categories = collect($queries)->filter(fn ($q) => str_contains($q['query'], 'from "categorias"') || str_contains($q['query'], 'from `categorias`'));
    $ingredients = collect($queries)->filter(fn ($q) => str_contains($q['query'], 'ingrediente_producto'));
    expect($categories->count())->toBeLessThanOrEqual(2)->and($ingredients->count())->toBe(1);
});
