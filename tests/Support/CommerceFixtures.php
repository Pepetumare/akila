<?php

namespace Tests\Support;

use App\Models\Categoria;
use App\Models\GrupoOpcion;
use App\Models\Opcion;
use App\Models\Producto;
use Illuminate\Support\Str;

class CommerceFixtures
{
    public static function product(string $name = 'Figura Luffy', int $price = 19990, bool $configurable = false): Producto
    {
        $category = Categoria::firstOrCreate(['nombre' => 'Catálogo general']);

        return Producto::create(['nombre' => $name, 'descripcion' => 'Producto de prueba', 'precio' => $price,
            'categoria_id' => $category->id, 'personalizable' => $configurable, 'unidad_tipo' => 'unidad',
            'unidades' => 1, 'activo' => true]);
    }

    public static function group(Producto $product, string $name, array $options, int $min = 1, int $max = 1): GrupoOpcion
    {
        $group = GrupoOpcion::create(['nombre' => $name, 'slug' => $product->slug.'-'.Str::slug($name),
            'tipo_seleccion' => $max > 1 ? 'multiple' : 'single',
            'min_selections' => $min, 'max_selections' => $max]);
        foreach ($options as $label => $surcharge) {
            Opcion::create(['grupo_opcion_id' => $group->id, 'nombre' => $label, 'slug' => Str::slug($label),
                'recargo' => $surcharge]);
        }
        $product->gruposOpciones()->attach($group);

        return $group->load('opciones');
    }

    public static function selected(GrupoOpcion $group, string ...$names): array
    {
        return $group->opciones->whereIn('nombre', $names)->pluck('id')->all();
    }
}
