<?php

use App\Models\GrupoOpcion;
use App\Models\Producto;
use Database\Seeders\SushiAkilaMenuSeeder;
use Tests\Support\MenuAuditConfiguration;

test('neutral engine preserves the nine representative menu products and their prices', function () {
    $this->seed(SushiAkilaMenuSeeder::class);
    $prices = ['20-piezas-a-eleccion' => 10000, '30-piezas-a-eleccion' => 14900,
        '60-piezas-a-eleccion' => 25990, '5-hand-rolls' => 18000, 'muchupleto' => 6500,
        'akilaburger' => 7500, 'arrollados' => 5000, 'noche-de-sushi' => 17990, 'costanera' => 14900];
    foreach ($prices as $slug => $price) {
        $product = Producto::where('slug', $slug)->sole();
        $payload = MenuAuditConfiguration::payload($product);
        $this->postJson(route('cart.quote'), $payload)->assertOk()
            ->assertJsonPath('precio_unit', $price)->assertJsonPath('recargo', 0);
    }
});

test('neutral engine preserves thirty piece repetitions and traditional premium options as catalog data', function () {
    $this->seed(SushiAkilaMenuSeeder::class);
    $product = Producto::where('slug', '30-piezas-a-eleccion')->sole();
    expect($product->bloques()->count())->toBe(3);
    $options = GrupoOpcion::where('slug', 'relleno-30-piezas-a-eleccion')->sole()->opciones->keyBy('slug');
    $payload = fn (array $names) => ['product_id' => $product->id, 'unidades' => 1,
        'blocks' => $product->bloques->values()->map(fn ($b, $i) => [
            'block_id' => $b->id, 'option_ids' => [$options[$names[$i]]->id],
        ])->all()];
    $this->postJson(route('cart.quote'), $payload(['pollo', 'camaron', 'pollo']))
        ->assertOk()->assertJsonPath('precio_unit', 14900);
    $this->postJson(route('cart.quote'), $payload(['pollo', 'pollo', 'pollo']))->assertUnprocessable()->assertSee('2 veces');
    $this->postJson(route('cart.quote'), $payload(['salmon', 'salmon', 'pollo']))->assertUnprocessable()->assertSee('1 vez');
    $premium = Producto::where('slug', 'hand-roll-tradicional')->sole();
    foreach (['salmon', 'atun'] as $slug) {
        $id = $premium->gruposOpciones->flatMap->opciones->firstWhere('slug', $slug)->id;
        $this->postJson(route('cart.quote'), ['product_id' => $premium->id, 'option_ids' => [$id]])
            ->assertOk()->assertJsonPath('precio_unit', 4990);
    }
    // Renaming a subject never changes a stored repetition rule.
    $options['salmon']->ingrediente->update(['nombre' => 'Nombre comercial editado']);
    $this->postJson(route('cart.quote'), $payload(['salmon', 'salmon', 'pollo']))->assertUnprocessable();
});
