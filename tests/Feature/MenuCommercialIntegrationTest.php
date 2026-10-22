<?php

use App\Models\GrupoOpcion;
use App\Models\Order;
use App\Models\Producto;
use App\Models\User;
use App\Services\MercadoPagoService;
use Database\Seeders\SushiAkilaMenuBatch01Seeder;
use Illuminate\Support\Facades\DB;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Net\MPHttpClient;
use MercadoPago\Net\MPResponse;

beforeEach(function () {
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    $this->choice = Producto::where('slug', '30-piezas-a-eleccion')->sole();
    $this->gateway = Mockery::mock(MercadoPagoService::class);
    $this->app->instance(MercadoPagoService::class, $this->gateway);
});

function commercialPayload(Producto $product, array $slugs = ['pollo', 'camaron', 'pollo']): array
{
    $options = GrupoOpcion::where('slug', 'relleno-30-piezas-a-eleccion')->sole()->opciones->keyBy('slug');

    return ['product_id' => $product->id, 'unidades' => 1, 'version' => 2,
        'blocks' => $product->bloques->values()->map(fn ($block, $index) => [
            'block_id' => $block->id, 'option_ids' => [$options[$slugs[$index]]->id],
        ])->all()];
}

test('commercial quote validates one choice per roll and returns server price', function (array $slugs) {
    $payload = commercialPayload($this->choice, $slugs);
    $payload += ['price' => 1, 'extra_price' => -9000, 'subtotal' => 1, 'total' => 1, 'discount' => 99999];
    $response = $this->postJson(route('cart.quote'), $payload)->assertOk()
        ->assertJsonPath('valid', true)->assertJsonPath('precio_base', 14900)
        ->assertJsonPath('recargo', 0)->assertJsonPath('precio_unit', 14900)->assertJsonPath('total', 14900);
    expect($response->json('configuration.blocks'))->toHaveCount(3);
})->with([
    'proteins' => [['pollo', 'camaron', 'pollo']],
    'vegetables' => [['palta', 'champinon', 'palta']],
]);

test('commercial backend rejects invalid configurations for quote and add', function (string $attack) {
    $payload = commercialPayload($this->choice);
    $options = GrupoOpcion::where('slug', 'relleno-30-piezas-a-eleccion')->sole()->opciones->keyBy('slug');
    switch ($attack) {
        case 'foreign-option':
            $payload['blocks'][0]['option_ids'] = [GrupoOpcion::where('slug', 'proteina')->sole()->opciones->first()->id];
            break;
        case 'wrong-block-group':
            $fixedBlock = Producto::where('slug', 'costanera')->sole()->bloques->first();
            $payload['blocks'][0]['option_ids'] = [$fixedBlock->gruposOpciones->first()->opciones->first()->id];
            break;
        case 'foreign-block':
            $payload['blocks'][0]['block_id'] = Producto::where('slug', 'costanera')->sole()->bloques->first()->id;
            break;
        case 'inactive-option':
            $options['pollo']->update(['activo' => false]);
            break;
        case 'inactive-group':
            $options['pollo']->grupo->update(['activo' => false]);
            break;
        case 'inactive-product':
            $this->choice->update(['activo' => false]);
            break;
        case 'missing-option':
            $payload['blocks'][0]['option_ids'] = [99999999];
            break;
        case 'missing-block':
            $payload['blocks'][0]['block_id'] = 99999999;
            break;
        case 'max':
            $payload['blocks'][0]['option_ids'][] = $options['palta']->id;
            break;
        case 'min':
            $payload['blocks'][0]['option_ids'] = [];
            break;
        case 'salmon':
            $payload = commercialPayload($this->choice, ['salmon', 'salmon', 'pollo']);
            break;
        case 'repetition':
            $payload = commercialPayload($this->choice, ['pollo', 'pollo', 'pollo']);
            break;
        case 'empty':
            unset($payload['blocks']);
            break;
        case 'empty-blocks':
            $payload['blocks'] = [];
            break;
        case 'duplicate-block':
            $payload['blocks'][1]['block_id'] = $payload['blocks'][0]['block_id'];
            break;
        case 'duplicate-option':
            $payload['blocks'][0]['option_ids'][] = $payload['blocks'][0]['option_ids'][0];
            break;
        case 'incomplete-blocks':
            array_pop($payload['blocks']);
            break;
        case 'product-option-id':
            $payload['option_ids'] = [$options['pollo']->id];
            break;
        case 'invalid-removal':
            $payload['removed_base_ids'] = [$options['pollo']->ingrediente_id];
            break;
    }
    $this->postJson(route('cart.quote'), $payload)->assertUnprocessable();
    $this->postJson(route('cart.add'), $payload)->assertUnprocessable();
    expect(session('cart', []))->toBe([]);
})->with(['foreign-option', 'wrong-block-group', 'foreign-block', 'inactive-option', 'inactive-group', 'inactive-product',
    'missing-option', 'missing-block', 'max', 'min', 'salmon', 'repetition', 'empty', 'empty-blocks',
    'duplicate-block', 'duplicate-option', 'incomplete-blocks', 'product-option-id', 'invalid-removal']);

