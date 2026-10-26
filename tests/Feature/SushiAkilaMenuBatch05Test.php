<?php

use App\Models\Categoria;
use App\Models\GrupoOpcion;
use App\Models\Ingrediente;
use App\Models\Order;
use App\Models\Producto;
use App\Models\User;
use App\Services\MenuConfigurationService;
use App\Services\MercadoPagoService;
use App\Services\OrderItemSnapshotService;
use Database\Seeders\SushiAkila\Batch05Data;
use Database\Seeders\SushiAkila\MenuIdentity;
use Database\Seeders\SushiAkilaMenuBatch01Seeder;
use Database\Seeders\SushiAkilaMenuBatch02Seeder;
use Database\Seeders\SushiAkilaMenuBatch03Seeder;
use Database\Seeders\SushiAkilaMenuBatch04Seeder;
use Database\Seeders\SushiAkilaMenuBatch05Seeder;
use Database\Seeders\SushiAkilaMenuSeeder;
use Illuminate\Support\Facades\DB;

function batch05Payload(array $flavors): array
{
    $group = GrupoOpcion::where('slug', 'sabor-arrollados')->sole();

    return ['product_id' => Producto::where('slug', 'arrollados')->sole()->id, 'unidades' => 1,
        'option_ids' => $group->opciones->whereIn('nombre', $flavors)->pluck('id')->all(),
        'price' => 1, 'total' => 1, 'extra_price' => -5000, 'discount' => 999999];
}

function batch05State(): array
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

test('batch05 imports exactly three products and principal reruns preserve every identity and value', function () {
    $this->seed([SushiAkilaMenuBatch01Seeder::class, SushiAkilaMenuBatch02Seeder::class,
        SushiAkilaMenuBatch03Seeder::class, SushiAkilaMenuBatch04Seeder::class]);
    $before = Producto::orderBy('id')->get(['id', 'slug', 'precio', 'activo'])->toArray();
    expect(Producto::count())->toBe(34)->and(Categoria::count())->toBe(5)->and(Ingrediente::count())->toBe(22);
    $this->artisan('db:seed', ['--class' => SushiAkilaMenuBatch05Seeder::class])->assertExitCode(0);
    expect(Producto::count())->toBe(37)->and(Categoria::count())->toBe(7)->and(Ingrediente::count())->toBe(23)
        ->and(Producto::distinct()->count('slug'))->toBe(37)->and(Categoria::distinct()->count('slug'))->toBe(7)
        ->and(Ingrediente::all()->map(fn ($i) => MenuIdentity::ingredientKey($i->nombre))->unique())->toHaveCount(23);
    expect(Producto::whereIn('id', array_column($before, 'id'))->orderBy('id')->get(['id', 'slug', 'precio', 'activo'])->toArray())->toBe($before);
    $state = batch05State();
    $this->seed(SushiAkilaMenuBatch05Seeder::class);
    expect(batch05State())->toBe($state);
    $this->artisan('db:seed', ['--class' => SushiAkilaMenuSeeder::class])->assertExitCode(0);
    expect(batch05State())->toBe($state);
    $this->artisan('db:seed', ['--class' => SushiAkilaMenuSeeder::class])->assertExitCode(0);
    expect(batch05State())->toBe($state);
});

test('batch05 arrollados has commercial flavors and eight units without physical recipes or sushi blocks', function () {
    $this->seed(SushiAkilaMenuBatch05Seeder::class);
    $product = Producto::where('slug', 'arrollados')->sole();
    $group = $product->gruposOpciones->sole();
    expect($product->precio)->toEqual(5000)->and($product->unidades)->toBe(8)->and($product->unidad_tipo->value)->toBe('unidad')
        ->and($product->categoria->slug)->toBe('acompanamientos')->and($product->activo)->toBeTrue()
        ->and($product->bloques)->toHaveCount(0)->and($product->ingredientes)->toHaveCount(0)->and($product->reglas)->toHaveCount(0)
        ->and($group->tipo_seleccion)->toBe('multiple')->and($group->limites($group->pivot))->toBe([1, 2])
        ->and($group->opciones->pluck('nombre')->sort()->values()->all())->toBe(['Jamón Queso', 'Primavera'])
        ->and($group->opciones->pluck('ingrediente_id')->all())->toBe([null, null])
        ->and(Ingrediente::whereIn('nombre', ['Primavera', 'Jamón Queso'])->count())->toBe(0);
    $schema = app(MenuConfigurationService::class)->schema($product);
    expect($schema['pieces'])->toBeNull()->and($schema['quantity_label'])->toBe('8 unidades')->and($schema['groups'][0]['categorize'])->toBeFalse();
    $this->get(route('producto.modal', $product->id))->assertOk()->assertSee('8 unidades')->assertSee('Primavera')->assertSee('Jamón Queso')
        ->assertDontSee('Roll 1')->assertDontSee('8 piezas')->assertDontSee('data-menu-block=', false);
});

