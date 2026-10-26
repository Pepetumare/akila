<?php

use App\Models\GrupoOpcion;
use App\Models\Ingrediente;
use App\Models\Producto;
use App\Services\MenuMoney;
use App\Services\OrderItemSnapshotService;
use App\Services\ProductPricingService;
use Database\Seeders\SushiAkila\Batch04Data;
use Database\Seeders\SushiAkila\MenuIdentity;
use Database\Seeders\SushiAkilaMenuBatch01Seeder;
use Database\Seeders\SushiAkilaMenuBatch02Seeder;
use Database\Seeders\SushiAkilaMenuBatch03Seeder;
use Database\Seeders\SushiAkilaMenuBatch04Seeder;
use Illuminate\Support\Facades\DB;

function batch04Option(string $group, string $name): int
{
    return GrupoOpcion::where('slug', $group)->sole()->opciones()->where('nombre', $name)->sole()->id;
}

function batch04Payload(string $slug, array $choices = []): array
{
    $p = Producto::where('slug', $slug)->sole();
    $input = ['product_id' => $p->id, 'unidades' => 1, 'price' => 1, 'total' => 1, 'discount' => 99999];
    if ($slug === '20-piezas-a-eleccion') {
        $input['blocks'] = $p->bloques->values()->map(fn ($b, $i) => ['block_id' => $b->id,
            'option_ids' => isset($choices[$i]) ? [batch04Option('relleno-20-piezas-a-eleccion', $choices[$i])] : []])->all();
    } elseif ($slug === 'akilaburger') {
        $input['option_ids'] = array_map(fn ($name) => batch04Option('relleno-akilaburger', $name), $choices);
    }

    return $input;
}

function batch04State(): array
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

test('batch04 adds exactly six products and preserves previous identities through both scoped runs', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class]);
    $ids = Producto::pluck('id', 'slug')->all();
    $this->artisan('db:seed', ['--class' => SushiAkilaMenuBatch04Seeder::class])->assertExitCode(0);
    expect(Producto::count())->toBe(34)->and(Ingrediente::count())->toBe(22)
        ->and(DB::table('categorias')->count())->toBe(5)->and(Producto::distinct()->count('slug'))->toBe(34);
    foreach ($ids as $slug => $id) {
        expect(Producto::where('slug', $slug)->sole()->id)->toBe($id);
    }
    $state = batch04State();
    $this->seed(SushiAkilaMenuBatch04Seeder::class);
    expect(batch04State())->toBe($state);
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    expect(batch04State())->toBe($state);
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    expect(batch04State())->toBe($state);
    expect(MenuMoney::cents(Producto::where('slug', 'mi-favorita')->sole()->precio))->toBe(1899000)
        ->and(MenuMoney::cents(Producto::where('slug', 'goloso')->sole()->precio))->toBe(1000000)
        ->and(MenuMoney::cents(Producto::where('slug', 'promo-hot')->sole()->precio))->toBe(1999000);
    expect(Ingrediente::all()->map(fn ($i) => MenuIdentity::ingredientKey($i->nombre))->unique())->toHaveCount(22);
    expect(Producto::whereIn('slug', ['arrollados', 'gohan-flecha', 'gohan-orion', 'delivery', 'extras'])->count())->toBe(0);
});

test('batch04 fixed nikkei costs 6990 with complete snapshots and no premium extra surcharge', function (string $slug, string $protein) {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    $p = Producto::where('slug', $slug)->sole();
    $this->postJson(route('cart.add'), batch04Payload($slug))->assertOk();
    $quote = app(ProductPricingService::class)->quote($p, ['price' => 1, 'total' => 1]);
    expect($quote['precio_unit'])->toEqual(6990)->and($quote['recargo'])->toEqual(0)
        ->and($p->unidades)->toBe(10)->and($p->unidad_tipo->value)->toBe('pieza')
        ->and($p->categoria->slug)->toBe('nikkei')->and($p->bloques)->toHaveCount(0);
    $snapshot = app(OrderItemSnapshotService::class)->fromQuote($p, $quote);
    expect($snapshot['version'])->toBe(2)->and($snapshot['selections']['Base'])->toContain($protein, 'Palta', 'Queso crema');
    expect($p->ingredientes->pluck('nombre')->all())->not->toContain('Cebollín', 'Arroz', 'Nori');
    if ($slug === 'nikkei-jamon-serrano') {
        expect($snapshot['selections']['Base'])->toContain('Jamón serrano');
    }
    if ($slug === 'nikkei-camaron-apanado') {
        expect($p->gruposOpciones->sole()->opciones->sole()->nombre)->toBe('Almendras');
    } else {
        expect($p->gruposOpciones)->toHaveCount(0);
    }
    $this->get(route('producto.modal', $p->id))->assertOk()->assertSee('10 piezas');
})->with([
    ['nikkei-jamon-serrano', 'Pollo apanado'], ['nikkei-pollo-crispy', 'Pollo apanado'],
    ['nikkei-camaron-apanado', 'Camarón apanado'], ['nikkei-salmon-ahumado', 'Salmón ahumado'],
]);

