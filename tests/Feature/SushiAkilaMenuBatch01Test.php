<?php

use App\Enums\TipoReglaPromocion;
use App\Models\Categoria;
use App\Models\GrupoOpcion;
use App\Models\Ingrediente;
use App\Models\Opcion;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\SushiAkilaMenuBatch01Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function batch01Counts(): array
{
    $result = [];
    foreach (['categorias', 'ingredientes', 'productos', 'promociones', 'ingrediente_producto', 'grupos_opciones',
        'opciones', 'grupo_opcion_producto', 'bloques_promocion', 'bloque_grupo_opcion', 'reglas_promocion', 'users', 'orders', 'order_items'] as $table) {
        $result[$table] = DB::table($table)->count();
    }

    return $result;
}

test('batch01 loads precisely the twelve sourced products and their prices', function () {
    $this->artisan('db:seed', ['--class' => SushiAkilaMenuBatch01Seeder::class])->assertExitCode(0);
    $prices = [
        'goloso' => 10000, 'proteinas' => 14990, 'noche-de-sushi' => 17990, 'mi-favorita' => 18990,
        'promo-hot' => 20990, 'lo-nuevo' => 25990, '30-piezas-a-eleccion' => 14900,
        'costanera' => 14900, 'monumento' => 14900, 'las-ruinas' => 14900, 'la-playa' => 14900, 'codigo-verde' => 14900,
    ];
    expect(Producto::count())->toBe(12)->and(Producto::pluck('slug')->sort()->values()->all())->toBe(collect(array_keys($prices))->sort()->values()->all());
    foreach ($prices as $slug => $price) {
        $product = Producto::where('slug', $slug)->sole();
        expect(bccomp((string) $product->precio, (string) $price, 2))->toBe(0)
            ->and(bccomp((string) $product->promocion->precio, (string) $price, 2))->toBe(0);
    }
    expect(Categoria::orderBy('orden')->pluck('nombre')->all())->toBe(['Promociones', 'Promociones a elección'])
        ->and(Categoria::where('activo', true)->count())->toBe(2)
        ->and(Producto::where('slug', 'nuevo-cortes')->exists())->toBeFalse();
    foreach (['productos' => 'precio', 'opciones' => 'recargo', 'reglas_promocion' => 'valor'] as $table => $column) {
        expect(Schema::getColumnType($table, $column, true))->toMatch('/(?:decimal|numeric)/i');
    }
});

test('batch01 reexecution keeps every natural identity without duplicate rows', function () {
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    $counts = batch01Counts();
    $identities = [];
    foreach (array_keys($counts) as $table) {
        $identities[$table] = DB::table($table)->orderBy('id')->pluck('id')->all();
    }
    $this->travel(1)->minutes();
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    expect(batch01Counts())->toBe($counts);
    foreach ($identities as $table => $ids) {
        expect(DB::table($table)->orderBy('id')->pluck('id')->all())->toBe($ids);
    }
    expect($counts['productos'])->toBe(12)->and($counts['categorias'])->toBe(2)
        ->and($counts['ingredientes'])->toBe(14)->and($counts['bloques_promocion'])->toBe(45)
        ->and($counts['grupos_opciones'])->toBe(16)->and($counts['opciones'])->toBe(36)
        ->and($counts['reglas_promocion'])->toBe(215);
});

test('batch01 scoped reexecution preserves identities and restores its own source price', function () {
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    $hot = Producto::where('slug', 'promo-hot')->sole();
    $hot->update(['precio' => '19990.00']);
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    expect(Producto::where('slug', 'promo-hot')->sole()->id)->toBe($hot->id)
        ->and((int) $hot->fresh()->precio)->toBe(20990)->and(Producto::count())->toBe(12);
});