test('commercial errors identify ingredient limits without relying on names in business logic', function () {
    $this->postJson(route('cart.quote'), commercialPayload($this->choice, ['salmon', 'salmon', 'pollo']))
        ->assertUnprocessable()->assertJsonValidationErrors('blocks')->assertSee('1 vez');
    $this->postJson(route('cart.quote'), commercialPayload($this->choice, ['pollo', 'pollo', 'pollo']))
        ->assertUnprocessable()->assertSee('2 veces');
});

test('commercial wrapper choices come only from assigned groups and count fillings independently', function () {
    $payload = commercialPayload($this->choice, ['palta', 'champinon', 'palta']);
    $wrapper = GrupoOpcion::where('slug', 'envoltura')->sole()->opciones->firstWhere('slug', 'palta');
    foreach ($payload['blocks'] as &$block) {
        $block['option_ids'][] = $wrapper->id;
    }
    unset($block);
    // Palta as wrapper must not inflate the repetition count of Palta as filling.
    $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('recargo', 0);
    $this->choice->bloques->first()->gruposOpciones()->detach($wrapper->grupo_opcion_id);
    $this->postJson(route('cart.quote'), $payload)->assertUnprocessable();
});

test('commercial surcharge is exact server money and quantity only multiplies final unit price', function () {
    $option = GrupoOpcion::where('slug', 'relleno-30-piezas-a-eleccion')->sole()->opciones->firstWhere('slug', 'camaron');
    $option->update(['recargo' => '1000.25']);
    $payload = commercialPayload($this->choice) + ['price' => 0, 'recargo' => 0, 'extra_price' => 0, 'total' => 0, 'discount' => 9999];
    $payload['unidades'] = 2;
    $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('recargo', 1000.25)
        ->assertJsonPath('precio_unit', 15900.25)->assertJsonPath('total', 31800.5);
    $this->postJson(route('cart.add'), $payload)->assertOk();
    $line = array_values(session('cart'))[0];
    $this->post(route('cart.update'), ['hash' => $line['hash'], 'unidades' => 3])->assertRedirect();
    $updated = array_values(session('cart'))[0];
    expect($updated['configuration'])->toBe($line['configuration'])->and($updated['recargo'])->toEqual(1000.25)
        ->and($updated['total'])->toEqual(47700.75);
});

test('commercial explicit unconditional rule surcharges work but unknown conditions do not invent prices', function () {
    $option = GrupoOpcion::where('slug', 'relleno-30-piezas-a-eleccion')->sole()->opciones->firstWhere('slug', 'camaron');
    $this->choice->reglas()->create(['codigo' => 'test-confirmed', 'tipo' => 'recargo', 'opcion_id' => $option->id, 'valor' => '1000.00']);
    $this->choice->reglas()->create(['codigo' => 'test-ambiguous', 'tipo' => 'recargo', 'opcion_id' => $option->id,
        'valor' => '9000.00', 'metadata' => ['condicion' => 'ingredientes_que_no_cumplen']]);
    $this->postJson(route('cart.quote'), commercialPayload($this->choice))->assertOk()->assertJsonPath('precio_unit', 15900);
});