test('batch04 twenty pieces validates choices with server price and two blocks', function (array $choices) {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    $p = Producto::where('slug', '20-piezas-a-eleccion')->sole();
    expect($p->bloques)->toHaveCount(2)->and($p->bloques->sum('cantidad_piezas'))->toBe(20);
    $this->postJson(route('cart.quote'), batch04Payload($p->slug, $choices))->assertOk()->assertJsonPath('precio_unit', 10000)->assertJsonPath('recargo', 0);
})->with([[['Pollo', 'Pollo']], [['Pollo', 'Camarón']], [['Salmón', 'Pollo']]]);

test('batch04 twenty pieces rejects salmon repetition foreign options manipulated blocks and missing choices', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    $this->postJson(route('cart.quote'), batch04Payload('20-piezas-a-eleccion', ['Salmón', 'Salmón']))->assertUnprocessable();
    $payload = batch04Payload('20-piezas-a-eleccion', ['Pollo', 'Pollo']);
    $payload['blocks'][0]['option_ids'] = [batch04Option('relleno-akilaburger', 'Pollo')];
    $this->postJson(route('cart.add'), $payload)->assertUnprocessable();
    $payload = batch04Payload('20-piezas-a-eleccion', ['Pollo', 'Pollo']);
    $payload['blocks'][0]['block_id'] = Producto::where('slug', 'goloso')->sole()->bloques->first()->id;
    $this->postJson(route('cart.quote'), $payload)->assertUnprocessable();
    $this->postJson(route('cart.quote'), batch04Payload('20-piezas-a-eleccion', ['Pollo']))->assertUnprocessable();
    $group = GrupoOpcion::where('slug', 'envoltura-20-piezas-a-eleccion')->sole();
    expect($group->opciones->pluck('nombre')->sort()->values()->all())->toBe(['Frito', 'Palta', 'Queso crema']);
});

test('batch04 burger includes every allowed filling at 7500 without charging ingredient extras', function (string $choice) {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    $this->postJson(route('cart.quote'), batch04Payload('akilaburger', [$choice]))->assertOk()->assertJsonPath('precio_unit', 7500)->assertJsonPath('recargo', 0);
    $p = Producto::where('slug', 'akilaburger')->sole();
    expect($p->bloques)->toHaveCount(0)->and($p->ingredientes->pluck('nombre')->sort()->values()->all())->toBe(['Cebollín', 'Palta', 'Queso crema']);
})->with(['Pollo', 'Salmón', 'Atún', 'Champiñón', 'Palta', 'Camarón', 'Choclo', 'Kanikama', 'Palmito']);

test('batch04 burger requires exactly one choice and cannot remove fixed base', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    $this->postJson(route('cart.quote'), batch04Payload('akilaburger'))->assertUnprocessable();
    $this->postJson(route('cart.quote'), batch04Payload('akilaburger', ['Pollo', 'Salmón']))->assertUnprocessable();
    $p = Producto::where('slug', 'akilaburger')->sole();
    $this->postJson(route('cart.quote'), batch04Payload('akilaburger', ['Pollo']) + ['removed_base_ids' => [$p->ingredientes->first()->id]])->assertUnprocessable();
});

