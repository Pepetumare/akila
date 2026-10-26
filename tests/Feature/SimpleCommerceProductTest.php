<?php

use App\Commerce\Configuration\ProductConfigurationInput;
use App\Commerce\Configuration\ProductConfigurationService;
use App\Commerce\Contracts\ProductCatalog;
use App\Models\Ingrediente;
use App\Models\Order;
use App\Services\MercadoPagoService;
use Tests\Support\CommerceFixtures as Fixture;

test('simple retail product quotes carts and checks out without any configuration entities', function () {
    $product = Fixture::product();
    expect(Ingrediente::count())->toBe(0)->and($product->bloques()->count())->toBe(0)
        ->and($product->gruposOpciones()->count())->toBe(0)->and($product->reglas()->count())->toBe(0);
    $payload = ['product_id' => $product->id, 'unidades' => 1, 'price' => 1, 'recargo' => -10000];
    $this->postJson(route('cart.quote'), $payload)->assertOk()
        ->assertJsonPath('precio_unit', 19990)->assertJsonPath('recargo', 0)->assertJsonPath('total', 19990);
    $this->postJson(route('cart.add'), $payload)->assertOk();
    $this->postJson(route('cart.add'), $payload)->assertOk();
    expect(session('cart'))->toHaveCount(1);
    $line = array_values(session('cart'))[0];
    expect($line['unidades'])->toBe(2)->and($line['total'])->toEqual(39980)
        ->and($line['configuration']['blocks'])->toBe([]);
    $this->get(route('cart.index'))->assertOk()->assertSee('Figura Luffy');
    $this->get(route('checkout.index'))->assertOk()->assertSee('Figura Luffy');
    $gateway = Mockery::mock(MercadoPagoService::class);
    $gateway->shouldReceive('createPreference')->once()->withArgs(fn (Order $o) => (float) $o->total === 39980.0)
        ->andReturn('https://payments.example.test/preference');
    $this->app->instance(MercadoPagoService::class, $gateway);
    $this->post(route('checkout.store'), ['cliente_nombre' => 'Comprador', 'cliente_telefono' => '123456789',
        'metodo_entrega' => 'pickup'])->assertRedirect('https://payments.example.test/preference');
    $order = Order::with('items')->sole();
    $item = $order->items->sole();
    expect($item->snapshot['version'])->toBe(2)
        ->and($item->snapshot['product']['name'])->toBe('Figura Luffy')
        ->and($item->snapshot['product']['unit_type'])->toBe('unidad')
        ->and($item->snapshot['quantity'])->toBe(2)
        ->and($item->snapshot['amounts']['final_unit'])->toBe('19990.00')
        ->and($item->snapshot['amounts']['line_total'])->toBe('39980.00');
    $saved = $item->snapshot;
    $product->update(['nombre' => 'Figura actualizada', 'precio' => 25000]);
    expect($item->fresh()->snapshot)->toBe($saved);
    $this->get(route('checkout.thankyou', $order))->assertOk()->assertSee('Figura Luffy');
});

test('simple product rejects unassigned choices instead of silently ignoring them', function () {
    $product = Fixture::product();
    $this->postJson(route('cart.quote'), ['product_id' => $product->id, 'option_ids' => [999999]])->assertUnprocessable();
    $this->postJson(route('cart.add'), ['product_id' => $product->id, 'unidades' => 1,
        'blocks' => [['block_id' => 999999, 'option_ids' => []]]])->assertUnprocessable();
    expect(session('cart', []))->toBe([]);
});

test('purchase quantity is independent from included commercial content', function () {
    $product = Fixture::product('Caja de donuts', 6000);
    $product->update(['unidades' => 12]);
    $definition = app(ProductCatalog::class)->definition($product);
    $quote = app(ProductConfigurationService::class)->quote($definition, new ProductConfigurationInput, 2);
    expect($quote->quantity)->toBe(2)->and($quote->total())->toBe(1200000)
        ->and($quote->details['included_quantity'])->toBe(12)
        ->and($quote->configuration)->not->toHaveKey('pieces');
});