test('batch01 normalizes singular accents case and the summary product alias', function () {
    $palmito = Ingrediente::create(['nombre' => 'PALMITOS', 'tipo' => 'vegetal']);
    $mushroom = Ingrediente::create(['nombre' => 'Champiñones', 'tipo' => 'vegetal']);
    $salmon = Ingrediente::create(['nombre' => 'SALMON', 'tipo' => 'proteina']);
    $category = Categoria::create(['nombre' => 'Promociones']);
    $old = Producto::create(['nombre' => 'Nuevo cortes', 'precio' => 25990, 'descripcion' => 'Resumen', 'categoria_id' => $category->id]);
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    expect(Ingrediente::count())->toBe(14)->and($palmito->fresh()->nombre)->toBe('Palmito')
        ->and($mushroom->fresh()->nombre)->toBe('Champiñón')->and($salmon->fresh()->nombre)->toBe('Salmón')
        ->and(Producto::where('slug', 'lo-nuevo')->sole()->id)->toBe($old->id)
        ->and($old->fresh()->nombre)->toBe('Lo Nuevo')->and(Producto::count())->toBe(12);
});

test('batch01 refuses ambiguous ingredient identities and rolls back the entire import', function () {
    Ingrediente::create(['nombre' => 'Palmito', 'tipo' => 'vegetal']);
    Ingrediente::create(['nombre' => 'Palmitos', 'tipo' => 'vegetal']);
    expect(fn () => $this->seed(SushiAkilaMenuBatch01Seeder::class))->toThrow(RuntimeException::class);
    expect(Ingrediente::count())->toBe(2)->and(Producto::count())->toBe(0)->and(Categoria::count())->toBe(0);
});

test('batch01 specific choice filling offers one protein OR vegetable and only eight allowed ingredients', function () {
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    $product = Producto::where('slug', '30-piezas-a-eleccion')->sole();
    expect($product->personalizable)->toBeTrue()->and($product->unidades)->toBe(30)->and($product->bloques->count())->toBe(3);
    foreach ($product->bloques as $block) {
        $filling = $block->gruposOpciones->firstWhere('slug', 'relleno-30-piezas-a-eleccion');
        expect($filling->limites($filling->pivot))->toBe([1, 1]);
        expect($filling->opciones->pluck('nombre')->sort()->values()->all())->toBe(collect([
            'Pollo', 'Camarón', 'Kanikama', 'Salmón', 'Palmito', 'Champiñón', 'Choclo', 'Palta',
        ])->sort()->values()->all());
        expect($filling->opciones->pluck('ingrediente_id')->filter()->count())->toBe(8);
    }
    $rules = $product->reglas->where('tipo', TipoReglaPromocion::MaxRepeticiones);
    expect($rules->count())->toBe(8);
    foreach ($rules as $rule) {
        expect($rule->valor)->toBe($rule->ingrediente->nombre === 'Salmón' ? '1.00' : '2.00');
    }
    $otherIds = Ingrediente::whereIn('nombre', ['Atún', 'Morrón'])->pluck('id');
    expect($product->reglas()->whereIn('ingrediente_id', $otherIds)->count())->toBe(0);
});

test('batch01 stores exact fixed blocks without extra sellable products', function (string $slug, array $expected) {
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    $blocks = Producto::where('slug', $slug)->sole()->bloques;
    expect($blocks->count())->toBe(3);
    foreach ($blocks as $index => $block) {
        expect($block->cantidad_piezas)->toBe(10);
        $names = $block->gruposOpciones->flatMap(fn ($group) => $group->opciones->pluck('nombre'))->sort()->values()->all();
        expect($names)->toBe(collect($expected[$index])->sort()->values()->all());
        foreach ($block->gruposOpciones as $group) {
            expect($group->opciones->count())->toBe(1)->and($group->limites($group->pivot))->toBe([1, 1]);
        }
    }
})->with([
    'Costanera' => ['costanera', [['Pollo', 'Frito'], ['Camarón', 'Palta'], ['Palta', 'Queso crema']]],
    'Monumento' => ['monumento', [['Pollo', 'Frito'], ['Champiñón', 'Palta'], ['Palta', 'Queso crema']]],
    'Las Ruinas' => ['las-ruinas', [['Pollo', 'Frito'], ['Kanikama', 'Palta'], ['Palta', 'Queso crema']]],
    'La Playa' => ['la-playa', [['Camarón', 'Frito'], ['Kanikama', 'Queso crema'], ['Salmón', 'Palta']]],
    'Código Verde' => ['codigo-verde', [['Champiñón', 'Frito'], ['Choclo', 'Queso crema'], ['Palmito', 'Palta']]],
]);