test('commercial hashes normalize arrays preserve different configurations and merge equal lines', function () {
    $payload = commercialPayload($this->choice);
    $wrapper = GrupoOpcion::where('slug', 'envoltura')->sole()->opciones->firstWhere('slug', 'sesamo');
    $payload['blocks'][0]['option_ids'][] = $wrapper->id;
    $this->postJson(route('cart.add'), $payload)->assertOk();
    $line = array_values(session('cart'))[0];
    $payload['blocks'] = array_reverse($payload['blocks']);
    foreach ($payload['blocks'] as &$block) {
        $block['option_ids'] = array_reverse($block['option_ids']);
    }
    unset($block);
    $this->postJson(route('cart.add'), $payload)->assertOk();
    expect(count(session('cart')))->toBe(1)->and(array_values(session('cart'))[0]['unidades'])->toBe(2);
    $this->postJson(route('cart.add'), commercialPayload($this->choice, ['palta', 'champinon', 'salmon']))->assertOk();
    expect(count(session('cart')))->toBe(2);
    $this->post(route('cart.remove'), ['hash' => $line['hash']])->assertRedirect();
    expect(count(session('cart')))->toBe(1);
});

test('commercial fixed batch products are added without manual block choices', function () {
    foreach (Producto::where('personalizable', false)->get() as $product) {
        $response = $this->postJson(route('cart.quote'), ['product_id' => $product->id]);
        $response->assertOk()->assertJsonPath('precio_unit', (int) $product->precio);
        expect($response->json('detalle.Bloques'))->toHaveCount($product->bloques()->count());
    }
    $product = Producto::where('slug', 'costanera')->sole();
    $this->postJson(route('cart.add'), ['product_id' => $product->id, 'unidades' => 1])->assertOk();
    $line = array_values(session('cart'))[0];
    $names = collect($line['detalle']['Bloques'])->map(fn ($block) => collect($block['grupos'])->flatMap(fn ($g) => array_column($g['opciones'], 'nombre'))->all());
    expect($names[0])->toContain('Pollo', 'Frito')->and($names[1])->toContain('Camarón', 'Palta')
        ->and($names[2])->toContain('Palta', 'Queso crema');
    $tampered = $line['configuration'];
    $tampered['product_id'] = $product->id;
    $tampered['blocks'][0]['option_ids'] = [];
    $this->postJson(route('cart.quote'), $tampered)->assertUnprocessable();
});

test('commercial modal reads only one product dynamically and does not expose internal rules', function () {
    DB::flushQueryLog();
    DB::enableQueryLog();
    $response = $this->get(route('producto.modal', $this->choice->id));
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();
    $response->assertOk()->assertSee('Roll 1')->assertSee('Roll 2')->assertSee('Roll 3')
        ->assertSee('Proteínas')->assertSee('Vegetales')->assertSee('Pollo')->assertSee('Camarón')
        ->assertSee('data-menu-config', false)->assertDontSee('max_repeticiones')->assertDontSee('modificacion_relleno');
    expect($queries)->toBeLessThan(24);
    $this->get(route('producto.modal', Producto::where('slug', 'costanera')->sole()->id))
        ->assertOk()->assertSee('Composición establecida')->assertDontSee('data-menu-option', false);
});

