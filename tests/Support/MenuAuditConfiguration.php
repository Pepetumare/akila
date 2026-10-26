<?php

namespace Tests\Support;

use App\Models\Producto;
use App\Services\MenuConfigurationService;
use App\Services\PromotionRuleEvaluator;
use RuntimeException;

/** Test/audit fixture only: completes required choices from assigned catalog relations. */
class MenuAuditConfiguration
{
    public static function payload(Producto $product): array
    {
        $product->loadMissing(MenuConfigurationService::RELATIONS);
        $payload = ['product_id' => $product->id, 'unidades' => 1];
        if (! $product->personalizable) {
            return $payload;
        }
        $counts = [];
        $limits = $product->reglas->where('activo', true)->filter(fn ($r) => $r->tipo->value === 'max_repeticiones')
            ->pluck('valor', 'ingrediente_id');
        $choose = function ($groups) use (&$counts, $limits) {
            $ids = [];
            $local = [];
            foreach ($groups as $group) {
                [$min] = $group->limites($group->pivot);
                $filling = app(PromotionRuleEvaluator::class)->isFilling($group);
                $picked = 0;
                foreach ($group->opciones->where('activo', true)->sortBy('id') as $option) {
                    if ($picked >= $min) {
                        break;
                    }
                    $ingredient = $option->ingrediente_id;
                    if ($filling && $ingredient && (in_array($ingredient, $local, true)
                        || ($counts[$ingredient] ?? 0) >= (int) ($limits[$ingredient] ?? PHP_INT_MAX))) {
                        continue;
                    }
                    $ids[] = $option->id;
                    $picked++;
                    if ($filling && $ingredient) {
                        $counts[$ingredient] = ($counts[$ingredient] ?? 0) + 1;
                        $local[] = $ingredient;
                    }
                }
                if ($picked < $min) {
                    throw new RuntimeException('No valid fixture for '.$group->slug);
                }
            }

            return $ids;
        };
        $payload['blocks'] = $product->bloques->map(fn ($block) => ['block_id' => $block->id,
            'option_ids' => $choose($block->gruposOpciones)])->all();
        $payload['option_ids'] = $choose($product->gruposOpciones);

        return $payload;
    }
}
