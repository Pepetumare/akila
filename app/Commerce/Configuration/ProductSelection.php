<?php

namespace App\Commerce\Configuration;

/** Canonical choices; purchase quantity is deliberately outside line identity. */
final readonly class ProductSelection
{
    public function __construct(
        public array $blocks = [],
        public array $optionIds = [],
        public array $removedComponentIds = [],
    ) {}

    public function toArray(): array
    {
        return ['version' => 2, 'blocks' => $this->blocks,
            'option_ids' => $this->optionIds, 'removed_component_ids' => $this->removedComponentIds];
    }
}
