<?php

namespace App\Commerce\Pricing;

use App\Commerce\Configuration\ProductSelection;

/** Immutable server quote. All monetary fields are integer hundredths, including CLP. */
final readonly class ProductQuote
{
    public array $configuration;

    public function __construct(
        public int $productId,
        public string $productName,
        public int $quantity,
        public int $basePrice,
        public int $modifierTotal,
        public ProductSelection $selection,
        public array $details = [],
        public array $charges = [],
        ?array $configuration = null,
    ) {
        $this->configuration = $configuration ?? $selection->toArray();
    }

    public function finalUnitPrice(): int
    {
        return $this->basePrice + $this->modifierTotal;
    }

    public function total(): int
    {
        return $this->finalUnitPrice() * $this->quantity;
    }

    public function hash(): string
    {
        return hash('sha256', json_encode([$this->productId, $this->configuration], JSON_UNESCAPED_UNICODE));
    }

    /** Representation changes never recalculate or override authoritative amounts. */
    public function withRepresentation(array $configuration, array $details): self
    {
        return new self($this->productId, $this->productName, $this->quantity, $this->basePrice,
            $this->modifierTotal, $this->selection, $details, $this->charges, $configuration);
    }

    public function toArray(): array
    {
        return ['product_id' => $this->productId, 'name' => $this->productName, 'quantity' => $this->quantity,
            'base_price' => CommerceMoney::decimal($this->basePrice),
            'modifier_total' => CommerceMoney::decimal($this->modifierTotal),
            'final_unit_price' => CommerceMoney::decimal($this->finalUnitPrice()),
            'total' => CommerceMoney::decimal($this->total()), 'hash' => $this->hash(),
            'configuration' => $this->configuration, 'details' => $this->details, 'charges' => $this->charges];
    }
}
