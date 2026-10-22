<?php

use App\Enums\UnidadComercial;
use App\Models\GrupoOpcion;
use App\Models\Ingrediente;
use App\Models\Order;
use App\Models\Producto;
use App\Services\MenuMoney;
use App\Services\MercadoPagoService;
use App\Services\OrderItemSnapshotService;
use App\Services\ProductPricingService;
use Database\Seeders\SushiAkilaMenuBatch01Seeder;
use Database\Seeders\SushiAkilaMenuBatch02Seeder;
use Database\Seeders\SushiAkilaMenuBatch03Seeder;
use Database\Seeders\SushiAkilaMenuSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

function batch03Option(string $group, string $ingredient): int
{
    return GrupoOpcion::where('slug', $group)->sole()->opciones()->where('slug', $ingredient)->sole()->id;
}

function batch03Payload(string $slug, array $choices): array
{
    $product = Producto::where('slug', $slug)->sole();
    $payload = ['product_id' => $product->id, 'unidades' => 1];
    if ($product->bloques->isNotEmpty()) {
        $payload['blocks'] = $product->bloques->values()->map(fn ($block, $index) => [
            'block_id' => $block->id,
            'option_ids' => isset($choices[$index]) ? [batch03Option('relleno-hand-roll', $choices[$index])] : [],
        ])->all();
    } else {
        $groups = $product->gruposOpciones;
        $payload['option_ids'] = [];
        foreach ($choices as $index => $choice) {
            $group = $groups->count() === 1 ? $groups->first() : $groups[$index];
            $payload['option_ids'][] = batch03Option($group->slug, $choice);
        }
    }

    return $payload;
}

function batch03State(): array
{
    $state = [];
    foreach (['categorias', 'ingredientes', 'productos', 'promociones', 'ingrediente_producto', 'grupos_opciones',
        'opciones', 'grupo_opcion_producto', 'bloques_promocion', 'bloque_grupo_opcion', 'reglas_promocion', 'users', 'orders', 'order_items'] as $table) {
        $state[$table] = DB::table($table)->orderBy('id')->get()->map(function ($row) {
            $values = (array) $row;
            unset($values['created_at'], $values['updated_at']);

            return $values;
        })->all();
    }

    return $state;
}

test('batch03 imports exactly eight new products and confirms prior fifty without changing ids', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    $ids = Producto::pluck('id', 'slug')->all();
    $this->artisan('db:seed', ['--class' => SushiAkilaMenuBatch03Seeder::class])->assertExitCode(0);
    foreach ($ids as $slug => $id) {
        expect(Producto::where('slug', $slug)->sole()->id)->toBe($id);
    }
    $expected = ['las-mismas-de-siempre' => [27900, 80], 'muchupleto' => [6500, 1],
        'hand-roll-tradicional' => [3990, 1], 'hand-roll-relleno-doble' => [4990, 1],
        '3-hand-rolls' => [11000, 3], '4-hand-rolls' => [14990, 4], '5-hand-rolls' => [18000, 5],
        '4-hand-rolls-pollo-palta' => [18000, 4], '50-piezas-a-eleccion' => [20990, 50]];
    foreach ($expected as $slug => [$price, $units]) {
        $product = Producto::where('slug', $slug)->sole();
        expect(MenuMoney::cents($product->precio))->toBe($price * 100)->and($product->unidades)->toBe($units);
    }
    expect(Producto::count())->toBe(28)->and(Producto::distinct()->count('slug'))->toBe(28)
        ->and(DB::table('categorias')->count())->toBe(4)->and(Ingrediente::count())->toBe(15)
        ->and(Ingrediente::where('nombre', 'Pepino')->count())->toBe(1)
        ->and(Ingrediente::whereIn('nombre', ['Carne', 'Frito', 'Primavera', 'Hand Roll', 'Relleno doble'])->count())->toBe(0);
    expect(Producto::whereIn('slug', ['nikkei', 'akilaburger', 'gohan', 'arrollados'])->count())->toBe(0);
});

test('batch03 principal and standalone reruns preserve every identity value and source priority', function () {
    $this->seed(SushiAkilaMenuSeeder::class);
    $state = batch03State();
    $fifty = Producto::where('slug', '50-piezas-a-eleccion')->sole();
    $this->travel(2)->minutes();
    $this->seed(SushiAkilaMenuBatch03Seeder::class);
    expect(batch03State())->toBe($state);
    $this->seed(SushiAkilaMenuSeeder::class);
    expect(batch03State())->toBe($state)->and($fifty->fresh()->id)->toBe($fifty->id)
        ->and($fifty->fresh()->bloques->first()->metadata['foto'])->toBe(30)
        ->and(MenuMoney::cents(Producto::where('slug', 'promo-hot')->sole()->precio))->toBe(1999000);
});