test('batch04 central extras have exact prices but are not globally assigned', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    $group = GrupoOpcion::where('slug', 'ingredientes-extras')->sole();
    $expected = ['Camarón' => '1000.00', 'Pollo' => '1000.00', 'Kanikama' => '1000.00', 'Choclo' => '1000.00',
        'Palmito' => '1000.00', 'Palta' => '1000.00', 'Champiñón' => '1000.00', 'Salmón' => '2000.00',
        'Pollo apanado' => '2000.00', 'Camarón apanado' => '2000.00', 'Atún' => '2000.00'];
    expect($group->opciones->pluck('recargo', 'nombre')->sortKeys()->all())->toBe(collect($expected)->sortKeys()->all())->and($group->productos()->count())->toBe(0);
    $this->postJson(route('cart.quote'), batch04Payload('goloso') + ['option_ids' => [$group->opciones->first()->id]])->assertUnprocessable();
    $canonical = Ingrediente::where('nombre', 'Pollo apanado')->sole()->id;
    foreach (['pollo crispy', 'pollo apanada', 'Pollo apanado'] as $alias) {
        expect(MenuIdentity::ingredient($alias, 'proteina')->id)->toBe($canonical);
    }
    expect(MenuIdentity::ingredientKey('Soja'))->toBe(MenuIdentity::ingredientKey('Salsa de soya'));
    expect(Ingrediente::where('nombre', 'Salmón')->sole()->id)->not->toBe(Ingrediente::where('nombre', 'Salmón ahumado')->sole()->id);
});

test('batch04 photo31 detailed sauce counts remain free and cannot be swapped by input', function (int $pieces, array $counts) {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    foreach (Producto::where('tipo', 'promocion')->where('unidad_tipo', 'pieza')->where('unidades', $pieces)->get() as $p) {
        $rules = $p->reglas->filter(fn ($r) => ($r->metadata['uso'] ?? null) === 'salsa_incluida');
        expect($rules->count())->toBe(2);
        foreach (array_combine(['Salsa de soya', 'Salsa agridulce'], $counts) as $name => $count) {
            expect($rules->firstWhere('ingrediente_id', Ingrediente::where('nombre', $name)->sole()->id)->valor)->toBe(number_format($count, 2, '.', ''));
        }
        foreach ($rules as $r) {
            expect($r->metadata['foto'])->toBe(31)->and($r->metadata['precio'])->toBe('0.00')->and($r->metadata['intercambiable'])->toBeFalse();
        }
    }
    expect(Batch04Data::sauces($pieces))->toBe($counts);
})->with([[20, [1, 1]], [30, [2, 1]], [40, [2, 1]], [50, [2, 1]], [60, [2, 2]], [70, [2, 2]], [80, [4, 3]], [90, [4, 3]], [100, [5, 3]]]);

test('batch04 additional sauces charge server prices without altering included quantities or base', function (array $names, int $extra) {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    $payload = batch04Payload('goloso') + ['option_ids' => array_map(fn ($n) => batch04Option('salsas-adicionales', $n), $names),
        'included_sauces' => ['soya' => 999]];
    $response = $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('precio_base', 10000)
        ->assertJsonPath('recargo', $extra)->assertJsonPath('precio_unit', 10000 + $extra);
    expect(array_column($response->json('detalle.Salsas incluidas'), 'cantidad'))->toBe([1, 1]);
    $p = Producto::where('slug', 'goloso')->sole();
    $snapshot = app(OrderItemSnapshotService::class)->fromQuote($p, $response->json());
    expect($snapshot['selections']['Salsas incluidas'])->toHaveCount(2);
})->with([[[], 0], [['Salsa de soya adicional'], 500], [['Salsa agridulce adicional'], 800], [['Salsa de soya adicional', 'Salsa agridulce adicional'], 1300]]);

test('batch04 emperor has no invented sauce quantity and outside delivery has no invented tariff', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class, SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    $p = Producto::where('slug', 'el-emperador')->sole();
    expect(Batch04Data::sauces(150))->toBeNull()
        ->and($p->reglas->filter(fn ($r) => ($r->metadata['uso'] ?? null) === 'salsa_incluida'))->toHaveCount(0)
        ->and(config('delivery.local_price'))->toBe(2500)->and(config('delivery.outside_price'))->toBeNull();
    $this->postJson(route('cart.add'), batch04Payload('goloso'))->assertOk();
    $this->postJson(route('checkout.store'), ['cliente_nombre' => 'Prueba', 'cliente_telefono' => '123',
        'metodo_entrega' => 'delivery', 'zona_delivery' => 'fuera', 'kms_fuera' => 5, 'cliente_direccion' => 'Prueba'])
        ->assertUnprocessable()->assertJsonValidationErrors('zona_delivery');
    expect(DB::table('orders')->count())->toBe(0);
});
