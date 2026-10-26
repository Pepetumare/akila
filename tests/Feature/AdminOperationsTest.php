<?php

use App\Models\AdminChange;
use App\Models\Ingrediente;
use App\Models\Order;
use App\Models\Producto;
use App\Models\User;
use App\Services\KitchenMetrics;
use App\Services\MenuConfigurationService;
use App\Services\OperationSettings;
use App\Services\OrderItemSnapshotService;
use App\Services\ProductPricingService;
use Database\Seeders\SushiAkilaMenuSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Support\MenuAuditConfiguration;

function adminOperationUser(): User
{
    $user = User::factory()->create();
    $user->forceFill(['is_admin' => true])->save();

    return $user;
}

function adminOperationOrder(string $slug = 'goloso', array $attributes = []): Order
{
    $product = Producto::where('slug', $slug)->firstOrFail();
    $quote = app(ProductPricingService::class)->quote($product, MenuAuditConfiguration::payload($product));
    $order = Order::create(array_merge(['cliente_nombre' => 'Prueba cocina', 'cliente_telefono' => '123', 'metodo_entrega' => 'pickup',
        'status' => 'pendiente', 'payment_status' => 'approved', 'subtotal' => $quote['total'], 'delivery_cost' => 0, 'total' => $quote['total']], $attributes));
    $order->items()->create(['product_id' => $product->id, 'nombre' => $quote['nombre'], 'unidades' => 1,
        'precio_unit' => $quote['precio_unit'], 'total' => $quote['total'], 'detalle' => $quote['detalle'],
        'snapshot' => app(OrderItemSnapshotService::class)->fromQuote($product, $quote)]);

    return $order;
}

beforeEach(function () {
    $this->seed(SushiAkilaMenuSeeder::class);
});

test('admin pages are protected and use the operational shell without public bundles', function () {
    $this->get(route('admin.kitchen'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('admin.settings'))->assertRedirect(route('home'));
    $this->actingAs(adminOperationUser());
    foreach (['admin.dashboard', 'admin.kitchen', 'admin.orders.index', 'admin.productos.index', 'admin.ingredientes.index', 'admin.categorias.index', 'admin.personalization', 'admin.inventory', 'admin.customers', 'admin.reports', 'admin.settings'] as $route) {
        $this->get(route($route))->assertOk()->assertSee('admin-sidebar')->assertSee('Administración')->assertDontSee('cdn.jsdelivr.net');
    }
});

test('dashboard counts paid sales and local day correctly without inventing pieces', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-10-23 02:30:00', 'UTC'));
    adminOperationOrder('goloso');
    adminOperationOrder('arrollados');
    adminOperationOrder('goloso', ['payment_status' => 'pending']);
    $legacy = Order::create(['cliente_nombre' => 'Legacy', 'cliente_telefono' => '123', 'total' => 99, 'status' => 'entregado', 'payment_status' => 'approved']);
    $legacy->forceFill(['created_at' => '2026-10-22 02:00:00'])->save(); // Previous day in Santiago.
    $response = $this->actingAs(adminOperationUser())->get(route('admin.dashboard'))->assertOk();
    expect($response->viewData('ordersToday'))->toBe(3)
        ->and((float) $response->viewData('sales'))->toBe(15000.0)
        ->and($response->viewData('sold')['pieces'])->toBe(20)
        ->and($response->viewData('sold')['units'])->toBe(8)
        ->and($response->viewData('load')['pieces'])->toBe(40)
        ->and($response->viewData('load')['fried'])->toBe(40);
});

