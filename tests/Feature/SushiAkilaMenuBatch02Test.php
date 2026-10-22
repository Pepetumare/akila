<?php

use App\Enums\TipoReglaPromocion;
use App\Models\GrupoOpcion;
use App\Models\Ingrediente;
use App\Models\Order;
use App\Models\Producto;
use App\Models\User;
use App\Services\MenuMoney;
use App\Services\MercadoPagoService;
use App\Services\OrderItemSnapshotService;
use App\Services\ProductPricingService;
use Database\Seeders\SushiAkila\Batch02Data;
use Database\Seeders\SushiAkilaMenuBatch01Seeder;
use Database\Seeders\SushiAkilaMenuBatch02Seeder;
use Illuminate\Support\Facades\DB;

function batch02State(): array
{
    $state = [];
    foreach (['categorias', 'ingredientes', 'productos', 'promociones', 'ingrediente_producto',
        'grupos_opciones', 'opciones', 'grupo_opcion_producto', 'bloques_promocion', 'bloque_grupo_opcion',
        'reglas_promocion', 'users', 'orders', 'order_items'] as $table) {
        $state[$table] = DB::table($table)->orderBy('id')->get()->map(function ($row) {
            $attributes = (array) $row;
            unset($attributes['created_at'], $attributes['updated_at']);

            return $attributes;
        })->all();
    }

    return $state;
}

function batch02Payload(Producto $product, array $slugs): array
{
    $group = GrupoOpcion::where('slug', 'relleno-promociones-a-eleccion')->sole();
    $options = $group->opciones->keyBy('slug');

    return ['product_id' => $product->id, 'unidades' => 1, 'version' => 2,
        'blocks' => $product->bloques->values()->map(fn ($block, $index) => [
            'block_id' => $block->id, 'option_ids' => [$options[$slugs[$index]]->id],
        ])->all()];
}

test('batch02 updates existing identities and adds exactly its eight sourced products', function () {
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    $identities = Producto::pluck('id', 'slug')->all();
    expect(MenuMoney::cents(Producto::where('slug', 'promo-hot')->sole()->precio))->toBe(2099000);
    $this->artisan('db:seed', ['--class' => SushiAkilaMenuBatch02Seeder::class])->assertExitCode(0);
    foreach ($identities as $slug => $id) {
        expect(Producto::where('slug', $slug)->sole()->id)->toBe($id);
    }
    $expected = ['promo-hot' => [19990, 60], 'el-ciclon' => [24990, 60],
        '60-piezas-a-eleccion' => [25990, 60], '50-piezas-a-eleccion' => [20990, 50],
        'noche-de-sushi' => [17990, 40], 'tu-media-naranja' => [17990, 40],
        '40-piezas-a-eleccion' => [17900, 40], 'el-emperador' => [49990, 150],
        'para-la-familia' => [36990, 100], 'de-las-que-no-sobran' => [31990, 90]];
    foreach ($expected as $slug => [$price, $pieces]) {
        $product = Producto::where('slug', $slug)->sole();
        expect(MenuMoney::cents($product->precio))->toBe($price * 100)->and(MenuMoney::cents($product->promocion->precio))->toBe($price * 100)
            ->and($product->unidades)->toBe($pieces)->and($product->bloques->sum('cantidad_piezas'))->toBe($pieces);
    }
    expect(Producto::count())->toBe(20)->and(Producto::distinct()->count('slug'))->toBe(20)
        ->and(Ingrediente::count())->toBe(14)->and(DB::table('categorias')->count())->toBe(2)
        ->and(MenuMoney::cents(Producto::where('slug', 'promo-hot')->sole()->precio))->toBe(1999000)
        ->and(MenuMoney::cents(Producto::where('slug', 'noche-de-sushi')->sole()->precio))->toBe(1799000);
});

test('batch02 second standalone execution preserves all identities values and counts', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    $before = batch02State();
    $this->travel(2)->minutes();
    $this->seed(SushiAkilaMenuBatch02Seeder::class);
    expect(batch02State())->toBe($before);
});

test('batch02 scoped composition runs in source order and is fully idempotent including final hot price', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    $before = batch02State();
    $hot = Producto::where('slug', 'promo-hot')->sole();
    $this->travel(2)->minutes();
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    expect(batch02State())->toBe($before)->and(MenuMoney::cents($hot->fresh()->precio))->toBe(1999000);
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    expect(MenuMoney::cents($hot->fresh()->precio))->toBe(2099000)->and($hot->fresh()->id)->toBe($hot->id);
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    expect(batch02State())->toBe($before)->and(MenuMoney::cents($hot->fresh()->precio))->toBe(1999000);
});

