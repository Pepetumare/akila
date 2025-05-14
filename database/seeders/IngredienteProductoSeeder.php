<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Ingrediente;
use Illuminate\Support\Facades\DB;

class IngredienteProductoSeeder extends Seeder
{
    public function run()
    {
        // Para cada producto, asignar todos los ingredientes básicos con límites
        // Ejemplo para los Hand Rolls (puedes extender a todos los productos según su tipo)
        $handRolls = Producto::whereIn('slug', ['3-hand-rolls','4-hand-rolls','5-hand-rolls','tradicional','relleno-doble','xl','lo-nuevo'])->get();
        $vegetales = Ingrediente::where('tipo','vegetal')->pluck('id')->all();
        $proteinas = Ingrediente::where('tipo','proteina')->pluck('id')->all();

        foreach ($handRolls as $prod) {
            $sync = [];

            // Base queso crema y cebollín sin límite (null)
            foreach (['queso crema','cebollín'] as $base) {
                $ing = Ingrediente::firstWhere('nombre', $base);
                if ($ing) $sync[$ing->id] = ['cantidad_permitida'=>null];
            }

            // Vegetales: max 2 veces cada uno
            foreach ($vegetales as $id) {
                $sync[$id] = ['cantidad_permitida'=>2];
            }
            // Proteínas: max 2, pero salmón/atún/carne sólo 1
            foreach ($proteinas as $id) {
                $ing = Ingrediente::find($id);
                $limite = in_array($ing->nombre, ['Salmon','Atún','Carne']) ? 1 : 2;
                $sync[$id] = ['cantidad_permitida'=>$limite];
            }

            $prod->ingredientes()->sync($sync);
        }
    }
}
