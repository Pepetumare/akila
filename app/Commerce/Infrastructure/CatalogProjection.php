<?php

namespace App\Commerce\Infrastructure;

use App\Commerce\Exceptions\ConfigurationException;
use App\Models\GrupoOpcion;
use App\Models\Opcion;
use App\Models\Producto;
use Illuminate\Support\Collection;

/** Relational catalog mapping. Labels, slugs and units never select a commercial policy. */
class CatalogProjection
{
    public function group(GrupoOpcion $group): array
    {
        [$min, $max] = $group->limites($group->pivot);

        return ['id' => (int) $group->id, 'name' => $group->nombre, 'active' => $group->activo,
            'min' => $min, 'max' => $max, 'count_component' => $group->catalog_policy['count_component'] ?? true,
            'change_kind' => $group->catalog_policy['change_kind'] ?? 'primary',
            'options' => $group->opciones->map(fn ($o) => $this->option($o))->all()];
    }

    public function option(Opcion $option): array
    {
        return ['id' => (int) $option->id, 'name' => $option->nombre, 'active' => $option->activo,
            'surcharge' => (string) $option->recargo,
            'available' => $option->disponible && $option->ingrediente?->is_available !== false,
            'catalog_available' => $option->disponible,
            'component_id' => $option->ingrediente_id ? (int) $option->ingrediente_id : null,
            'component_type' => $option->ingrediente?->tipo, 'component_name' => $option->ingrediente?->nombre];
    }

    public function rules(Producto $product): array
    {
        return $this->mapRules($product->reglas)->filter(fn ($r) =>
            ! ($r['configurable_only'] ?? false) || $product->personalizable)->values()->all();
    }

    public function mapRules(Collection $rules): Collection
    {
        return $rules->where('activo', true)->map(function ($rule) {
            $type = match ($rule->tipo->value) {
                'max_repeticiones' => 'max_repetitions', 'excluir' => 'exclude', 'recargo' => 'surcharge',
                'min_piezas' => 'minimum_units', 'cantidad_incluida' => 'included_quantity',
                'incompatible' => 'incompatible', default => throw new ConfigurationException('unsupported_rule'),
            };

            return ['id' => (int) $rule->id, 'type' => $type, 'value' => (string) $rule->valor,
                'subject_type' => $rule->opcion_id !== null ? 'option' : 'component',
                'subject_id' => (int) ($rule->opcion_id ?? $rule->ingrediente_id),
                'change_kind' => $rule->metadata['change_kind'] ?? null,
                'configurable_only' => $rule->metadata['configurable_only'] ?? false,
                'display_only' => $rule->metadata['display_only'] ?? false,
                'extra_unit_price' => $rule->metadata['extra_unit_price'] ?? null,
                'option_ids' => array_map('intval', $rule->metadata['option_ids'] ?? [])];
        });
    }
}
