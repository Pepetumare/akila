<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promocion extends Model
{
    protected $table = 'promociones';

    protected $fillable = [
        'nombre', 'descripcion', 'precio', 'cantidad_rolls', 'personalizable', 'categoria'
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'promocion_handroll')->withPivot('cantidad');
    }
}
