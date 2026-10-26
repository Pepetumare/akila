<?php

namespace App\Commerce\Orders;

use App\Commerce\Catalog\ProductDefinition;
use App\Commerce\Pricing\CommerceMoney;
use App\Commerce\Pricing\ProductQuote;

class OrderItemSnapshotService
{
    /** Values are captured once. Readers require no current catalog or option relations. */
    public function fromQuote(ProductDefinition $product, ProductQuote $quote): array
    {
        return $this->build([
            'id' => $product->id, 'name' => $quote->productName, 'description' => $product->description,
            'slug' => $product->slug, 'unit_type' => $product->unitType, 'included_quantity' => $product->includedQuantity,
        ], ($quote->configuration['version'] ?? null) === 2 ? 2 : 1, $quote->quantity, [
            'base_unit' => CommerceMoney::decimal($quote->basePrice),
            'surcharge_unit' => CommerceMoney::decimal($quote->modifierTotal),
            'final_unit' => CommerceMoney::decimal($quote->finalUnitPrice()),
            'line_total' => CommerceMoney::decimal($quote->total()),
        ], $quote->configuration, $quote->details, $quote->charges);
    }

    public function build(array $product, int $version, int $quantity, array $amounts,
        array $configuration, array $selections, array $charges): array
    {
        return ['version' => $version, 'currency' => 'CLP', 'product' => $product,
            'quantity' => $quantity, 'amounts' => $amounts, 'configuration' => $configuration,
            'selections' => $selections, 'charges' => $charges];
    }
}
