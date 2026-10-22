<?php

use App\Enums\TipoProducto;
use App\Enums\TipoReglaPromocion;
use App\Models\Categoria;
use App\Models\GrupoOpcion;
use App\Models\Ingrediente;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\User;
use App\Services\MercadoPagoService;
use App\Services\OrderItemSnapshotService;
use App\Services\ProductPricingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function menuStructureProduct(array $attributes = []): Producto
{
    $category = Categoria::firstOrCreate(['nombre' => 'Fixture categoría']);

    return Producto::create(array_replace([
        'categoria_id' => $category->id, 'nombre' => 'Fixture producto', 'descripcion' => 'Descripción histórica',
        'precio' => '4500.25', 'unidades' => 40,
    ], $attributes))->fresh();
}

function menuStructureGroup(array $attributes = []): GrupoOpcion
{
    return GrupoOpcion::create(array_replace(['nombre' => 'Fixture grupo', 'slug' => 'fixture-grupo'], $attributes));
}

function menuStructureItem(Producto $product): OrderItem
{
    $quote = app(ProductPricingService::class)->quote($product, []);
    $order = Order::create(['cliente_nombre' => 'Fixture', 'cliente_telefono' => '123', 'total' => $quote['total']]);

    return $order->items()->create([
        'product_id' => $product->id, 'nombre' => $quote['nombre'], 'unidades' => 1,
        'precio_unit' => $quote['precio_unit'], 'total' => $quote['total'], 'detalle' => $quote['detalle'],
        'snapshot' => app(OrderItemSnapshotService::class)->fromQuote($product, $quote),
    ]);
}

test('catalog metadata reuses included units and preserves legacy defaults', function () {
    $product = menuStructureProduct();
    expect($product->tipo)->toBe(TipoProducto::Simple)
        ->and($product->activo)->toBeTrue()->and($product->unidades)->toBe(40)
        ->and($product->categoria->activo)->toBeTrue();
    expect(Schema::hasColumn('productos', 'cantidad_piezas'))->toBeFalse();
    $product->update(['tipo' => TipoProducto::Promocion, 'personalizable' => true, 'orden' => 2]);
    expect($product->fresh()->tipo)->toBe(TipoProducto::Promocion);
});

test('ingredient classification supports new kinds without rewriting old kinds', function (string $type) {
    $ingredient = Ingrediente::create(['nombre' => 'Fixture '.$type, 'tipo' => $type]);
    expect($ingredient->fresh()->tipo)->toBe($type);
})->with(['proteina', 'vegetal', 'envoltura', 'base', 'salsa', 'topping', 'otros']);

test('product composition stores nullable physical quantities independently of legacy quantities', function () {
    $product = menuStructureProduct();
    $ingredient = Ingrediente::create(['nombre' => 'Fixture base', 'tipo' => 'base']);
    $product->ingredientes()->attach($ingredient, [
        'cantidad_permitida' => 2, 'cantidad' => null, 'unidad' => null,
        'obligatorio' => true, 'reemplazable' => false, 'orden' => 3, 'metadata' => ['note' => 'Sin gramaje conocido'],
    ]);
    $pivot = $product->ingredientes()->first()->pivot;
    expect($pivot->cantidad)->toBeNull()->and($pivot->cantidad_permitida)->toBe(2)
        ->and($pivot->obligatorio)->toBeTrue()->and($pivot->metadata['note'])->toBe('Sin gramaje conocido');
    $ingredient->productos()->updateExistingPivot($product->id, ['cantidad' => '12.345', 'unidad' => 'g']);
    expect($product->ingredientes()->first()->pivot->cantidad)->toBe('12.345');
});

test('composition rejects negative physical quantity', function () {
    $product = menuStructureProduct();
    $ingredient = Ingrediente::create(['nombre' => 'Fixture', 'tipo' => 'base']);
    expect(fn () => $product->ingredientes()->attach($ingredient, ['cantidad' => -1]))
        ->toThrow(ValidationException::class);
});

