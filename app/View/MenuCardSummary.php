<?php

namespace App\View;

use App\Enums\UnidadComercial;
use App\Models\Producto;

/** Presentation only. Never hydrates block configuration or computes prices. */
class MenuCardSummary
{
    public static function lines(Producto $product): array
    {
        $lines = [$product->unidades.' '.$product->unidad_tipo->label($product->unidades)];
        if ($product->personalizable && $product->bloques_count && $product->unidad_tipo === UnidadComercial::Pieza) {
            $uniform = $product->bloques_min_cantidad_piezas && $product->bloques_min_cantidad_piezas === $product->bloques_max_cantidad_piezas;
            $lines[] = $product->bloques_count.($uniform ? ' rolls de '.$product->bloques_min_cantidad_piezas : ' rolls para personalizar');
        }
        if ($product->relationLoaded('gruposOpciones') && $product->personalizable) {
            foreach ($product->gruposOpciones->where('activo', true) as $group) {
                [$min, $max] = $group->limites($group->pivot);
                if ($min < 1) {
                    continue;
                }
                $options = $group->opciones->where('activo', true)->sortBy('orden');
                if ($options->every(fn ($option) => $option->ingrediente_id === null)) {
                    $lines[] = $options->pluck('nombre')->implode(' / ');
                    if ($min === 1 && $max > 1 && $max === $options->count()) {
                        $lines[] = 'Uno o ambos sabores';
                    }
                } else {
                    $lines[] = $group->nombre.': '.($min === $max ? 'elige '.$min : $min.' a '.$max);
                }
            }
        }
        if ($product->relationLoaded('ingredientes')) {
            $ingredients = $product->ingredientes->sortBy('pivot.orden');
            if (! $product->personalizable) {
                $composition = $ingredients->filter(fn ($i) => $i->pivot->metadata['piezas_relleno'] ?? false);
                if ($composition->isNotEmpty()) {
                    $lines[] = $composition->map(fn ($i) => $i->pivot->metadata['piezas_relleno'].' '.$i->nombre)->implode(' · ');
                }
            }
            $base = $ingredients->filter(fn ($i) => $i->tipo === 'base' || ($i->pivot->metadata['composicion_fija'] ?? false));
            if ($base->isNotEmpty()) {
                $lines[] = ($product->personalizable ? 'Base: ' : 'Incluye: ').$base->pluck('nombre')->implode(' + ');
            }
        }

        return array_slice($lines, 0, 4);
    }
}
