<?php
// app/Models/Producto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'imagen',
        'categoria_id',
        'personalizable',
        'unidades'
    ];

    // Para generar un slug si quisieras
    public static function boot()
    {
        parent::boot();
        static::creating(function($p){
            $p->slug = Str::slug($p->nombre);
        });
        static::updating(function($p){
            $p->slug = Str::slug($p->nombre);
        });
    }

    // Relación con Categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function swappables()
    {
        return $this->belongsToMany(
            Ingrediente::class,
            'producto_swappable_ingredient'
        );
    }
}
