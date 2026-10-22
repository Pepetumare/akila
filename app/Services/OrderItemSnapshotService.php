<?php

namespace App\Services;

use App\Models\Producto;

class OrderItemSnapshotService
{
    /** Only call with a fresh server quote, never request input or an old session price. */
    public function fromQuote(Producto $product, array $quote): array
    {
        return [
            'version' => ($quote['configuration']['version'] ?? null) === 2 ? 2 : 1,
            'currency' => 'CLP',
            'product' => ['id' => $product->id, 'name' => $quote['nombre'],
                'description' => $product->descripcion, 'slug' => $product->slug,
                'unit_type' => $product->unidad_tipo->value, 'included_quantity' => $product->unidades],
            'quantity' => (int) $quote['unidades'],
            'amounts' => [
                'base_unit' => $this->money($quote['precio_base']),
                'surcharge_unit' => $this->money($quote['recargo']),
                'final_unit' => $this->money($quote['precio_unit']),
                'line_total' => $this->money($quote['total']),
            ],
            'configuration' => $quote['configuration'],
            'selections' => $quote['detalle'],
            'charges' => $quote['charges'] ?? [],
        ];
    }

    private function money(int|float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