test('batch02 choice groups have independent rolls restricted fillings and actual wrapper availability', function (string $slug, int $blocks, string $price, int $wrappers) {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    $product = Producto::where('slug', $slug)->sole();
    expect($product->personalizable)->toBeTrue()->and(MenuMoney::cents($product->precio))->toBe(MenuMoney::cents($price))
        ->and($product->bloques)->toHaveCount($blocks);
    foreach ($product->bloques as $block) {
        expect($block->cantidad_piezas)->toBe(10);
        $filling = $block->gruposOpciones->firstWhere('slug', 'relleno-promociones-a-eleccion');
        expect($filling->limites($filling->pivot))->toBe([1, 1])
            ->and($filling->opciones->pluck('slug')->sort()->values()->all())->toBe([
                'camaron', 'champinon', 'choclo', 'kanikama', 'palmito', 'palta', 'pollo', 'salmon',
            ]);
        $wrapper = $block->gruposOpciones->firstWhere('slug', 'envoltura-promociones-mixtas');
        expect($wrapper?->opciones->count() ?? 0)->toBe($wrappers);
        if ($wrapper) {
            expect($wrapper->opciones->pluck('slug')->sort()->values()->all())->toBe(['palta', 'queso-crema', 'sesamo'])
                ->and($wrapper->limites($wrapper->pivot))->toBe([0, 1]);
        }
    }
    expect($product->reglas->where('tipo', TipoReglaPromocion::MaxRepeticiones))->toHaveCount(8);
    $this->postJson(route('cart.add'), ['product_id' => $product->id, 'unidades' => 1])->assertUnprocessable();
    $this->get(route('producto.modal', $product->id))->assertOk()->assertSee('Roll '.$blocks)->assertDontSee('Roll '.($blocks + 1));
})->with([
    ['40-piezas-a-eleccion', 4, '17900.00', 0],
    ['50-piezas-a-eleccion', 5, '20990.00', 3],
    ['60-piezas-a-eleccion', 6, '25990.00', 3],
]);

test('batch02 fixed compositions preserve exact filling counts and do not open selectors', function (string $slug) {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    $data = Batch02Data::products()[$slug];
    $product = Producto::where('slug', $slug)->sole();
    $this->postJson(route('cart.add'), ['product_id' => $product->id, 'unidades' => 1])->assertOk();
    $line = array_values(session('cart'))[0];
    expect($line['precio_unit'])->toEqual((float) $data['precio'])->and($product->personalizable)->toBeFalse()
        ->and($line['configuration']['blocks'])->toHaveCount(count($data['bloques']));
    foreach ($data['bloques'] as $index => $block) {
        $groups = collect($line['detalle']['Bloques'][$index]['grupos']);
        $names = $groups->flatMap(fn ($g) => array_column($g['opciones'], 'nombre'))->all();
        expect($names)->toBe(array_merge($block['relleno'], isset($block['envoltura']) ? [$block['envoltura']] : []));
    }
    $snapshot = app(OrderItemSnapshotService::class)->fromQuote($product, $line);
    expect($snapshot['version'])->toBe(2)->and($snapshot['selections']['Bloques'])->toBe($line['detalle']['Bloques']);
    $this->get(route('producto.modal', $product->id))->assertOk()->assertDontSee('data-menu-option', false);
})->with(['promo-hot', 'noche-de-sushi', 'el-ciclon', 'tu-media-naranja', 'el-emperador', 'para-la-familia', 'de-las-que-no-sobran']);

test('batch02 global preparations are exact without invented per block assignments or physical ingredients', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    foreach (Batch02Data::products() as $slug => $data) {
        $product = Producto::where('slug', $slug)->sole();
        $actual = $product->reglas->where('activo', true)->where('tipo', TipoReglaPromocion::CantidadIncluida)->pluck('valor', 'codigo')->all();
        $expected = collect($data['preparacion'])->mapWithKeys(fn ($value, $name) => ['piezas-'.$name => number_format($value, 2, '.', '')])->all();
        ksort($actual);
        ksort($expected);
        expect($actual)->toBe($expected);
        if (! ($data['personalizable'] ?? false) && ! in_array($slug, ['promo-hot', 'tu-media-naranja'], true)) {
            foreach ($product->bloques as $block) {
                expect($block->gruposOpciones->contains(fn ($g) => str_starts_with($g->slug, 'envoltura')))->toBeFalse();
            }
        }
    }
    $noche = Producto::where('slug', 'noche-de-sushi')->sole();
    expect($noche->reglas()->where('codigo', 'piezas-envuelto')->sole()->activo)->toBeFalse();
    expect(DB::table('ingrediente_producto')->whereNotNull('cantidad')->count())->toBe(0)
        ->and(DB::table('ingrediente_producto')->whereNotNull('unidad')->count())->toBe(0)
        ->and(Ingrediente::whereIn('nombre', ['Frito', 'Frío', 'Pollo frito', 'Palta envuelta'])->count())->toBe(0);
    expect(Producto::whereIn('slug', ['las-mismas-de-siempre', 'muchupleto', 'hand-roll-tradicional', 'nikkei', 'akilaburger', 'arrollados', 'gohan'])->count())->toBe(0);
});

