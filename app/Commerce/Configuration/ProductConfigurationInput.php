<?php

namespace App\Commerce\Configuration;

use Illuminate\Support\Facades\Validator;

/** Transport-independent structural validation. Amounts supplied by callers are never retained. */
final readonly class ProductConfigurationInput
{
    public function __construct(
        public ?array $blocks = null,
        public ?array $optionIds = null,
        public array $removedComponentIds = [],
    ) {}

    public static function fromArray(array $input): self
    {
        $data = Validator::make($input, [
            'version' => 'sometimes|integer|in:2',
            'blocks' => 'sometimes|array|list|max:100',
            'blocks.*.block_id' => 'required|integer|distinct',
            'blocks.*.option_ids' => 'required|array|list|max:100',
            'blocks.*.option_ids.*' => 'required|integer',
            'option_ids' => 'sometimes|array|list|max:100',
            'option_ids.*' => 'required|integer|distinct',
            'removed_component_ids' => 'sometimes|array|list|max:100',
            'removed_component_ids.*' => 'required|integer|distinct',
        ])->validate();

        return new self(
            isset($data['blocks']) ? array_map(fn ($b) => [
                'block_id' => (int) $b['block_id'], 'option_ids' => array_map('intval', $b['option_ids']),
            ], $data['blocks']) : null,
            isset($data['option_ids']) ? array_map('intval', $data['option_ids']) : null,
            array_map('intval', $data['removed_component_ids'] ?? []),
        );
    }
}