test('kitchen transitions enforce sequence timestamps stale state and separate payment', function () {
    $order = adminOperationOrder();
    $snapshot = $order->items->first()->snapshot;
    $this->actingAs(adminOperationUser());
    $this->getJson(route('admin.kitchen'))->assertOk()->assertJsonFragment(['new_ids' => [$order->id]]);
    $this->patchJson(route('admin.orders.update', $order), ['status' => 'listo', 'expected_status' => 'pendiente'])->assertUnprocessable();
    foreach ([['pendiente', 'en preparación', 'preparing_at'], ['en preparación', 'listo', 'ready_at'], ['listo', 'entregado', 'delivered_at']] as [$from, $to, $timestamp]) {
        $this->patchJson(route('admin.orders.update', $order), ['status' => $to, 'expected_status' => $from])->assertOk();
        expect($order->fresh()->$timestamp)->not->toBeNull()->and($order->fresh()->status)->toBe($to)->and($order->fresh()->payment_status)->toBe('approved');
    }
    $this->patchJson(route('admin.orders.update', $order), ['status' => 'en preparación', 'expected_status' => 'pendiente'])->assertUnprocessable();
    expect($order->items->first()->fresh()->snapshot)->toBe($snapshot)->and(AdminChange::where('action', 'estado')->count())->toBe(3);
});

test('order detail and comanda retain historical selection after product changes', function () {
    $order = adminOperationOrder('30-piezas-a-eleccion');
    $item = $order->items->first();
    Producto::find($item->product_id)->update(['nombre' => 'Renombrado', 'precio' => 1]);
    $this->actingAs(adminOperationUser());
    foreach (['admin.orders.show', 'admin.orders.print'] as $route) {
        $this->get(route($route, $order))->assertOk()->assertSee('30 Piezas a Elección')->assertSee('Roll 1')->assertSee('Salsa de soya')->assertDontSee('Renombrado');
    }
});

