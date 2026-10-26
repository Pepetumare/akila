<?php

namespace App\Services;

use App\Models\Ingrediente;
use App\Models\Producto;

class IngredientImpact
{
    public function products(Ingrediente $ingredient)
    {
        return Producto::where(function ($query) use ($ingredient) {
            $query->whereHas('ingredientes', fn ($q) => $q->where('ingredientes.id', $ingredient->id))
                ->orWhereHas('gruposOpciones.opciones', fn ($q) => $q->where('ingrediente_id', $ingredient->id))
                ->orWhereHas('bloques.gruposOpciones.opciones', fn ($q) => $q->where('ingrediente_id', $ingredient->id));
        })->with('categoria')->orderBy('nombre')->get();
    }
}
