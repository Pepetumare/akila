<?php

namespace App\Commerce\Configuration;

use App\Commerce\Catalog\ProductDefinition;
use App\Commerce\Exceptions\ConfigurationException as Error;
use App\Commerce\Pricing\CommerceMoney;
use App\Commerce\Pricing\PricingService;
use App\Commerce\Pricing\ProductQuote;

class ProductConfigurationService
{
    public function __construct(private ProductRuleEvaluator $rules, private PricingService $pricing) {}

    public function quote(ProductDefinition $product, ProductConfigurationInput $input, int $quantity = 1): ProductQuote
    {
        Error::check($product->available, 'product_unavailable');
        Error::check($quantity >= 1 && $quantity <= PricingService::MAX_QUANTITY, 'invalid_quantity');
        $submitted = collect($input->blocks ?? []);
        $ids = $submitted->pluck('block_id')->sort()->values()->all();
        $expected = collect($product->blocks)->pluck('id')->sort()->values()->all();
        if ($input->blocks !== null || $product->configurable) {
            Error::check($ids === $expected, 'invalid_blocks');
        }
        $submitted = $submitted->keyBy('block_id');
        $normalized = $readable = $selections = $charges = [];
        foreach (collect($product->blocks)->sortBy('id') as $block) {
            if ($product->policies['matching_units'] ?? false) {
                Error::check($block['unit_type'] === $product->unitType, 'content_mismatch');
            }
            [$optionIds, $groups] = $this->select($product, $block['groups'],
                $submitted->get($block['id'])['option_ids'] ?? null, $block['name'],
                $block['measure'], $selections, $charges);
            $normalized[] = ['block_id' => $block['id'], 'quantity' => $block['quantity'],
                'unit_type' => $block['unit_type'], 'option_ids' => $optionIds];
            $readable[] = ['block_id' => $block['id'], 'name' => $block['name'], 'order' => $block['order'],
                'quantity' => $block['quantity'], 'unit_type' => $block['unit_type'], 'groups' => $groups];
        }
        if ($product->policies['sum_content'] ?? false) {
            Error::check($product->blocks === [] || array_sum(array_column($product->blocks, 'quantity')) === $product->includedQuantity, 'content_mismatch');
        }
        [$optionIds, $groups] = $this->select($product, $product->groups, $input->optionIds,
            'este producto', 0, $selections, $charges);
        $removed = $input->removedComponentIds;
        sort($removed, SORT_NUMERIC);
        $allowed = array_column(array_filter($product->components, fn ($c) => $c['removable']), 'id');
        Error::check(array_diff($removed, $allowed) === [], 'invalid_removal');
        $this->rules->validate($product->rules, $selections);
        $selection = new ProductSelection($normalized, $optionIds, $removed);

        return $this->pricing->quote($product, $selection, $quantity, [
            'components' => array_values(array_filter($product->components, fn ($c) => ! in_array($c['id'], $removed, true))),
            'unit_type' => $product->unitType, 'included_quantity' => $product->includedQuantity,
            'blocks' => collect($readable)->sortBy('order')->values()->all(), 'groups' => $groups,
            'removed_components' => array_values(array_filter($product->components, fn ($c) => in_array($c['id'], $removed, true))),
        ], $charges);
    }

    private function select(ProductDefinition $product, array $groups, ?array $submitted, string $label,
        int $measure, array &$selections, array &$charges): array
    {
        $context = ['label' => $label];
        Error::check(collect($groups)->every(fn ($g) => $g['active']), 'group_unavailable', $context);
        $ids = $submitted ?? [];
        Error::check(count($ids) === count(array_unique($ids)), 'duplicate_selection', $context);
        $allowed = collect($groups)->flatMap(fn ($g) => collect($g['options'])->where('active', true)->pluck('id'))->all();
        Error::check(array_diff($ids, $allowed) === [], 'invalid_option', $context);
        $start = count($selections);
        $result = [];
        foreach (collect($groups)->sortBy('id') as $group) {
            $options = collect($group['options'])->where('active', true);
            $min = $group['min'];
            $max = $group['max'];
            $baseline = $options->count() === 1 && $min === 1 ? [$options->first()['id']] : [];
            $pickedIds = array_values(array_intersect($ids, $options->pluck('id')->all()));
            if (! $product->configurable && $min > 0) {
                Error::check($baseline !== [], 'invalid_fixed_configuration', $context);
                Error::check($submitted === null || $pickedIds === $baseline, 'fixed_configuration', $context);
                $pickedIds = $baseline;
            }
            $limits = $context + ['group' => $group['name'], 'min' => $min, 'max' => $max];
            Error::check(count($pickedIds) >= $min, 'required_selection', $limits);
            Error::check(count($pickedIds) <= $max, 'too_many_selections', $limits);
            $picked = [];
            foreach ($options->whereIn('id', $pickedIds)->sortBy('id') as $option) {
                Error::check((! $product->configurable && $min > 0) || $option['available'],
                    'option_unavailable', ['name' => $option['component_name'] ?? $option['name']]);
                $optionCharges = $this->rules->surcharge($option, $group, $product->rules, $baseline);
                array_push($charges, ...$optionCharges);
                $selections[] = ['option_id' => $option['id'], 'component_id' => $option['component_id'],
                    'count_component' => $group['count_component'], 'measure' => $measure,
                    'name' => $option['component_name'] ?? $option['name']];
                $picked[] = ['option_id' => $option['id'], 'name' => $option['name'],
                    'component_id' => $option['component_id'], 'component_type' => $option['component_type'],
                    'surcharge' => CommerceMoney::decimal(array_sum(array_map(fn ($c) => CommerceMoney::cents($c['amount']), $optionCharges)))];
            }
            $result[] = ['group_id' => $group['id'], 'name' => $group['name'], 'options' => $picked];
        }
        if ($product->configurable && ($product->policies['unique_components_per_scope'] ?? false)) {
            $components = collect(array_slice($selections, $start))->where('count_component', true)->pluck('component_id')->filter();
            Error::check($components->count() === $components->unique()->count(), 'duplicate_component');
        }

        return [collect($result)->flatMap(fn ($g) => array_column($g['options'], 'option_id'))->sort()->values()->all(), $result];
    }
}