test('commercial checkout preserves readable blocks money and immutable snapshot after catalog edits', function () {
    $payload = commercialPayload($this->choice);
    $this->postJson(route('cart.add'), $payload)->assertOk();
    $this->gateway->shouldReceive('createPreference')->once()->withArgs(function (Order $order) {
        return $order->total === '14900.00' && $order->subtotal === '14900.00'
            && $order->items->first()->snapshot['version'] === 2;
    })->andReturn('https://example.test/checkout');
    $this->post(route('checkout.store'), ['cliente_nombre' => 'Prueba', 'cliente_telefono' => '123', 'metodo_entrega' => 'pickup', 'total' => 1])
        ->assertRedirect('https://example.test/checkout');
    $order = Order::sole();
    $item = $order->items()->sole();
    $snapshot = $item->snapshot;
    expect($snapshot['configuration']['version'])->toBe(2)->and($snapshot['selections']['Bloques'])->toHaveCount(3)
        ->and($snapshot['amounts']['final_unit'])->toBe('14900.00')->and($item->precio_unit)->toBe('14900.00');
    $this->choice->update(['nombre' => 'Nombre cambiado', 'precio' => 99000]);
    GrupoOpcion::where('slug', 'relleno-30-piezas-a-eleccion')->sole()->opciones()->first()->update(['nombre' => 'Otro nombre']);
    expect($item->fresh()->snapshot)->toBe($snapshot);
    $this->get(route('checkout.thankyou', $order))->assertOk()->assertSee('Pollo');
    expect(view('pdf.invoice', ['order' => $order->load('items')])->render())->toContain('Pollo', 'Camarón');
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('Camarón');
});

test('commercial repricing rejects stale selections before checkout and refreshes changed option prices', function () {
    $payload = commercialPayload($this->choice);
    $this->postJson(route('cart.add'), $payload)->assertOk();
    $option = GrupoOpcion::where('slug', 'relleno-30-piezas-a-eleccion')->sole()->opciones->firstWhere('slug', 'camaron');
    $option->update(['recargo' => 1000]);
    $this->post(route('checkout.store'), ['cliente_nombre' => 'Prueba', 'cliente_telefono' => '123', 'metodo_entrega' => 'pickup'])
        ->assertRedirect(route('cart.index'))->assertSessionHasErrors();
    expect(Order::count())->toBe(0)->and(array_values(session('cart'))[0]['precio_unit'])->toEqual(15900);
    $option->update(['activo' => false]);
    $this->get(route('checkout.index'))->assertRedirect(route('cart.index'))->assertSessionHasErrors();
});

test('commercial Mercado Pago payload equals snapshot totals including options and delivery', function () {
    $option = GrupoOpcion::where('slug', 'relleno-30-piezas-a-eleccion')->sole()->opciones->firstWhere('slug', 'camaron');
    $option->update(['recargo' => 1000]);
    $payload = commercialPayload($this->choice);
    $payload['unidades'] = 2;
    $this->postJson(route('cart.add'), $payload)->assertOk();
    $this->gateway->shouldReceive('createPreference')->once()->andReturn('https://example.test/payment');
    $this->post(route('checkout.store'), ['cliente_nombre' => 'Prueba', 'cliente_telefono' => '123',
        'metodo_entrega' => 'delivery', 'zona_delivery' => 'dentro', 'cliente_direccion' => 'Prueba 123'])
        ->assertRedirect('https://example.test/payment');
    $order = Order::sole();
    expect($order->subtotal)->toBe('31800.00')->and($order->total)->toBe('34300.00');
    $client = Mockery::mock(MPHttpClient::class);
    $client->shouldReceive('send')->once()->withArgs(function ($request) use ($order) {
        $body = json_decode($request->getPayload(), true);
        $sum = collect($body['items'])->sum(fn ($item) => $item['unit_price'] * $item['quantity']);

        return (float) $sum === (float) $order->total && $body['items'][0]['unit_price'] == 15900
            && $body['external_reference'] === (string) $order->id;
    })->andReturn(new MPResponse(201, ['init_point' => 'https://example.test/real', 'sandbox_init_point' => 'https://example.test/sandbox']));
    $previousClient = MercadoPagoConfig::getHttpClient();
    MercadoPagoConfig::setHttpClient($client);
    config(['services.mercadopago.access_token' => 'test-token']);
    try {
        (new MercadoPagoService)->createPreference($order);
    } finally {
        MercadoPagoConfig::setHttpClient($previousClient);
    }
});
