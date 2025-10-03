<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'cliente_nombre',
        'cliente_telefono',
        'cliente_direccion',
        'cliente_comentarios',
        'metodo_entrega',
        'zona_delivery',
        'kms_fuera',
        'subtotal',
        'delivery_cost',
        'total',
        'status',
        'mp_payment_id',
    ];

    protected $casts = [
        'subtotal'      => 'decimal:2',
        'delivery_cost' => 'decimal:2',
        'total'         => 'decimal:2',
        'kms_fuera'     => 'integer',
    ];

    protected $appends = ['envio'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function envio(): Attribute
    {
        return Attribute::get(fn () => $this->delivery_cost ?? 0);
    }
}
