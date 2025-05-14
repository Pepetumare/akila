<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',    // o 'product_id' según tu esquema
        'nombre',
        'unidades',
        'precio_base',
        'subtotal',       // aquí en lugar de 'price'
        'removed_bases',
        'extras',
    ];

    protected $casts = [
        'removed_bases' => 'array',
        'extras'        => 'array',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'product_id');
    }
}