test('batch02 commercial forty validates repetitions salmon and foreign ids with server prices', function (string $scenario) {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    $product = Producto::where('slug', '40-piezas-a-eleccion')->sole();
    $payload = batch02Payload($product, match ($scenario) {
        'repetition' => ['pollo', 'pollo', 'pollo', 'palta'],
        'salmon' => ['salmon', 'salmon', 'pollo', 'palta'],
        default => ['pollo', 'camaron', 'pollo', 'palta'],
    });
    if ($scenario === 'foreign-block') {
        $payload['blocks'][0]['block_id'] = Producto::where('slug', '60-piezas-a-eleccion')->sole()->bloques->first()->id;
    }
    if ($scenario === 'foreign-option') {
        $payload['blocks'][0]['option_ids'] = [GrupoOpcion::where('slug', 'proteina')->sole()->opciones->first()->id];
    }
    $payload += ['price' => 1, 'total' => 1, 'extra_price' => -1000, 'discount' => 999999];
    foreach (['cart.quote', 'cart.add'] as $route) {
        $response = $this->postJson(route($route), $payload);
        if ($scenario === 'valid') {
            $response->assertOk();
            if ($route === 'cart.quote') {
                $response->assertJsonPath('precio_unit', 17900)->assertJsonPath('recargo', 0);
            }
        } else {
            $response->assertUnprocessable();
        }
    }
})->with(['valid', 'repetition', 'salmon', 'foreign-block', 'foreign-option']);

test('batch02 selected wrappers are not counted as filling repetitions', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    $product = Producto::where('slug', '60-piezas-a-eleccion')->sole();
    $payload = batch02Payload($product, ['palta', 'palta', 'pollo', 'pollo', 'camaron', 'choclo']);
    $wrapper = GrupoOpcion::where('slug', 'envoltura-promociones-mixtas')->sole()->opciones->firstWhere('slug', 'palta');
    foreach ($payload['blocks'] as &$block) {
        $block['option_ids'][] = $wrapper->id;
    }
    unset($block);
    $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('precio_unit', 25990);
});

test('batch02 preserves unrelated data and prior historical prices through principal reruns', function () {
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    $hot = Producto::where('slug', 'promo-hot')->sole();
    $quote = app(ProductPricingService::class)->quote($hot, []);
    $order = Order::create(['cliente_nombre' => 'Histórico', 'cliente_telefono' => '123', 'total' => $quote['total']]);
    $item = $order->items()->create(['product_id' => $hot->id, 'nombre' => $quote['nombre'], 'unidades' => 1,
        'precio_unit' => $quote['precio_unit'], 'total' => $quote['total'], 'detalle' => $quote['detalle'],
        'snapshot' => app(OrderItemSnapshotService::class)->fromQuote($hot, $quote)]);
    $snapshot = $item->snapshot;
    $user = User::factory()->create();
    $outside = Producto::create(['nombre' => 'Producto ajeno', 'descripcion' => 'Conservar', 'precio' => 123, 'categoria_id' => $hot->categoria_id]);
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    expect($item->fresh()->snapshot)->toBe($snapshot)->and($item->fresh()->precio_unit)->toBe('20990.00')
        ->and(MenuMoney::cents($hot->fresh()->precio))->toBe(1999000)->and($outside->fresh()->descripcion)->toBe('Conservar')
        ->and($user->fresh())->not->toBeNull()->and(Producto::count())->toBe(21);
});

test('batch02 checkout keeps new choice and fixed composition in versioned snapshots', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class]);
    $product = Producto::where('slug', '40-piezas-a-eleccion')->sole();
    $this->postJson(route('cart.add'), batch02Payload($product, ['pollo', 'camaron', 'pollo', 'palta']))->assertOk();
    $fixed = Producto::where('slug', 'el-emperador')->sole();
    $this->postJson(route('cart.add'), ['product_id' => $fixed->id, 'unidades' => 1])->assertOk();
    $gateway = Mockery::mock(MercadoPagoService::class);
    $gateway->shouldReceive('createPreference')->once()->andReturn('https://example.test/batch02');
    $this->app->instance(MercadoPagoService::class, $gateway);
    $this->post(route('checkout.store'), ['cliente_nombre' => 'Prueba', 'cliente_telefono' => '123', 'metodo_entrega' => 'pickup'])
        ->assertRedirect('https://example.test/batch02');
    $order = Order::sole();
    expect($order->total)->toBe('67890.00')->and($order->items)->toHaveCount(2);
    foreach ($order->items as $item) {
        expect($item->snapshot['version'])->toBe(2)->and($item->snapshot['configuration']['blocks'])->toHaveCount($item->product_id === $fixed->id ? 15 : 4);
    }
});
