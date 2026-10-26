<?php

use App\Models\Categoria;
use App\Models\Producto;
use App\View\MenuCardSummary;
use Database\Seeders\SushiAkilaMenuSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Support\MenuAuditConfiguration;

beforeEach(function () {
    $this->seed(SushiAkilaMenuSeeder::class);
});

test('public category chips exclude every empty or inactive category and count active products', function () {
    Categoria::create(['nombre' => 'Categoría vacía', 'activo' => true]);
    $response = $this->get(route('menu'))->assertOk();
    $categories = $response->viewData('categorias');
    expect($categories)->toHaveCount(6)->and($categories->sum('disponibles_count'))->toBe(35);
    expect($categories->pluck('slug')->all())->not->toContain('gohan', 'categoria-vacia');
    expect(Categoria::where('slug', 'gohan')->exists())->toBeTrue();
});

test('cards show bounded structured metadata and Spanish pagination instead of full descriptions', function () {
    $this->get(route('menu'))->assertOk()->assertSee('35 productos disponibles')
        ->assertSee('Mostrando 1–12 de 35 productos')->assertDontSee('Showing')
        ->assertSee('Buscar producto...')->assertSee('akila-card-metadata');
    $response = $this->get(route('menu', ['q' => '30 Piezas a Elección']))->assertOk();
    $product = $response->viewData('productos')->sole();
    expect(MenuCardSummary::lines($product))->toContain('30 piezas', '3 rolls de 10', 'Base: Queso crema + Cebollín')
        ->toHaveCount(3);
    $response->assertDontSee($product->descripcion)->assertSee('Personalizar');
});

test('fixed cards summarize confirmed composition without required selectors', function () {
    $response = $this->get(route('menu', ['q' => 'Goloso']))->assertOk()->assertSee('Ver y agregar');
    expect(MenuCardSummary::lines($response->viewData('productos')->sole()))
        ->toContain('20 piezas', '20 Pollo · 20 Palta');
    $product = Producto::where('slug', 'goloso')->sole();
    $this->get(route('producto.modal', $product))->assertOk()->assertSee('Composición incluida')
        ->assertDontSee('data-menu-block=')->assertSee('role="dialog"', false)->assertSee('aria-modal="true"', false);
});

test('flavor cards show both commercial flavors without inventing proportions', function () {
    $response = $this->get(route('menu', ['q' => 'Arrollados']))->assertOk();
    expect(MenuCardSummary::lines($response->viewData('productos')->sole()))
        ->toBe(['8 unidades', 'Primavera / Jamón Queso', 'Uno o ambos sabores']);
    $response->assertDontSee('4+4');
});

test('search sorting pagination and categories preserve their filters', function () {
    $this->get(route('menu', ['q' => 'Nikkei', 'filter' => 'nikkei', 'sort' => 'price_desc']))
        ->assertOk()->assertViewHas('productos', fn ($p) => $p->total() === 4 && $p->every(fn ($p) => str_contains($p->nombre, 'Nikkei')));
    $this->get(route('menu', ['q' => 'Gohan']))->assertOk()->assertViewHas('productos', fn ($p) => $p->total() === 0);
    $this->get(route('menu', ['q' => ['unexpected']]))->assertOk();
});

test('menu metadata uses bounded eager queries and never hydrates block options for cards', function () {
    DB::enableQueryLog();
    $this->get(route('menu'))->assertOk();
    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();
    expect($queries->filter(fn ($q) => str_contains($q, 'ingrediente_producto')))->toHaveCount(1);
    expect($queries->filter(fn ($q) => str_contains($q, 'bloque_grupo_opcion')))->toHaveCount(0);
    expect($queries->filter(fn ($q) => str_contains($q, 'from "opciones"')))->toHaveCount(1);
});

test('repeated quotes preserve the configuration contract and never mutate a cart', function () {
    $product = Producto::where('slug', '60-piezas-a-eleccion')->sole();
    $payload = MenuAuditConfiguration::payload($product);
    $first = $this->postJson(route('cart.quote'), $payload)->assertOk()->json();
    for ($i = 0; $i < 5; $i++) {
        $quote = $this->postJson(route('cart.quote'), $payload + ['price' => 1])->assertOk()->json();
        expect($quote['configuration'])->toBe($first['configuration'])->and($quote['total'])->toBe($first['total']);
    }
    expect(session('cart', []))->toBe([]);
});

test('reference layout uses an existing banner and generic local fallbacks without illustrative commercial badges', function () {
    $response = $this->get(route('menu'))->assertOk()
        ->assertSee('img/slide3.jpg')->assertSee('akila-category-panel')->assertSee('akila-card-cta')
        ->assertSee('img/logo/logo-dark-transparent.png')->assertDontSee('Top Seller');
    expect($response->viewData('categorias')->sum('disponibles_count'))->toBe(35);
});

test('reference ingredient tabs have unique accessible ids while fixed products retain composition and preparation', function () {
    $product = Producto::where('slug', '30-piezas-a-eleccion')->sole();
    $response = $this->get(route('producto.modal', $product))->assertOk()
        ->assertSee('role="tablist"', false)->assertSee('role="tabpanel"', false)
        ->assertSee('data-option-tab="proteina"', false)->assertSee('data-option-tab="vegetal"', false)
        ->assertSee('Tu selección')->assertSee('Precio base')->assertSee('Adicionales');
    preg_match_all('/\sid="([^"]+)"/', $response->getContent(), $ids);
    expect($ids[1])->toHaveCount(count(array_unique($ids[1])));
    $fixed = Producto::where('slug', 'noche-de-sushi')->sole();
    $this->get(route('producto.modal', $fixed))->assertOk()->assertSee('Composición incluida')
        ->assertSee('Preparación')->assertSee('40 piezas')->assertDontSee('data-menu-block=');
});

test('reference cart renders one quantity form per line and preserves mixed flavors and server totals', function () {
    $product = Producto::where('slug', 'arrollados')->sole();
    $payload = MenuAuditConfiguration::payload($product);
    $payload['option_ids'] = $product->gruposOpciones->sole()->opciones->pluck('id')->all();
    $this->postJson(route('cart.add'), $payload)->assertOk();
    $before = session('cart');
    $response = $this->get(route('cart.index'))->assertOk()->assertSee('akila-cart-item')
        ->assertSee('8 unidades')->assertSee('Mixto')->assertSee('Primavera, Jamón Queso')->assertSee('$5.000');
    expect(substr_count($response->getContent(), 'class="akila-cart-update"'))->toBe(1)
        ->and(substr_count($response->getContent(), 'name="unidades"'))->toBe(2)
        ->and(session('cart'))->toBe($before);
});