test('product groups options and ingredient references have working inverse relations', function () {
    $product = menuStructureProduct();
    $group = menuStructureGroup();
    $ingredient = Ingrediente::create(['nombre' => 'Fixture ingrediente', 'tipo' => 'proteina']);
    $option = $group->opciones()->create(['nombre' => 'Fixture opción', 'slug' => 'fixture', 'ingrediente_id' => $ingredient->id, 'recargo' => '1234.56']);
    $labelOnly = $group->opciones()->create(['nombre' => 'Sin ingrediente', 'slug' => 'sin-ingrediente']);
    $product->gruposOpciones()->attach($group, ['min_selections' => 1, 'max_selections' => 1, 'obligatorio' => true, 'orden' => 3]);
    $assigned = $product->gruposOpciones()->first();
    expect($assigned->productos->first()->id)->toBe($product->id)
        ->and($option->grupo->id)->toBe($group->id)->and($option->ingrediente->id)->toBe($ingredient->id)
        ->and($ingredient->opciones->first()->id)->toBe($option->id)
        ->and($labelOnly->ingrediente)->toBeNull()->and($option->fresh()->recargo)->toBe('1234.56');
    $assigned->validarSelecciones([$option->id], $assigned->pivot);
    expect(fn () => $assigned->validarSelecciones([], $assigned->pivot))->toThrow(ValidationException::class);
});

test('selection validation rejects foreign inactive duplicate and excessive options', function () {
    $group = menuStructureGroup();
    $option = $group->opciones()->create(['nombre' => 'Opción', 'slug' => 'opcion']);
    $inactive = $group->opciones()->create(['nombre' => 'Inactiva', 'slug' => 'inactiva', 'activo' => false]);
    $other = menuStructureGroup(['slug' => 'otro'])->opciones()->create(['nombre' => 'Otra', 'slug' => 'otra']);
    foreach ([[$other->id], [$inactive->id], [$option->id, $option->id], [$option->id, $other->id]] as $ids) {
        expect(fn () => $group->validarSelecciones($ids))->toThrow(ValidationException::class);
    }
});

test('group overrides are local and invalid limits are rejected on server', function () {
    $product = menuStructureProduct();
    $group = menuStructureGroup(['tipo_seleccion' => 'multiple', 'min_selections' => 0, 'max_selections' => 4]);
    $product->gruposOpciones()->attach($group, ['min_selections' => 2, 'max_selections' => 3]);
    $assigned = $product->gruposOpciones()->first();
    expect($assigned->limites($assigned->pivot))->toBe([2, 3])->and($group->fresh()->limites())->toBe([0, 4]);
    expect(fn () => $product->gruposOpciones()->updateExistingPivot($group->id, ['max_selections' => 1]))
        ->toThrow(ValidationException::class);
    expect(fn () => $group->update(['tipo_seleccion' => 'single', 'max_selections' => 1]))
        ->toThrow(ValidationException::class);
    expect(fn () => menuStructureGroup(['slug' => 'invalid', 'min_selections' => 2, 'max_selections' => 1]))
        ->toThrow(ValidationException::class);
});

test('product blocks use independent group overrides and ordered identities', function () {
    $product = menuStructureProduct(['tipo' => TipoProducto::Promocion, 'personalizable' => true]);
    $group = menuStructureGroup();
    $first = $product->bloques()->create(['nombre' => 'Bloque 1', 'orden' => 1]);
    $second = $product->bloques()->create(['nombre' => 'Bloque 2', 'orden' => 2]);
    $first->gruposOpciones()->attach($group, ['obligatorio' => true]);
    $second->gruposOpciones()->attach($group, ['obligatorio' => false]);
    expect($first->producto->id)->toBe($product->id)->and($first->fresh()->cantidad_piezas)->toBe(10)
        ->and($product->bloques->pluck('orden')->all())->toBe([1, 2])
        ->and($group->bloques->count())->toBe(2);
    $g1 = $first->gruposOpciones()->first();
    $g2 = $second->gruposOpciones()->first();
    expect($g1->limites($g1->pivot))->toBe([1, 1])->and($g2->limites($g2->pivot))->toBe([0, 1]);
});

test('promotion rules reference stable subjects and store exact monetary surcharges', function () {
    $product = menuStructureProduct();
    $ingredient = Ingrediente::create(['nombre' => 'Fixture ingrediente', 'tipo' => 'salsa']);
    $rule = $product->reglas()->create(['codigo' => 'fixture-recargo', 'tipo' => TipoReglaPromocion::Recargo,
        'ingrediente_id' => $ingredient->id, 'valor' => '123.45', 'metadata' => ['note' => 'Fixture']]);
    expect($rule->fresh()->valor)->toBe('123.45')->and($rule->ingrediente->id)->toBe($ingredient->id)
        ->and($rule->producto->id)->toBe($product->id)->and($rule->tipo)->toBe(TipoReglaPromocion::Recargo);
    $option = menuStructureGroup()->opciones()->create(['nombre' => 'Fixture', 'slug' => 'fixture']);
    $included = $product->reglas()->create(['codigo' => 'incluida', 'tipo' => 'cantidad_incluida', 'opcion_id' => $option->id, 'valor' => 2]);
    expect($included->opcion->id)->toBe($option->id);
    expect(fn () => $product->reglas()->create(['codigo' => 'invalid', 'tipo' => 'max_repeticiones', 'valor' => 1]))
        ->toThrow(ValidationException::class);
    expect(fn () => $included->update(['ingrediente_id' => $ingredient->id]))->toThrow(ValidationException::class);
    expect(fn () => $rule->update(['tipo' => 'max_repeticiones', 'valor' => '1.50']))->toThrow(ValidationException::class);
});

