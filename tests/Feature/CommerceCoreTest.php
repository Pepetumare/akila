<?php

use App\Commerce\Configuration\ProductConfigurationInput;
use App\Commerce\Configuration\ProductConfigurationService;
use App\Commerce\Contracts\ProductCatalog;
use App\Commerce\Exceptions\ConfigurationException;
use App\Commerce\Orders\OrderItemSnapshotService as CoreSnapshots;
use App\Commerce\Pricing\ProductQuote;
use App\Models\BloquePromocion;
use App\Models\Ingrediente;
use App\Models\Order;
use App\Models\ReglaPromocion;
use App\Services\MercadoPagoService;
use App\Services\ProductPricingService;
use Illuminate\Support\Facades\DB;
use Tests\Support\CommerceFixtures as Fixture;

function neutralCommerceScenario(string $kind): array
{
    if ($kind === 'coffee') {
        $product = Fixture::product('Café Latte', 3000, true);
        $size = Fixture::group($product, 'Tamaño', ['Pequeño' => 0, 'Mediano' => 500, 'Grande' => 1000]);
        $milk = Fixture::group($product, 'Tipo de leche', ['Entera' => 0, 'Almendra' => 700]);

        return [$product, array_merge(Fixture::selected($size, 'Grande'), Fixture::selected($milk, 'Almendra')), 4700];
    }
    if ($kind === 'clothing') {
        $product = Fixture::product('Polera', 12000, true);
        $size = Fixture::group($product, 'Talla', ['S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0]);
        $color = Fixture::group($product, 'Color', ['Negro' => 0, 'Blanco' => 0]);

        return [$product, array_merge(Fixture::selected($size, 'L'), Fixture::selected($color, 'Negro')), 12000];
    }
    $product = Fixture::product('Hamburguesa Doble', 8000, true);
    $main = Fixture::group($product, 'Tipo de carne', ['Vacuno' => 0, 'Pollo' => 0, 'Vegetariana' => 0]);
    $cheese = Fixture::group($product, 'Queso', ['Cheddar' => 0, 'Gouda' => 500], 0, 1);
    $extras = Fixture::group($product, 'Extras', ['Tocino' => 1000, 'Huevo' => 700, 'Palta' => 1000], 0, 3);

    return [$product, array_merge(Fixture::selected($main, 'Vacuno'), Fixture::selected($cheese, 'Gouda'),
        Fixture::selected($extras, 'Tocino')), 9500];
}

test('same neutral core quotes and records configurable products without ingredients or blocks', function (string $kind) {
    [$product, $ids, $expected] = neutralCommerceScenario($kind);
    expect(Ingrediente::count())->toBe(0)->and($product->bloques()->count())->toBe(0);
    $definition = app(ProductCatalog::class)->definition($product);
    DB::enableQueryLog();
    DB::flushQueryLog();
    $quote = app(ProductConfigurationService::class)->quote($definition,
        ProductConfigurationInput::fromArray(['option_ids' => array_reverse($ids)]), 1);
    expect(DB::getQueryLog())->toBe([]);
    DB::disableQueryLog();
    expect($quote)->toBeInstanceOf(ProductQuote::class)->and($quote->finalUnitPrice())->toBe($expected * 100)
        ->and($quote->details)->not->toHaveKey('pieces')->and($quote->details)->not->toHaveKey('ingredients');
    $input = ['product_id' => $product->id, 'unidades' => 1, 'option_ids' => $ids,
        'price' => 1, 'recargo' => -50000, 'total' => 1];
    $first = $this->postJson(route('cart.quote'), $input)->assertOk()->assertJsonPath('total', $expected);
    $input['option_ids'] = array_reverse($ids);
    $this->postJson(route('cart.quote'), $input)->assertOk()->assertJsonPath('hash', $first->json('hash'));
    $this->postJson(route('cart.add'), $input)->assertOk();
    $gateway = Mockery::mock(MercadoPagoService::class);
    $gateway->shouldReceive('createPreference')->once()->andReturn('https://payments.example.test/preference');
    $this->app->instance(MercadoPagoService::class, $gateway);
    $this->post(route('checkout.store'), ['cliente_nombre' => 'Comprador', 'cliente_telefono' => '123456789',
        'metodo_entrega' => 'pickup'])->assertRedirect('https://payments.example.test/preference');
    $item = Order::with('items')->sole()->items->sole();
    expect($item->snapshot['amounts']['final_unit'])->toBe(number_format($expected, 2, '.', ''))
        ->and($item->snapshot['product']['unit_type'])->toBe('unidad')
        ->and($item->snapshot['selections']['Grupos'])->toHaveCount(count($definition->groups));
    $history = $item->snapshot;
    $product->update(['nombre' => 'Producto editado', 'precio' => 99999]);
    $product->gruposOpciones->each(fn ($g) => $g->opciones->each(fn ($o) => $o->update(['nombre' => 'Opción editada', 'recargo' => 999])));
    expect($item->fresh()->snapshot)->toBe($history)->and($item->fresh()->nombre)->not->toBe('Producto editado');
})->with(['coffee', 'burger', 'clothing']);

test('one group supports each configured surcharge', function (string $name, int $expected) {
    $product = Fixture::product('Café Latte', 3000, true);
    $group = Fixture::group($product, 'Tamaño', ['Pequeño' => 0, 'Mediano' => 500, 'Grande' => 1000]);
    $quote = app(ProductPricingService::class)->quoteResult($product,
        ProductConfigurationInput::fromArray(['option_ids' => Fixture::selected($group, $name)]));
    expect($quote->finalUnitPrice())->toBe($expected * 100);
})->with([['Pequeño', 3000], ['Mediano', 3500], ['Grande', 4000]]);

test('configuration rejects missing excessive foreign inactive duplicate and malformed selections', function (string $attack) {
    $product = Fixture::product('Producto configurable', 1000, true);
    $group = Fixture::group($product, 'Formato', ['A' => 0, 'B' => 100]);
    $ids = Fixture::selected($group, 'A');
    $payload = ['product_id' => $product->id, 'unidades' => 1, 'option_ids' => $ids];
    match ($attack) {
        'missing' => $payload['option_ids'] = [],
        'excessive' => $payload['option_ids'] = $group->opciones->pluck('id')->all(),
        'foreign' => $payload['option_ids'] = [Fixture::group(Fixture::product('Otro'), 'Otro grupo', ['Ajena' => 0])->opciones->first()->id],
        'inactive' => $group->opciones->first()->update(['activo' => false]),
        'inactive-group' => $group->update(['activo' => false]),
        'duplicate' => $payload['option_ids'] = [$ids[0], $ids[0]],
        'malformed' => $payload['option_ids'] = 'invalid',
        'removal' => $payload['removed_base_ids'] = [99999],
    };
    $this->postJson(route('cart.quote'), $payload)->assertUnprocessable();
    $this->postJson(route('cart.add'), $payload)->assertUnprocessable();
    expect(session('cart', []))->toBe([])->and(Order::count())->toBe(0);
})->with(['missing', 'excessive', 'foreign', 'inactive', 'inactive-group', 'duplicate', 'malformed', 'removal']);

test('neutral errors retain a stable domain reason', function () {
    $product = Fixture::product('Producto requerido', 1000, true);
    Fixture::group($product, 'Formato', ['A' => 0, 'B' => 0]);
    try {
        app(ProductConfigurationService::class)->quote(app(ProductCatalog::class)->definition($product), new ProductConfigurationInput);
        $this->fail('Expected required selection');
    } catch (ConfigurationException $e) {
        expect($e->reason)->toBe('required_selection')->and($e->context['group'])->toBe('Formato');
    }
});

test('typed neutral snapshots preserve selections without current catalog relations', function () {
    [$product, $ids] = neutralCommerceScenario('burger');
    $definition = app(ProductCatalog::class)->definition($product);
    $quote = app(ProductConfigurationService::class)->quote($definition,
        ProductConfigurationInput::fromArray(['option_ids' => $ids]));
    $snapshot = app(CoreSnapshots::class)->fromQuote($definition, $quote);
    expect($snapshot['version'])->toBe(2)->and($snapshot['amounts']['final_unit'])->toBe('9500.00')
        ->and($snapshot['selections']['groups'][0]['name'])->toBe('Tipo de carne');
    $product->update(['nombre' => 'Cambio posterior', 'precio' => 20000]);
    expect($snapshot['product']['name'])->toBe('Hamburguesa Doble')
        ->and($snapshot['selections']['groups'][0]['options'][0]['name'])->toBe('Vacuno');
});

test('quantity outside the server limit never creates a cart line', function (int $quantity) {
    $product = Fixture::product();
    $this->postJson(route('cart.add'), ['product_id' => $product->id, 'unidades' => $quantity])->assertUnprocessable();
    expect(session('cart', []))->toBe([]);
})->with([0, 51]);

test('optional groups and different choices keep independent deterministic cart lines', function () {
    $product = Fixture::product('Artículo', 1000, true);
    $group = Fixture::group($product, 'Complementos', ['A' => 100, 'B' => 200], 0, 3);
    $this->postJson(route('cart.add'), ['product_id' => $product->id, 'unidades' => 1])->assertOk();
    $this->postJson(route('cart.add'), ['product_id' => $product->id, 'unidades' => 1,
        'option_ids' => Fixture::selected($group, 'A')])->assertOk();
    expect(session('cart'))->toHaveCount(2);
});

test('configuration blocks accept neutral units and do not require homogeneous content', function () {
    $product = Fixture::product('Pack configurable', 5000, true);
    $group = Fixture::group($product, 'Artículo', ['Uno' => 0, 'Dos' => 500]);
    $product->gruposOpciones()->detach();
    $blocks = collect(['Principal', 'Acompañamiento'])->map(function ($name, $i) use ($product, $group) {
        $block = BloquePromocion::create(['producto_id' => $product->id, 'nombre' => $name,
            'unidad_tipo' => 'unidad', 'cantidad_piezas' => null, 'cantidad_unidades' => $i + 1, 'orden' => $i + 1]);
        $block->gruposOpciones()->attach($group);

        return $block;
    });
    $input = ['blocks' => $blocks->map(fn ($b) => ['block_id' => $b->id,
        'option_ids' => Fixture::selected($group, 'Uno')])->all()];
    $quote = app(ProductConfigurationService::class)->quote(app(ProductCatalog::class)->definition($product),
        ProductConfigurationInput::fromArray($input));
    expect($quote->total())->toBe(500000)->and($quote->details['blocks'][0]['name'])->toBe('Principal')
        ->and($blocks->every(fn ($b) => $b->cantidad_piezas === null))->toBeTrue();
});

test('option based exclusions and incompatibilities use ids regardless of labels', function (string $type) {
    $product = Fixture::product('Artículo con reglas', 1000, true);
    $group = Fixture::group($product, 'Complementos', ['A' => 0, 'B' => 0], 0, 2);
    $ids = $group->opciones->pluck('id')->all();
    ReglaPromocion::create(['producto_id' => $product->id, 'codigo' => 'restriction', 'tipo' => $type,
        'opcion_id' => $ids[0], 'valor' => 0, 'activo' => true, 'metadata' => ['option_ids' => [$ids[1]]]]);
    $this->postJson(route('cart.quote'), ['product_id' => $product->id, 'option_ids' => $ids])->assertUnprocessable();
    $this->postJson(route('cart.quote'), ['product_id' => $product->id, 'option_ids' => [$ids[1]]])->assertOk();
})->with(['excluir', 'incompatible']);

test('multiple extras enforce their configured maximum independently of ingredient entities', function () {
    $product = Fixture::product('Artículo con extras', 1000, true);
    $group = Fixture::group($product, 'Extras', ['A' => 100, 'B' => 200, 'C' => 300, 'D' => 400], 0, 3);
    $ids = $group->opciones->pluck('id')->all();
    $this->postJson(route('cart.quote'), ['product_id' => $product->id, 'option_ids' => array_slice($ids, 0, 3)])
        ->assertOk()->assertJsonPath('precio_unit', 1600);
    $this->postJson(route('cart.quote'), ['product_id' => $product->id, 'option_ids' => $ids])->assertUnprocessable();
});

test('option repetition across neutral blocks is a rule by id and never by display name', function () {
    $product = Fixture::product('Pack con elecciones', 5000, true);
    $group = Fixture::group($product, 'Formato', ['A' => 0, 'B' => 0]);
    $product->gruposOpciones()->detach();
    $blocks = collect(['Primero', 'Segundo'])->map(function ($name, $i) use ($product, $group) {
        $block = BloquePromocion::create(['producto_id' => $product->id, 'nombre' => $name,
            'unidad_tipo' => 'unidad', 'cantidad_piezas' => null, 'cantidad_unidades' => 1, 'orden' => $i + 1]);
        $block->gruposOpciones()->attach($group);

        return $block;
    });
    $option = $group->opciones->first();
    ReglaPromocion::create(['producto_id' => $product->id, 'codigo' => 'limit-a',
        'tipo' => 'max_repeticiones', 'opcion_id' => $option->id, 'valor' => 1, 'activo' => true]);
    $option->update(['nombre' => 'Etiqueta nueva']);
    $payload = ['product_id' => $product->id, 'blocks' => $blocks->map(fn ($b) => [
        'block_id' => $b->id, 'option_ids' => [$option->id],
    ])->all()];
    $this->postJson(route('cart.quote'), $payload)->assertUnprocessable()->assertSee('1 vez');
    $payload['blocks'][1]['option_ids'] = [$group->opciones->last()->id];
    $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('precio_unit', 5000);
});
