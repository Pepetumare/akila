<?php

namespace App\Services;

use App\Enums\TipoReglaPromocion;
use App\Models\GrupoOpcion;
use App\Models\Opcion;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PromotionRuleEvaluator
{
    /** Existing catalog group roles; ingredients may be both filling and wrapper. */
    public function isFilling(GrupoOpcion $group): bool
    {
        return $group->slug !== 'envoltura' && $group->slug !== 'preparacion'
            && ! str_starts_with($group->slug, 'envoltura-');
    }

    public function validate(Producto $product, array $selections): void
    {
        foreach ($product->reglas->where('activo', true) as $rule) {
            $matches = collect($selections)->filter(function ($selection) use ($rule) {
                $option = $selection['option'];

                return $rule->opcion_id !== null ? (int) $rule->opcion_id === (int) $option->id
                    : $selection['filling'] && (int) $rule->ingrediente_id === (int) $option->ingrediente_id;
            });
            if ($rule->tipo === TipoReglaPromocion::MaxRepeticiones && $product->personalizable
                && $matches->count() > (int) $rule->valor) {
                $name = $matches->first()['option']->ingrediente?->nombre ?? $matches->first()['option']->nombre;
                throw ValidationException::withMessages(['blocks' => "{$name} puede repetirse como máximo ".(int) $rule->valor.' '.((int) $rule->valor === 1 ? 'vez' : 'veces').' en esta promoción.']);
            }
            if ($rule->tipo === TipoReglaPromocion::Excluir && $matches->isNotEmpty()) {
                throw ValidationException::withMessages(['blocks' => $matches->first()['option']->nombre.' no está disponible en esta promoción.']);
            }
            if ($rule->tipo === TipoReglaPromocion::MinPiezas && $matches->sum('pieces') < (int) $rule->valor) {
                throw ValidationException::withMessages(['blocks' => 'La elección no cumple la cantidad de piezas requerida por esta promoción.']);
            }
        }
    }

    public function surcharge(Opcion $option, GrupoOpcion $group, Collection $rules, array $baseline): array
    {
        $charges = [];
        $base = MenuMoney::cents($option->recargo);
        if ($base > 0) {
            $charges[] = ['source' => 'option', 'option_id' => $option->id, 'name' => $option->nombre, 'amount' => MenuMoney::decimal($base)];
        }
        foreach ($rules->where('activo', true)->where('tipo', TipoReglaPromocion::Recargo) as $rule) {
            $matches = $rule->opcion_id !== null ? (int) $rule->opcion_id === (int) $option->id
                : (int) $rule->ingrediente_id === (int) $option->ingrediente_id;
            if (! $matches) {
                continue;
            }
            $condition = $rule->metadata['condicion'] ?? null;
            if ($condition !== null) {
                // No baseline means an initial choice, not a paid modification.
                $kind = $this->isFilling($group) ? 'modificacion_relleno' : 'modificacion_envoltura';
                if ($condition !== $kind || $baseline === [] || in_array((int) $option->id, $baseline, true)) {
                    continue;
                }
            }
            $amount = MenuMoney::cents($rule->valor);
            if ($amount > 0) {
                $charges[] = ['source' => 'rule', 'rule_id' => $rule->id, 'option_id' => $option->id,
                    'name' => $option->nombre, 'amount' => MenuMoney::decimal($amount)];
            }
        }

        return $charges;
    }
}
