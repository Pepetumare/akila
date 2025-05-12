<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'removed_bases',
        'extras',
        'price',
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
