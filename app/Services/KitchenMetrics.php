<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Historical snapshots only: never infer units or preparations from today's catalog. */
class KitchenMetrics
{
    public function items(Collection $items): array
    {
        $result = ['pieces' => 0, 'fried' => 0, 'cold' => 0, 'hand_rolls' => 0, 'units' => 0, 'covered' => 0, 'uncovered' => 0];
        foreach ($items as $item) {
            $snapshot = $item->snapshot ?? [];
            $type = data_get($snapshot, 'product.unit_type');
            $included = data_get($snapshot, 'product.included_quantity');
            if (! in_array($type, ['pieza', 'hand_roll', 'unidad'], true) || ! is_numeric($included)) {
                $result['uncovered']++;
                continue;
            }
            $result['covered']++;
            $quantity = (int) ($snapshot['quantity'] ?? $item->unidades);
            $result[match ($type) { 'pieza' => 'pieces', 'hand_roll' => 'hand_rolls', default => 'units' }] += (int) $included * $quantity;
            if ($type !== 'pieza') {
                continue;
            }
            // Global published quantities take priority. Wrapped does not mean cold.
            foreach (data_get($snapshot, 'selections.Preparación', []) as $preparation) {
                $name = Str::lower(Str::ascii($preparation['nombre'] ?? ''));
                $key = str_contains($name, 'frit') ? 'fried' : (str_contains($name, 'frio') || str_contains($name, 'fria') ? 'cold' : null);
                if ($key) {
                    $result[$key] += (int) ($preparation['piezas'] ?? 0) * $quantity;
                }
            }
        }

        return $result;
    }
}