test('negative and overprecision option surcharges are rejected', function () {
    $group = menuStructureGroup();
    foreach (['-0.01', '0.001'] as $price) {
        expect(fn () => $group->opciones()->create(['nombre' => 'Fixture', 'slug' => 'invalid', 'recargo' => $price]))
            ->toThrow(ValidationException::class);
    }
});

test('duplicate relationship identities are rejected by database constraints', function (string $identity) {
    $product = menuStructureProduct();
    $group = menuStructureGroup();
    $ingredient = Ingrediente::create(['nombre' => 'Fixture', 'tipo' => 'proteina']);
    $promotion = Promocion::create(['nombre' => 'Fixture', 'precio' => 1]);
    $block = $product->bloques()->create(['nombre' => 'Fixture', 'orden' => 1]);
    $write = match ($identity) {
        'composition' => fn () => $product->ingredientes()->attach($ingredient),
        'swappable' => fn () => $product->swappables()->attach($ingredient, ['tipo' => 'proteina']),
        'promotion-product' => fn () => $promotion->productos()->attach($product),
        'product-group' => fn () => $product->gruposOpciones()->attach($group),
        'block-group' => fn () => $block->gruposOpciones()->attach($group),
        'block-position' => fn () => $product->bloques()->create(['nombre' => 'Duplicado', 'orden' => 2]),
        'option-slug' => fn () => $group->opciones()->create(['nombre' => 'Fixture', 'slug' => 'duplicado']),
        'rule-code' => fn () => $product->reglas()->create(['codigo' => 'duplicado', 'tipo' => 'excluir', 'ingrediente_id' => $ingredient->id, 'valor' => 0]),
        'promotion-link' => fn () => Promocion::create(['nombre' => 'Link', 'precio' => 1, 'producto_id' => $product->id]),
    };
    $write();
    expect($write)->toThrow(QueryException::class);
})->with(['composition', 'swappable', 'promotion-product', 'product-group', 'block-group', 'block-position', 'option-slug', 'rule-code', 'promotion-link']);

test('promotion remains complementary to one sellable product', function () {
    $product = menuStructureProduct();
    $promotion = Promocion::create(['producto_id' => $product->id, 'nombre' => 'Fixture', 'precio' => 1]);
    expect($product->promocion->id)->toBe($promotion->id)->and($promotion->producto->id)->toBe($product->id);
});

test('historical item snapshot survives catalog modification and restricts deletion', function () {
    $product = menuStructureProduct();
    $item = menuStructureItem($product);
    $snapshot = $item->snapshot;
    $product->update(['nombre' => 'Nuevo nombre', 'precio' => '9999.99', 'descripcion' => 'Nueva descripción']);
    expect($item->fresh()->snapshot)->toBe($snapshot)
        ->and($snapshot['product']['description'])->toBe('Descripción histórica')
        ->and($snapshot['amounts']['base_unit'])->toBe('4500.25')
        ->and($item->fresh()->nombre)->toBe('Fixture producto');
    expect(fn () => $product->delete())->toThrow(QueryException::class);
    expect($item->fresh())->not->toBeNull();
    expect(fn () => $item->update(['snapshot' => ['version' => 999]]))->toThrow(ValidationException::class);
    expect($item->order->items->count())->toBe(1);
});

test('category deletion cannot cascade through historical products', function () {
    $product = menuStructureProduct();
    $item = menuStructureItem($product);
    expect(fn () => $product->categoria->delete())->toThrow(QueryException::class);
    expect($item->fresh())->not->toBeNull();
});

test('admin deletion preserves product image when history prevents deletion', function () {
    Storage::fake('public');
    $product = menuStructureProduct(['imagen' => 'productos/history.jpg']);
    menuStructureItem($product);
    Storage::disk('public')->put($product->imagen, 'fixture');
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $this->actingAs($admin)->from('/admin/productos')->delete(route('admin.productos.destroy', $product))
        ->assertRedirect('/admin/productos')->assertSessionHasErrors();
    Storage::disk('public')->assertExists($product->imagen);
});

