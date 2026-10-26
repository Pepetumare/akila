<?php

namespace Database\Seeders;

use App\Enums\TipoProducto;
use App\Enums\UnidadComercial;
use App\Models\GrupoOpcion;
use App\Models\Producto;
use Database\Seeders\SushiAkila\Batch01Data;
use Database\Seeders\SushiAkila\Batch02Data;
use Database\Seeders\SushiAkila\Batch03Data;
use Database\Seeders\SushiAkila\Batch04Data;
use Database\Seeders\SushiAkila\MenuBatchImporter;
use Database\Seeders\SushiAkila\MenuIdentity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SushiAkilaMenuBatch04Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            app(MenuBatchImporter::class)->run(Batch04Data::products(), 'batch04', Batch04Data::ingredients(), Batch04Data::groups());
            $extras = $this->group('ingredientes-extras', 'Ingredientes extras', 11);
            foreach (Batch04Data::extras() as $name => $price) {
                $this->option($extras, $name, $name, $price);
            }
            // Price catalog only: no blanket assignment of ingredient extras to products.
            $sauces = $this->group('salsas-adicionales', 'Salsas adicionales', 2);
            foreach (['Salsa de soya' => '500.00', 'Salsa agridulce' => '800.00'] as $name => $price) {
                $this->option($sauces, $name.' adicional', $name, $price);
            }
            $menuSlugs = array_keys(array_merge(Batch01Data::products(), Batch02Data::products(), Batch03Data::products(), Batch04Data::products()));
            foreach (Producto::whereIn('slug', $menuSlugs)->where('tipo', TipoProducto::Promocion->value)->where('unidad_tipo', UnidadComercial::Pieza->value)
                ->whereHas('categoria', fn ($q) => $q->whereIn('slug', ['promociones', 'promociones-a-eleccion']))->get() as $product) {
                $product->gruposOpciones()->syncWithoutDetaching([$sauces->id => [
                    'min_selections' => 0, 'max_selections' => 2, 'obligatorio' => false, 'orden' => 100,
                ]]);
                $counts = Batch04Data::sauces($product->unidades);
                if ($counts === null) {
                    continue; // No invented quantities for 150 pieces.
                }
                foreach (array_combine(['Salsa de soya', 'Salsa agridulce'], $counts) as $name => $count) {
                    $ingredient = MenuIdentity::ingredient($name, 'salsa');
                    $product->reglas()->updateOrCreate(['codigo' => 'incluida-'.Str::slug($name)], [
                        'tipo' => 'cantidad_incluida', 'ingrediente_id' => $ingredient->id, 'opcion_id' => null,
                        'valor' => $count, 'activo' => true, 'metadata' => ['importacion' => 'batch04', 'foto' => 31,
                            'uso' => 'salsa_incluida', 'unidad' => 'pote', 'precio' => '0.00', 'intercambiable' => false],
                    ]);
                }
            }
        });
    }

    private function group(string $slug, string $name, int $max): GrupoOpcion
    {
        return GrupoOpcion::updateOrCreate(['slug' => $slug], ['nombre' => $name, 'tipo_seleccion' => 'multiple',
            'min_selections' => 0, 'max_selections' => $max, 'activo' => true, 'orden' => 100]);
    }

    private function option(GrupoOpcion $group, string $label, string $ingredient, string $price): void
    {
        $type = Batch04Data::ingredients()[$ingredient] ?? Batch01Data::ingredients()[$ingredient];
        $group->opciones()->updateOrCreate(['slug' => Str::slug($label)], ['nombre' => $label,
            'ingrediente_id' => MenuIdentity::ingredient($ingredient, $type)->id, 'recargo' => $price, 'activo' => true]);
    }
}
