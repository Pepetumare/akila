<?php
// app/Models/Ingrediente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingrediente extends Model
{
    protected $table = 'ingredientes';

    protected $fillable = [
        'nombre',
        'tipo',    // 'base' o 'extra'
        'costo',   // nullable para tipo 'base'
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class)
                    ->withPivot('cantidad_permitida')
                    ->withTimestamps();
    }    
}