test('checkout writes snapshots only from server refreshed quote and keeps old columns', function () {
    $product = menuStructureProduct();
    $quote = app(ProductPricingService::class)->quote($product, []);
    $gateway = Mockery::mock(MercadoPagoService::class);
    $gateway->shouldReceive('createPreference')->once()->andReturn('https://example.test/fixture-payment');
    $this->app->instance(MercadoPagoService::class, $gateway);
    $this->withSession(['cart' => [$quote['hash'] => $quote]])->post(route('checkout.store'), [
        'cliente_nombre' => 'Fixture', 'cliente_telefono' => '123', 'metodo_entrega' => 'pickup',
        'price' => 1, 'total' => 1, 'snapshot' => ['amounts' => ['final_unit' => '1.00']],
    ])->assertRedirect('https://example.test/fixture-payment');
    $item = OrderItem::sole();
    expect($item->snapshot['amounts']['final_unit'])->toBe('4500.25')
        ->and($item->precio_unit)->toBe('4500.25')->and($item->snapshot['configuration'])->toBe($quote['configuration']);
});

test('legacy order items remain readable without a structured snapshot', function () {
    $product = menuStructureProduct();
    $order = Order::create(['cliente_nombre' => 'Fixture', 'cliente_telefono' => '123', 'total' => 1]);
    $item = $order->items()->create(['product_id' => $product->id, 'nombre' => 'Nombre legado', 'precio_unit' => 1, 'total' => 1, 'detalle' => []]);
    expect($item->fresh()->snapshot)->toBeNull()->and($item->nombre)->toBe('Nombre legado');
});

test('ingredient type rollback refuses to discard new classifications', function () {
    $ingredient = Ingrediente::create(['nombre' => 'Fixture salsa', 'tipo' => 'salsa']);
    $migration = require database_path('migrations/2026_10_21_000110_extend_ingredient_types.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
    expect($ingredient->fresh()->tipo)->toBe('salsa');
});

test('new migrations reverse and reapply without losing legacy rows in isolation', function () {
    // SQLite table rebuilds must run outside RefreshDatabase's wrapping transaction.
    // Use a separate in-memory connection; never run these downs on MariaDB.
    expect(DB::getDriverName())->toBe('sqlite')->and(config('database.connections.sqlite.database'))->toBe(':memory:');
    $original = DB::getDefaultConnection();
    config(['database.connections.catalog_roundtrip' => config('database.connections.sqlite')]);
    DB::setDefaultConnection('catalog_roundtrip');
    Schema::clearResolvedInstance('db.schema');
    try {
        $this->artisan('migrate', ['--database' => 'catalog_roundtrip', '--force' => true])->assertExitCode(0);
        $product = menuStructureProduct();
        $ingredient = Ingrediente::create(['nombre' => 'Fixture', 'tipo' => 'proteina']);
        $product->ingredientes()->attach($ingredient, ['cantidad_permitida' => 1]);
        $order = Order::create(['cliente_nombre' => 'Fixture', 'cliente_telefono' => '123', 'total' => '4500.25']);
        $item = $order->items()->create(['product_id' => $product->id, 'nombre' => 'Legado', 'precio_unit' => '4500.25', 'total' => '4500.25', 'detalle' => []]);
        $paths = glob(database_path('migrations/2026_10_21_0001*.php'));
        foreach (array_reverse($paths) as $path) {
            (require $path)->down();
        }
        expect(Schema::hasTable('opciones'))->toBeFalse()->and(Schema::hasColumn('order_items', 'snapshot'))->toBeFalse();
        expect(DB::table('order_items')->where('id', $item->id)->value('nombre'))->toBe('Legado')
            ->and(DB::table('ingrediente_producto')->where('producto_id', $product->id)->value('cantidad_permitida'))->toBe(1);
        foreach ($paths as $path) {
            (require $path)->up();
        }
        expect(Schema::hasTable('opciones'))->toBeTrue()->and(Schema::hasColumn('order_items', 'snapshot'))->toBeTrue();
        expect($item->fresh()->precio_unit)->toBe('4500.25')->and($product->fresh()->nombre)->toBe('Fixture producto');
        expect(DB::select('PRAGMA foreign_key_check'))->toBe([]);
    } finally {
        DB::setDefaultConnection($original);
        Schema::clearResolvedInstance('db.schema');
        DB::purge('catalog_roundtrip');
    }
});
