<?php

namespace App\Services;

use App\Commerce\Compatibility\SushiConfigurationAdapter;
use App\Commerce\Infrastructure\EloquentProductCatalog;
use App\Models\Producto;

/** Compatibility facade for current controllers/views. Remove only after their migration. */
class MenuConfigurationService
{
    public const RELATIONS = EloquentProductCatalog::RELATIONS;

    public function __construct(private ProductPricingService $pricing, private SushiConfigurationAdapter $presentation) {}

    public function supports(Producto $product): bool
    {
        return ! $this->pricing->usesLegacyConfiguration($product);
    }

    public function quote(Producto $product, array $input, int $quantity): array
    {
        return $this->pricing->quote($product, $input, $quantity);
    }

    public function schema(Producto $product): array
    {
        return $this->presentation->schema($product);
    }
}