test('batch05 each flavor combination costs 5000 and keeps names without invented quantities in snapshot', function (array $flavors) {
    $this->seed(SushiAkilaMenuBatch05Seeder::class);
    $payload = batch05Payload($flavors);
    $response = $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('precio_unit', 5000)
        ->assertJsonPath('precio_base', 5000)->assertJsonPath('recargo', 0)->assertJsonPath('total', 5000);
    $this->postJson(route('cart.add'), $payload)->assertOk();
    $quote = $response->json();
    $snapshot = app(OrderItemSnapshotService::class)->fromQuote(Producto::findOrFail($payload['product_id']), $quote);
    $selection = $snapshot['selections']['Grupos'][0];
    expect($snapshot['version'])->toBe(2)->and($snapshot['product']['included_quantity'])->toBe(8)
        ->and($snapshot['product']['unit_type'])->toBe('unidad')->and($snapshot['configuration']['blocks'])->toBe([])
        ->and($selection['mixto'])->toBe(count($flavors) === 2)->and(array_column($selection['opciones'], 'nombre'))->toBe($flavors);
    foreach ($selection['opciones'] as $option) {
        expect($option)->not->toHaveKey('cantidad')->and($option)->not->toHaveKey('pieces');
    }
    $this->get(route('cart.index'))->assertOk()->assertSee('8 unidades')->assertSee(implode(', ', $flavors));
    $this->postJson(route('cart.quote'), array_replace($payload, ['option_ids' => array_reverse($payload['option_ids'])]))
        ->assertOk()->assertJsonPath('hash', $quote['hash']);
})->with([[['Primavera']], [['Jamón Queso']], [['Primavera', 'Jamón Queso']]]);

test('batch05 arrollados rejects absent duplicate excessive foreign and inactive flavors', function (string $attack) {
    $this->seed(SushiAkilaMenuBatch05Seeder::class);
    $payload = batch05Payload(['Primavera', 'Jamón Queso']);
    $group = GrupoOpcion::where('slug', 'sabor-arrollados')->sole();
    switch ($attack) {
        case 'missing': $payload['option_ids'] = [];
            break;
        case 'duplicate': $payload['option_ids'] = [$payload['option_ids'][0], $payload['option_ids'][0]];
            break;
        case 'excessive':
            // A later valid catalog option still cannot bypass the assigned maximum of two.
            $third = $group->opciones()->create(['nombre' => 'Solo prueba', 'slug' => 'solo-prueba', 'recargo' => 0]);
            $payload['option_ids'][] = $third->id;
            break;
        case 'foreign': $payload['option_ids'] = [GrupoOpcion::where('slug', 'proteina')->sole()->opciones->first()->id];
            break;
        case 'inactive': $group->opciones->first()->update(['activo' => false]);
            break;
    }
    foreach (['cart.quote', 'cart.add'] as $route) {
        $this->postJson(route($route), $payload)->assertUnprocessable();
    }
    expect(session('cart', []))->toBe([]);
})->with(['missing', 'duplicate', 'excessive', 'foreign', 'inactive']);