test('batch03 hand rolls store units without artificial pieces and product groups need no blocks', function () {
    $this->seed(SushiAkilaMenuSeeder::class);
    foreach (['3-hand-rolls' => 3, '4-hand-rolls' => 4, '5-hand-rolls' => 5, '4-hand-rolls-pollo-palta' => 4] as $slug => $units) {
        $product = Producto::where('slug', $slug)->sole();
        expect($product->unidad_tipo)->toBe(UnidadComercial::HandRoll)->and($product->bloques)->toHaveCount($units)
            ->and($product->bloques->sum('cantidad_unidades'))->toBe($units);
        foreach ($product->bloques as $block) {
            expect($block->cantidad_piezas)->toBeNull()->and($block->cantidad_unidades)->toBe(1);
        }
        $this->get(route('producto.modal', $product->id))->assertOk()->assertSee('Hand Roll '.$units)->assertDontSee('10 piezas');
    }
    foreach (['muchupleto', 'hand-roll-tradicional', 'hand-roll-relleno-doble'] as $slug) {
        expect(Producto::where('slug', $slug)->sole()->bloques)->toHaveCount(0);
    }
    $product = Producto::where('slug', 'muchupleto')->sole();
    expect($product->ingredientes->pluck('nombre')->sort()->values()->all())->toBe(['Cebollín', 'Palta', 'Pollo', 'Queso crema']);
    foreach ($product->ingredientes as $ingredient) {
        expect($ingredient->pivot->reemplazable)->toBeFalse()->and($ingredient->pivot->cantidad)->toBeNull();
    }
});

test('batch03 commercial individual selections use exact server prices', function (string $slug, array $choices, int $price) {
    $this->seed(SushiAkilaMenuSeeder::class);
    $payload = batch03Payload($slug, $choices) + ['price' => 1, 'total' => 1, 'extra_price' => -1000, 'discount' => 99999];
    $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('precio_unit', $price);
    $this->postJson(route('cart.add'), $payload)->assertOk();
    expect(array_values(session('cart'))[0]['precio_unit'])->toEqual($price);
})->with([
    ['hand-roll-tradicional', ['pollo'], 3990], ['hand-roll-tradicional', ['palta'], 3990],
    ['hand-roll-tradicional', ['salmon'], 4990], ['hand-roll-tradicional', ['atun'], 4990],
    ['hand-roll-relleno-doble', ['pollo', 'palta'], 4990], ['hand-roll-relleno-doble', ['camaron', 'champinon'], 4990],
    ['hand-roll-relleno-doble', ['salmon', 'pepino'], 4990], ['hand-roll-relleno-doble', ['atun', 'morron'], 4990],
    ['muchupleto', ['kanikama'], 6500], ['muchupleto', ['champinon'], 6500], ['muchupleto', ['salmon'], 6500],
]);

test('batch03 commercial individuals reject missing excessive wrong type and foreign options', function (string $slug, array $groups, array $choices) {
    $this->seed(SushiAkilaMenuSeeder::class);
    $product = Producto::where('slug', $slug)->sole();
    $payload = ['product_id' => $product->id, 'unidades' => 1,
        'option_ids' => array_map(fn ($group, $choice) => batch03Option($group, $choice), $groups, $choices)];
    foreach (['cart.quote', 'cart.add'] as $route) {
        $this->postJson(route($route), $payload)->assertUnprocessable();
    }
})->with([
    ['hand-roll-tradicional', [], []],
    ['hand-roll-tradicional', ['relleno-hand-roll', 'relleno-hand-roll'], ['pollo', 'camaron']],
    ['hand-roll-relleno-doble', ['proteina'], ['pollo']],
    ['hand-roll-relleno-doble', ['vegetal'], ['palta']],
    ['hand-roll-relleno-doble', ['proteina', 'proteina'], ['pollo', 'camaron']],
    ['hand-roll-relleno-doble', ['vegetal', 'vegetal'], ['palta', 'champinon']],
    ['muchupleto', [], []],
    ['muchupleto', ['relleno-muchupleto', 'relleno-muchupleto'], ['kanikama', 'champinon']],
    ['muchupleto', ['relleno-hand-roll'], ['pepino']],
]);

