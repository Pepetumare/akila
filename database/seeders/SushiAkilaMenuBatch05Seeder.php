<?php

namespace Database\Seeders;

use App\Models\Producto;
use Database\Seeders\SushiAkila\Batch05Data;
use Database\Seeders\SushiAkila\MenuBatchImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SushiAkilaMenuBatch05Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            app(MenuBatchImporter::class)->run(Batch05Data::products(), 'batch05', ['Arroz' => 'base'], Batch05Data::groups());
            // Descriptive product notes live with its confirmed base, not as executable substitutions.
            foreach (['gohan-flecha', 'gohan-orion'] as $slug) {
                $product = Producto::where('slug', $slug)->sole();
                $rice = $product->ingredientes()->where('nombre', 'Arroz')->sole();
                $product->ingredientes()->updateExistingPivot($rice->id, ['metadata' => array_merge($rice->pivot->metadata, [
                    'pendiente' => Batch05Data::GOHAN_PENDING,
                    'opcion_vegetariana' => ['disponible' => true, 'descripcion' => 'Disponible opción vegetariana',
                        'estado' => 'pendiente_definicion_operativa'],
                ])]);
            }
        });
    }
}