test('batch01 preserves known preparations without guessing unspecified block wrappers', function () {
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    foreach (['lo-nuevo' => ['piezas-frio' => 30, 'piezas-frito' => 40],
        'noche-de-sushi' => ['piezas-envuelto' => 20, 'piezas-frito' => 20],
        'mi-favorita' => ['piezas-envuelto' => 30, 'piezas-frito' => 20]] as $slug => $counts) {
        $product = Producto::where('slug', $slug)->sole();
        foreach ($counts as $code => $pieces) {
            $rule = $product->reglas()->where('codigo', $code)->sole();
            expect((int) $rule->valor)->toBe($pieces)->and($rule->metadata['unidad'])->toBe('pieza')
                ->and($rule->metadata['distribucion_por_bloque'])->toBeFalse();
        }
        foreach ($product->bloques as $block) {
            expect($block->gruposOpciones->contains(fn ($group) => str_starts_with($group->slug, 'envoltura')))->toBeFalse();
        }
    }
    expect(Producto::where('slug', 'lo-nuevo')->sole()->unidades)->toBe(70);
});

test('batch01 has no physical quantities fictional ingredients or later photo products', function () {
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    expect(DB::table('ingrediente_producto')->whereNotNull('cantidad')->count())->toBe(0)
        ->and(DB::table('ingrediente_producto')->whereNotNull('unidad')->count())->toBe(0)
        ->and(Ingrediente::whereIn('nombre', ['Frito', 'Carne', 'Pepino', 'Arroz', 'Salsa de soya'])->count())->toBe(0);
    $wrapper = GrupoOpcion::where('slug', 'envoltura')->sole();
    expect($wrapper->opciones()->where('slug', 'frito')->sole()->ingrediente_id)->toBeNull();
    $palta = Ingrediente::where('nombre', 'Palta')->sole();
    expect($wrapper->opciones()->where('slug', 'palta')->sole()->ingrediente_id)->toBe($palta->id)
        ->and(Ingrediente::where('nombre', 'Palta')->count())->toBe(1);
    expect(Producto::whereIn('slug', ['el-ciclon', '60-piezas-a-eleccion', '50-piezas-a-eleccion', 'tu-media-naranja',
        'el-emperador', 'para-la-familia', 'muchupleto', 'akilaburger', 'gohan'])->count())->toBe(0);
});

test('batch01 changes are conditional server data and do not invalidate fixed recipes', function () {
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    $fixed = Producto::where('slug', 'proteinas')->sole();
    expect($fixed->personalizable)->toBeFalse()->and($fixed->reglas()->where('tipo', 'max_repeticiones')->count())->toBe(0);
    $change = $fixed->reglas()->where('codigo', 'cambio-relleno-pollo')->sole();
    expect($change->valor)->toBe('1000.00')->and($change->metadata['condicion'])->toBe('modificacion_relleno');
    expect(Opcion::where('recargo', '!=', 0)->count())->toBe(0);
    $base = $fixed->ingredientes->firstWhere('nombre', 'Queso crema')->pivot;
    expect($base->reemplazable)->toBeFalse()->and($base->metadata['removible'])->toBeTrue();
});

test('batch01 preserves unrelated catalog users orders and historical snapshots', function () {
    $user = User::factory()->create();
    $category = Categoria::create(['nombre' => 'Categoría administrativa']);
    $unrelated = Producto::create(['nombre' => 'Producto administrativo', 'descripcion' => 'No tocar', 'precio' => 123, 'categoria_id' => $category->id]);
    $order = Order::create(['cliente_nombre' => 'Histórico', 'cliente_telefono' => '123', 'total' => '123.00']);
    $order->user()->associate($user)->save();
    $item = $order->items()->create(['product_id' => $unrelated->id, 'nombre' => 'Histórico', 'precio_unit' => 123, 'total' => 123, 'detalle' => [], 'snapshot' => ['version' => 1, 'name' => 'Histórico']]);
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    $this->seed(SushiAkilaMenuBatch01Seeder::class);
    expect(Producto::count())->toBe(13)->and($unrelated->fresh()->descripcion)->toBe('No tocar')
        ->and(User::count())->toBe(1)->and(Order::count())->toBe(1)->and(OrderItem::count())->toBe(1)
        ->and($item->fresh()->snapshot)->toBe(['version' => 1, 'name' => 'Histórico']);
});
