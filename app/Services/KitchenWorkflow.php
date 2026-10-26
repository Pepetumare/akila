<?php

namespace App\Services;

use App\Models\AdminChange;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KitchenWorkflow
{
    public const LABELS = ['pendiente' => 'Nuevo', 'en preparación' => 'Preparando', 'listo' => 'Listo',
        'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];

    public const ACTIVE = ['pendiente', 'en preparación', 'listo'];

    public function transition(Order $order, string $target, string $expected): Order
    {
        return DB::transaction(function () use ($order, $target, $expected) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $allowed = match ($locked->status) {
                'pendiente' => ['en preparación', 'cancelado'],
                'en preparación' => ['listo', 'cancelado'], 'listo' => ['entregado', 'cancelado'], default => [],
            };
            if ($locked->status !== $expected || ! in_array($target, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'El pedido cambió o la transición no es válida. Actualiza la vista.']);
            }
            $before = $locked->status;
            $locked->status = $target;
            $field = match ($target) {
                'en preparación' => 'preparing_at', 'listo' => 'ready_at', 'entregado' => 'delivered_at', 'cancelado' => 'cancelled_at',
            };
            $locked->$field = now();
            $locked->save();
            AdminChange::record($locked, 'estado', ['status' => ['before' => $before, 'after' => $target]]);

            return $locked;
        });
    }
}