test('batch03 commercial promotions validate all fillings including optional ingredients', function (string $slug, array $choices, bool $valid) {
    $this->seed(SushiAkilaMenuSeeder::class);
    $response = $this->postJson(route('cart.quote'), batch03Payload($slug, $choices));
    $valid ? $response->assertOk() : $response->assertUnprocessable();
    if ($valid) {
        $response->assertJsonPath('recargo', 0);
    }
})->with([
    ['3-hand-rolls', ['pollo', 'pollo', 'camaron'], true],
    ['3-hand-rolls', ['salmon', 'atun', 'pepino'], true],
    ['3-hand-rolls', ['pollo', 'pollo', 'pollo'], false],
    ['3-hand-rolls', ['salmon', 'salmon', 'pollo'], false],
    ['3-hand-rolls', ['atun', 'atun', 'pollo'], false],
    ['4-hand-rolls', ['salmon', 'atun', 'pollo', 'palta'], true],
    ['5-hand-rolls', ['pollo', 'pollo', 'camaron', 'camaron', 'palta'], true],
    ['5-hand-rolls', ['pollo', 'pollo', 'pollo', 'camaron', 'palta'], false],
    ['5-hand-rolls', ['salmon', 'salmon', 'pollo', 'camaron', 'palta'], false],
    ['5-hand-rolls', ['atun', 'atun', 'pollo', 'camaron', 'palta'], false],
]);

test('batch03 second ingredient is scoped per unit priced exactly and protected from tampering', function () {
    $this->seed(SushiAkilaMenuSeeder::class);
    $payload = batch03Payload('5-hand-rolls', ['pollo', 'pollo', 'camaron', 'camaron', 'palta']);
    $extra = batch03Option('segundo-ingrediente', 'champinon');
    $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('precio_unit', 18000);
    $payload['blocks'][0]['option_ids'][] = $extra;
    $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('precio_unit', 19000);
    $payload['blocks'][1]['option_ids'][] = $extra;
    $payload += ['total' => 1, 'extra_price' => 0];
    $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('precio_unit', 20000);
    $payload['unidades'] = 2;
    $this->postJson(route('cart.add'), $payload)->assertOk();
    $line = array_values(session('cart'))[0];
    expect($line['precio_unit'])->toEqual(20000)->and($line['total'])->toEqual(40000)->and($line['charges'])->toHaveCount(2);
    $payload['blocks'][2]['option_ids'][] = $extra;
    $this->postJson(route('cart.quote'), $payload)->assertUnprocessable();
    $payload = batch03Payload('5-hand-rolls', ['pollo', 'pollo', 'camaron', 'camaron', 'palta']);
    $payload['blocks'][0]['option_ids'][] = batch03Option('segundo-ingrediente', 'pollo');
    $this->postJson(route('cart.quote'), $payload)->assertUnprocessable();
    $payload = batch03Payload('3-hand-rolls', ['pollo', 'pollo', 'camaron']);
    $payload['blocks'][0]['option_ids'][] = $extra;
    $this->postJson(route('cart.quote'), $payload)->assertUnprocessable();
    $payload['blocks'][0]['option_ids'] = [999999];
    $this->postJson(route('cart.quote'), $payload)->assertUnprocessable();
    $payload = batch03Payload('3-hand-rolls', ['pollo', 'pollo', 'camaron']);
    $payload['blocks'][0]['block_id'] = Producto::where('slug', '4-hand-rolls')->sole()->bloques->first()->id;
    $this->postJson(route('cart.quote'), $payload)->assertUnprocessable();
});

test('batch03 fixed products quote automatically and all historical details keep commercial units', function () {
    $this->seed(SushiAkilaMenuSeeder::class);
    foreach (['las-mismas-de-siempre' => [27900, 8], '4-hand-rolls-pollo-palta' => [18000, 4]] as $slug => [$price, $blocks]) {
        $product = Producto::where('slug', $slug)->sole();
        $quote = app(ProductPricingService::class)->quote($product, []);
        expect($quote['precio_unit'])->toEqual($price)->and($quote['configuration']['blocks'])->toHaveCount($blocks);
        $snapshot = app(OrderItemSnapshotService::class)->fromQuote($product, $quote);
        expect($snapshot['product']['unit_type'])->toBe($product->unidad_tipo->value);
        if ($slug === 'las-mismas-de-siempre') {
            expect($product->reglas()->where('codigo', 'piezas-frio')->sole()->valor)->toBe('40.00')
                ->and($product->reglas()->where('codigo', 'piezas-frito')->sole()->valor)->toBe('40.00');
        } else {
            foreach ($snapshot['selections']['Bloques'] as $block) {
                expect($block['piezas'])->toBeNull()->and($block['cantidad'])->toBe(1)
                    ->and(collect($block['grupos'])->flatMap(fn ($g) => array_column($g['opciones'], 'nombre'))->all())->toBe(['Pollo', 'Palta']);
            }
        }
    }
});