test('batch05 gohan has only confirmed base and descriptive vegetarian note and cannot be bought', function (string $slug) {
    $this->seed(SushiAkilaMenuBatch05Seeder::class);
    $product = Producto::where('slug', $slug)->sole();
    expect($product->precio)->toEqual(7500)->and($product->activo)->toBeFalse()->and($product->categoria->slug)->toBe('gohan')
        ->and($product->ingredientes->pluck('nombre')->sort()->values()->all())->toBe(collect(Batch05Data::GOHAN_BASE)->sort()->values()->all())
        ->and($product->gruposOpciones)->toHaveCount(0)->and($product->bloques)->toHaveCount(0)->and($product->reglas)->toHaveCount(0)
        ->and($product->descripcion)->toContain(Batch05Data::GOHAN_PENDING, 'Disponible opción vegetariana')
        ->and(Ingrediente::where('nombre', 'Arroz')->count())->toBe(1);
    foreach ($product->ingredientes as $ingredient) {
        expect($ingredient->pivot->cantidad)->toBeNull()->and($ingredient->pivot->unidad)->toBeNull()
            ->and($ingredient->pivot->reemplazable)->toBeFalse()->and($ingredient->pivot->metadata['removible'])->toBeFalse();
    }
    $rice = $product->ingredientes->firstWhere('nombre', 'Arroz');
    expect($rice->tipo)->toBe('base')->and($rice->pivot->metadata['opcion_vegetariana']['disponible'])->toBeTrue()
        ->and($rice->pivot->metadata['opcion_vegetariana']['estado'])->toBe('pendiente_definicion_operativa');
    $this->get(route('producto.modal', $product->id))->assertNotFound();
    foreach (['cart.quote', 'cart.add'] as $route) {
        $this->postJson(route($route), ['product_id' => $product->id, 'unidades' => 1, 'activo' => true, 'price' => 1])->assertUnprocessable();
    }
})->with(['gohan-flecha', 'gohan-orion']);

test('batch05 public availability hides inactive products and categories while administration retains gohan', function () {
    $this->seed(SushiAkilaMenuBatch05Seeder::class);
    $this->get(route('menu', ['filter' => 'gohan']))->assertOk()->assertDontSee('Gohan Flecha')->assertDontSee('Gohan Orion');
    $this->get(route('menu', ['filter' => 'acompanamientos']))->assertOk()->assertSee('Arrollados');
    $hidden = Producto::create(['nombre' => 'AAA Producto oculto', 'precio' => 999, 'descripcion' => 'No publicar',
        'categoria_id' => Categoria::where('slug', 'acompanamientos')->sole()->id, 'activo' => false, 'personalizable' => true]);
    $category = Categoria::create(['nombre' => 'Oculta', 'slug' => 'oculta', 'activo' => false]);
    $legacy = Producto::create(['nombre' => 'AAA Categoría oculta', 'precio' => 999, 'descripcion' => 'No publicar', 'categoria_id' => $category->id]);
    foreach (['home', 'menu'] as $route) {
        $this->get(route($route))->assertOk()->assertDontSee($hidden->nombre)->assertDontSee($legacy->nombre)
            ->assertDontSee('Gohan Flecha')->assertDontSee('Gohan Orion')->assertDontSee('>Oculta<', false);
    }
    foreach ([$hidden, $legacy] as $product) {
        $this->get(route('producto.modal', $product->id))->assertNotFound();
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'unidades' => 1])->assertUnprocessable();
    }
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->get(route('admin.productos.index', ['q' => 'Gohan']))->assertOk()->assertSee('Gohan Flecha')->assertSee('Gohan Orion');
});

test('batch05 mixed arrollados survives checkout and immutable snapshot without changes to payments', function () {
    $this->seed(SushiAkilaMenuBatch05Seeder::class);
    $payload = batch05Payload(['Primavera', 'Jamón Queso']);
    $this->postJson(route('cart.add'), $payload)->assertOk();
    $gateway = Mockery::mock(MercadoPagoService::class);
    $gateway->shouldReceive('createPreference')->once()->withArgs(fn ($order) => $order->total === '5000.00')->andReturn('https://example.test/batch05');
    $this->app->instance(MercadoPagoService::class, $gateway);
    $this->post(route('checkout.store'), ['cliente_nombre' => 'Prueba', 'cliente_telefono' => '123', 'metodo_entrega' => 'pickup'])
        ->assertRedirect('https://example.test/batch05');
    $item = Order::sole()->items()->sole();
    $snapshot = $item->snapshot;
    expect($snapshot['selections']['Grupos'][0]['mixto'])->toBeTrue()
        ->and(array_column($snapshot['selections']['Grupos'][0]['opciones'], 'nombre'))->toBe(['Primavera', 'Jamón Queso']);
    $this->seed(SushiAkilaMenuBatch05Seeder::class);
    Producto::findOrFail($payload['product_id'])->update(['precio' => 9999]);
    expect($item->fresh()->snapshot)->toBe($snapshot)->and($item->fresh()->precio_unit)->toBe('5000.00');
    expect(view('pdf.invoice', ['order' => Order::sole()->load('items')])->render())->toContain('Mixto', 'Primavera, Jamón Queso');
});
