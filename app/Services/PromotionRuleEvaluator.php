<?php

namespace App\Services;

use App\Commerce\Compatibility\SushiConfigurationAdapter;
use App\Commerce\Configuration\ProductRuleEvaluator;
use App\Commerce\Exceptions\ConfigurationException;
use App\Models\GrupoOpcion;
use App\Models\Opcion;
use App\Models\Producto;
use Illuminate\Support\Collection;

/** Compatibility API for catalog fixtures and current UI schema consumers. */
class PromotionRuleEvaluator
{
    public function __construct(private SushiConfigurationAdapter $compatibility, private ProductRuleEvaluator $rules) {}

    public function isFilling(GrupoOpcion $group): bool
    {
        return $this->compatibility->isFilling($group);
    }

    public function validate(Producto $product, array $selections): void
    {
        $normalized = array_map(fn ($s) => [
            'option_id' => (int) $s['option']->id, 'component_id' => $s['option']->ingrediente_id,
            'count_component' => $s['filling'], 'measure' => $s['pieces'],
            'name' => $s['option']->ingrediente?->nombre ?? $s['option']->nombre,
        ], $selections);
        try {
            $this->rules->validate($this->compatibility->rules($product), $normalized);
        } catch (ConfigurationException $e) {
            $this->compatibility->translate($e);
        }
    }

    public function surcharge(Opcion $option, GrupoOpcion $group, Collection $rules, array $baseline): array
    {
        return $this->compatibility->surcharge($option, $group, $rules, $baseline);
    }
}
