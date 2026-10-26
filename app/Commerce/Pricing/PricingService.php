<?php

namespace App\Commerce\Pricing;

use App\Commerce\Catalog\ProductDefinition;
use App\Commerce\Configuration\ProductSelection;
use App\Commerce\Exceptions\ConfigurationException;

class PricingService
{
    public const MAX_QUANTITY = 50;

    /** The only base + modifiers + purchase quantity arithmetic, also used by compatibility. */
    public function quote(ProductDefinition $product, ProductSelection $selection, int $quantity,
        array $details = [], array $charges = [], ?array $configuration = null): ProductQuote
    {
        ConfigurationException::check($product->available, 'product_unavailable');
        ConfigurationException::check($quantity >= 1 && $quantity <= self::MAX_QUANTITY, 'invalid_quantity');
        $modifiers = array_sum(array_map(fn ($charge) => CommerceMoney::cents($charge['amount']), $charges));

        return new ProductQuote($product->id, $product->name, $quantity, CommerceMoney::cents($product->basePrice),
            $modifiers, $selection, $details, $charges, $configuration);
    }
}
