<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run()
    {
        // Obtener la categoría “Hand Roll”
        $categoria = Categoria::where('slug','hand-roll')->first();

        Producto::create([
            'nombre'         => 'Hand Roll de Salmón',
            'descripcion'    => 'Delicioso hand roll con salmón fresco, queso crema y cebollín.',
            'precio'         => 10000,
            'imagen'         => 'sushi.jpg',
            'categoria_id'   => $categoria->id,     // <— aquí
            'personalizable' => true,
        ]);
    }
}
