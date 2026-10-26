<?php

// app/Models/Ingrediente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingrediente extends Model
{
    protected $table = 'ingredientes';

    protected $fillable = [
        'nombre',
        'tipo',    // 'base' o 'extra'
        'costo',   // nullable para tipo 'base'
        'is_available',
    ];

    protected $attributes = ['is_available' => true];

    protected $casts = ['is_available' => 'boolean', 'costo' => 'decimal:2'];

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class)
            ->using(IngredienteProducto::class)
            ->withPivot('cantidad_permitida', 'cantidad', 'unidad', 'obligatorio', 'reemplazable', 'orden', 'metadata')
            ->withTimestamps();
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(Opcion::class);
    }
}
