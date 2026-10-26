<?php

namespace App\Commerce\Catalog;

/**
 * A sellable Commerce product, never a stock item or an authenticated user.
 * Groups/options/blocks/rules are explicit scalar projections, not Eloquent serialization.
 */
final readonly class ProductDefinition
{
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public string $slug,
        public string $basePrice,
        public bool $available,
        public bool $configurable,
        public string $unitType,
        public int $includedQuantity,
        public array $groups = [],
        public array $blocks = [],
        public array $components = [],
        public array $rules = [],
        public array $policies = [],
    ) {}
}
