<?php

namespace App\Commerce\Configuration;

use App\Commerce\Exceptions\ConfigurationException;
use App\Commerce\Pricing\CommerceMoney;

class ProductRuleEvaluator
{
    public function validate(array $rules, array $selections): void
    {
        foreach ($rules as $rule) {
            $matches = array_values(array_filter($selections, fn ($s) => $this->matches($rule, $s)));
            $context = ['name' => $matches[0]['name'] ?? '', 'limit' => (int) $rule['value']];
            match ($rule['type']) {
                'max_repetitions' => ConfigurationException::check(count($matches) <= (int) $rule['value'], 'max_repetitions', $context),
                'exclude' => ConfigurationException::check($matches === [], 'excluded_option', $context),
                'minimum_units' => ConfigurationException::check(array_sum(array_column($matches, 'measure')) >= (int) $rule['value'], 'rule_violation'),
                'incompatible' => ConfigurationException::check($matches === [] || array_intersect(
                    array_column($selections, 'option_id'), $rule['option_ids'] ?? []) === [], 'incompatible_options'),
                'surcharge', 'included_quantity' => null,
                default => throw new ConfigurationException('unsupported_rule'),
            };
        }
    }

    public function surcharge(array $option, array $group, array $rules, array $baseline): array
    {
        $charges = [];
        $base = CommerceMoney::cents($option['surcharge']);
        if ($base > 0) {
            $charges[] = ['source' => 'option', 'option_id' => $option['id'], 'name' => $option['name'],
                'amount' => CommerceMoney::decimal($base)];
        }
        foreach ($rules as $rule) {
            if ($rule['type'] !== 'surcharge' || ! $this->matches($rule, [
                'option_id' => $option['id'], 'component_id' => $option['component_id'],
                'count_component' => true,
            ])) {
                continue;
            }
            if (($rule['change_kind'] ?? null) !== null && (
                $rule['change_kind'] !== ($group['change_kind'] ?? null) || $baseline === []
                || in_array($option['id'], $baseline, true)
            )) {
                continue;
            }
            $amount = CommerceMoney::cents($rule['value']);
            if ($amount > 0) {
                $charges[] = ['source' => 'rule', 'rule_id' => $rule['id'], 'option_id' => $option['id'],
                    'name' => $option['name'], 'amount' => CommerceMoney::decimal($amount)];
            }
        }

        return $charges;
    }

    private function matches(array $rule, array $selection): bool
    {
        return $rule['subject_type'] === 'option'
            ? $rule['subject_id'] === $selection['option_id']
            : ($selection['count_component'] ?? false) && $rule['subject_id'] === $selection['component_id'];
    }
}
