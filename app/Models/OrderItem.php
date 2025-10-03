<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'removed_bases' => 'array',
        'extras'        => 'array',
        'detalle'       => 'array',
        'precio_unit'   => 'decimal:2',
        'total'         => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'product_id');
    }
}
