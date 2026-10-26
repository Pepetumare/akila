<?php

namespace App\Services;

use App\Commerce\Contracts\ProductCatalog;
use App\Commerce\Orders\OrderItemSnapshotService as SnapshotService;
use App\Commerce\Pricing\ProductQuote;
use App\Models\Producto;

/** Compatibility boundary. Only fresh server quotes, never browser snapshots/amounts. */
class OrderItemSnapshotService
{
    public function __construct(private SnapshotService $snapshots, private ProductCatalog $catalog) {}

    public function fromQuote(Producto $product, array|ProductQuote $quote): array
    {
        if ($quote instanceof ProductQuote) {
            return $this->snapshots->fromQuote($this->catalog->definition($product), $quote);
        }

        return $this->snapshots->build([
            'id' => $product->id, 'name' => $quote['nombre'], 'description' => $product->descripcion,
            'slug' => $product->slug, 'unit_type' => $product->unidad_tipo->value, 'included_quantity' => $product->unidades,
        ], ($quote['configuration']['version'] ?? null) === 2 ? 2 : 1, (int) $quote['unidades'], [
            'base_unit' => $this->money($quote['precio_base']), 'surcharge_unit' => $this->money($quote['recargo']),
            'final_unit' => $this->money($quote['precio_unit']), 'line_total' => $this->money($quote['total']),
        ], $quote['configuration'], $quote['detalle'], $quote['charges'] ?? []);
    }

    private function money(int|float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