test('batch03 cart separates configurations checkout preserves snapshots and current catalog cannot alter history', function () {
    $this->seed(SushiAkilaMenuSeeder::class);
    $a = batch03Payload('3-hand-rolls', ['pollo', 'kanikama', 'palta']);
    $b = batch03Payload('3-hand-rolls', ['camaron', 'champinon', 'pollo']);
    $this->postJson(route('cart.add'), $a)->assertOk();
    $this->postJson(route('cart.add'), $b)->assertOk();
    expect(session('cart'))->toHaveCount(2);
    $this->postJson(route('cart.add'), batch03Payload('muchupleto', ['kanikama']))->assertOk();
    $gateway = Mockery::mock(MercadoPagoService::class);
    $gateway->shouldReceive('createPreference')->once()->andReturn('https://example.test/batch03');
    $this->app->instance(MercadoPagoService::class, $gateway);
    $this->post(route('checkout.store'), ['cliente_nombre' => 'Prueba', 'cliente_telefono' => '123', 'metodo_entrega' => 'pickup'])->assertRedirect('https://example.test/batch03');
    $order = Order::sole();
    expect($order->total)->toBe('28500.00');
    $item = $order->items()->where('product_id', $a['product_id'])->first();
    $snapshot = $item->snapshot;
    expect($snapshot['version'])->toBe(2)->and($snapshot['configuration']['blocks'][0]['unit_type'])->toBe('hand_roll')
        ->and($snapshot['configuration']['blocks'][0]['pieces'])->toBeNull();
    Producto::findOrFail($a['product_id'])->update(['precio' => 99999]);
    expect($item->fresh()->snapshot)->toBe($snapshot);
    expect(view('pdf.invoice', ['order' => $order->load('items')])->render())->toContain('Hand Roll 1', 'Kanikama');
});

test('batch03 structural validation rejects false piece quantities and unsafe schema rollback', function () {
    $this->seed(SushiAkilaMenuSeeder::class);
    $product = Producto::where('slug', '3-hand-rolls')->sole();
    expect(fn () => $product->bloques()->create(['nombre' => 'Inválido', 'unidad_tipo' => 'hand_roll', 'cantidad_piezas' => 10, 'cantidad_unidades' => 1, 'orden' => 99]))->toThrow(ValidationException::class);
    $migration = require database_path('migrations/2026_10_22_000100_add_commercial_units_to_catalog.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
    expect(Schema::hasColumn('productos', 'unidad_tipo'))->toBeTrue();
});

test('batch03 unit migration reverses and reapplies with legacy data in isolated memory', function () {
    $original = DB::getDefaultConnection();
    config(['database.connections.batch03_isolated' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
    DB::setDefaultConnection('batch03_isolated');
    try {
        Schema::create('productos', fn (Blueprint $t) => $t->id());
        Schema::create('bloques_promocion', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('cantidad_piezas')->default(10);
        });
        DB::table('productos')->insert(['id' => 1]);
        DB::table('bloques_promocion')->insert(['id' => 1, 'cantidad_piezas' => 10]);
        $migration = require database_path('migrations/2026_10_22_000100_add_commercial_units_to_catalog.php');
        $migration->up();
        expect(DB::table('productos')->value('unidad_tipo'))->toBe('pieza');
        $migration->down();
        expect(Schema::hasColumn('productos', 'unidad_tipo'))->toBeFalse()->and(DB::table('bloques_promocion')->value('cantidad_piezas'))->toBe(10);
        $migration->up();
        expect(DB::table('productos')->count())->toBe(1)->and(DB::table('bloques_promocion')->value('cantidad_piezas'))->toBe(10);
    } finally {
        DB::setDefaultConnection($original);
        DB::purge('batch03_isolated');
    }
});
