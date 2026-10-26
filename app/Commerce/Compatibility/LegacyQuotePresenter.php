<?php

namespace App\Commerce\Compatibility;

use App\Commerce\Pricing\ProductQuote;

/** Public/session array contract kept until consumers migrate to ProductQuote. No pricing here. */
class LegacyQuotePresenter
{
    public function toArray(ProductQuote $quote, bool $legacy = false): array
    {
        $line = ['hash' => $quote->hash(), 'producto_id' => $quote->productId, 'nombre' => $quote->productName,
            'unidades' => $quote->quantity, 'precio_base' => $quote->basePrice / 100,
            'recargo' => $legacy ? intdiv($quote->modifierTotal, 100) : $quote->modifierTotal / 100,
            'precio_unit' => $quote->finalUnitPrice() / 100, 'total' => $quote->total() / 100,
            'configuration' => $quote->configuration, 'detalle' => $quote->details];

        return $legacy ? $line : ['valid' => true] + $line + ['charges' => $quote->charges];
    }
}
