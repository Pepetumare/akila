<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Promocion extends Model
{
    protected $table = 'promociones';

    protected $fillable = [
        'nombre', 'descripcion', 'precio', 'cantidad_rolls', 'personalizable', 'categoria', 'producto_id',
    ];

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'promocion_handroll')->withPivot('cantidad');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