test('general product update preserves every modern composition relationship', function () {
    $product = Producto::where('slug', '30-piezas-a-eleccion')->sole();
    $product->load(MenuConfigurationService::RELATIONS);
    $before = app(MenuConfigurationService::class)->schema($product);
    $rules = $product->reglas->toArray();
    $pivot = DB::table('ingrediente_producto')->where('producto_id', $product->id)->get()->toArray();
    $this->actingAs(adminOperationUser())->put(route('admin.productos.update', $product), [
        'nombre' => 'Nombre administrativo', 'slug' => $product->slug, 'precio' => '15900',
        'categoria_id' => $product->categoria_id, 'activo' => 1,
        'personalizable' => 0, 'envolturas' => [], 'blocks' => [], 'rules' => [],
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect($product->fresh()->personalizable)->toBeTrue()
        ->and(app(MenuConfigurationService::class)->schema($product->fresh()))->toBe($before)
        ->and($product->fresh()->reglas->toArray())->toBe($rules)
        ->and(DB::table('ingrediente_producto')->where('producto_id', $product->id)->get()->toArray())->toEqual($pivot)
        ->and($product->fresh()->precio)->toEqual('15900');
    $this->get(route('admin.productos.edit', $product))->assertOk()->assertSee('3 bloques')->assertSee('Salmón')->assertSee('max repeticiones');
});

test('unit edits cannot invalidate configured blocks and pending products cannot activate', function () {
    $this->actingAs(adminOperationUser());
    $product = Producto::where('slug', '30-piezas-a-eleccion')->sole();
    $this->put(route('admin.productos.update', $product), ['nombre' => $product->nombre, 'precio' => $product->precio, 'categoria_id' => $product->categoria_id, 'unidades' => 40])->assertSessionHasErrors('unidades');
    foreach (['gohan-flecha', 'gohan-orion'] as $slug) {
        $gohan = Producto::where('slug', $slug)->sole();
        $this->patch(route('admin.productos.availability', $gohan), ['activo' => 1])->assertSessionHasErrors('activo');
        expect($gohan->fresh()->activo)->toBeFalse();
    }
});

test('ingredient 86 blocks new options but preserves history fixed products and prices', function () {
    $order = adminOperationOrder('hand-roll-tradicional');
    $item = $order->items->first();
    $snapshot = $item->snapshot;
    $salmon = Ingrediente::where('nombre', 'Salmón')->sole();
    $product = Producto::where('slug', 'hand-roll-tradicional')->sole();
    $option = $product->gruposOpciones->first()->opciones()->where('ingrediente_id', $salmon->id)->sole();
    $payload = ['product_id' => $product->id, 'option_ids' => [$option->id]];
    $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('precio_unit', 4990);
    $this->actingAs(adminOperationUser())->patch(route('admin.ingredientes.availability', $salmon), ['is_available' => 0])->assertRedirect()->assertSessionHasNoErrors();
    $this->postJson(route('cart.quote'), $payload)->assertUnprocessable()->assertSee('agotado');
    $schema = app(MenuConfigurationService::class)->schema($product->fresh());
    expect(collect($schema['groups'][0]['options'])->pluck('id'))->not->toContain($option->id);
    $fixed = Producto::where('slug', 'la-playa')->sole();
    $this->postJson(route('cart.quote'), ['product_id' => $fixed->id])->assertOk();
    $this->patch(route('admin.ingredientes.availability', $salmon), ['is_available' => 1])->assertSessionHasNoErrors();
    $this->postJson(route('cart.quote'), $payload)->assertOk()->assertJsonPath('precio_unit', 4990);
    expect($item->fresh()->snapshot)->toBe($snapshot)->and($fixed->fresh()->activo)->toBeTrue();
});

test('ingredient impact includes both fixed composition and option-only assignments', function () {
    $salmon = Ingrediente::where('nombre', 'Salmón')->sole();
    $response = $this->actingAs(adminOperationUser())->get(route('admin.ingredientes.edit', $salmon))->assertOk();
    $names = $response->viewData('affected')->pluck('slug');
    expect($names)->toContain('hand-roll-tradicional', 'la-playa', 'akilaburger');
});

test('configuration persists and validates thresholds without touching catalog', function () {
    $before = Producto::orderBy('id')->get()->toArray();
    $settings = array_replace(OperationSettings::DEFAULTS, ['estimate_min' => 35, 'estimate_max' => 50, 'message' => 'Mensaje de prueba']);
    $this->actingAs(adminOperationUser())->put(route('admin.settings.update'), $settings)->assertRedirect()->assertSessionHasNoErrors();
    expect(app(OperationSettings::class)->get()['estimate_min'])->toBe(35)->and(Producto::orderBy('id')->get()->toArray())->toBe($before);
    $this->put(route('admin.settings.update'), array_replace($settings, ['estimate_max' => 10]))->assertSessionHasErrors('estimate_max');
    $this->put(route('admin.settings.update'), array_replace($settings, ['load_high' => 10]))->assertSessionHasErrors('load_high');
});

test('reports exclude unpaid sales and kitchen metrics skip unsupported historical lines', function () {
    adminOperationOrder('goloso');
    adminOperationOrder('5-hand-rolls');
    adminOperationOrder('arrollados');
    adminOperationOrder('goloso', ['payment_status' => 'pending']);
    $response = $this->actingAs(adminOperationUser())->get(route('admin.reports', ['period' => 'today']))->assertOk();
    expect((float) $response->viewData('sales'))->toBe(33000.0)->and($response->viewData('sold')['pieces'])->toBe(20)
        ->and($response->viewData('sold')['hand_rolls'])->toBe(5)->and($response->viewData('sold')['units'])->toBe(8);
    $metrics = app(KitchenMetrics::class)->items(collect([(object) ['snapshot' => null, 'unidades' => 10]]));
    expect($metrics['uncovered'])->toBe(1)->and($metrics['pieces'])->toBe(0);
});

test('duplicate is an unpublished independent product with preserved blocks and shared option definitions', function () {
    $product = Producto::where('slug', '30-piezas-a-eleccion')->sole();
    $this->actingAs(adminOperationUser())->post(route('admin.productos.duplicate', $product))->assertRedirect()->assertSessionHasNoErrors();
    $copy = Producto::where('nombre', $product->nombre.' (copia)')->sole();
    expect($copy->activo)->toBeFalse()->and($copy->bloques)->toHaveCount(3)->and($copy->reglas)->toHaveCount($product->reglas()->count())
        ->and($copy->bloques->first()->id)->not->toBe($product->bloques->first()->id)
        ->and($copy->bloques->first()->gruposOpciones->pluck('id')->all())->toBe($product->bloques->first()->gruposOpciones->pluck('id')->all());
});
