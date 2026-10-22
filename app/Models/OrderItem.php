<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'nombre',
        'unidades',
        'precio_unit',   // ← usa el nuevo nombre
        'total',
        'detalle',
        'snapshot',
    ];

    protected $casts = [
        'removed_bases' => 'array',
        'extras' => 'array',
        'detalle' => 'array',
        'precio_unit' => 'decimal:2',
        'total' => 'decimal:2',
        'snapshot' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $item) {
            if ($item->getRawOriginal('snapshot') !== null && $item->isDirty('snapshot')) {
                throw ValidationException::withMessages(['snapshot' => 'El snapshot histórico no se puede reemplazar.']);
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'product_id');
    }
}
