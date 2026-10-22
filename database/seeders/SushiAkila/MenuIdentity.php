<?php

namespace Database\Seeders\SushiAkila;

use App\Models\Ingrediente;
use App\Models\Producto;
use Illuminate\Support\Str;
use RuntimeException;

class MenuIdentity
{
    public static function ingredientKey(string $name): string
    {
        $key = Str::slug(trim($name));

        return [
            'palmitos' => 'palmito', 'champinones' => 'champinon',
            'camarones' => 'camaron', 'proteinas' => 'proteina',
        ][$key] ?? $key;
    }

    public static function ingredient(string $name, string $type): Ingrediente
    {
        $matches = Ingrediente::all()->filter(fn ($ingredient) => self::ingredientKey($ingredient->nombre) === self::ingredientKey($name));
        if ($matches->count() > 1) {
            throw new RuntimeException("Hay ingredientes equivalentes duplicados para {$name}; resolver sus referencias sin borrado automático.");
        }
        $ingredient = $matches->first() ?? new Ingrediente;
        $ingredient->fill(['nombre' => $name, 'tipo' => $type]);
        if (! $ingredient->exists || $ingredient->isDirty()) {
            $ingredient->save();
        }

        return $ingredient;
    }

    public static function product(string $slug, array $attributes): Producto
    {
        $product = Producto::where('slug', $slug)->first();
        if (! $product) {
            $aliases = $slug === 'lo-nuevo' ? ['lo-nuevo', 'nuevo-cortes'] : [$slug];
            $matches = Producto::all()->filter(fn ($p) => in_array(Str::slug($p->nombre), $aliases, true));
            if ($matches->count() > 1) {
                throw new RuntimeException("Hay varios productos candidatos para {$slug}; no se fusionan automáticamente.");
            }
            $product = $matches->first() ?? new Producto;
        }
        $product->fill($attributes);
        if (! $product->exists || $product->isDirty()) {
            $product->save();
        }
        // The legacy name observer regenerates slugs. Import identity is authoritative.
        if ($product->slug !== $slug) {
            $product->forceFill(['slug' => $slug])->saveQuietly();
        }

        return $product;
    }
}
