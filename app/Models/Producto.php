<?php
// app/Models/Producto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    // protected $fillable = [
    //     'nombre',
    //     'descripcion',
    //     'precio',
    //     'imagen',
    //     'categoria_id',
    //     'personalizable',
    //     'unidades',
    //     'rolls_total',
    //     'rolls_envueltos',
    //     'rolls_fritos',
    //     // 'slug' si quieres asignarlo masivamente
    // ];

    protected $guarded = [];

    protected $casts = [
        'personalizable' => 'boolean',
    ];

    // Generar slug automáticamente
    public static function boot()
    {
        parent::boot();

        static::creating(function ($p) {
            $slug = Str::slug($p->nombre);
            $original = $slug;
            $i = 1;

            while (Producto::where('slug', $slug)->exists()) {
                $slug = $original . '-' . $i++;
            }

            $p->slug = $slug;
        });

        static::updating(function ($p) {
            // Si el nombre no ha cambiado, no regeneres el slug
            if (!$p->isDirty('nombre')) {
                return;
            }

            $slug = Str::slug($p->nombre);
            $original = $slug;
            $i = 1;

            while (Producto::where('slug', $slug)->where('id', '!=', $p->id)->exists()) {
                $slug = $original . '-' . $i++;
            }

            $p->slug = $slug;
        });
    }

    // Relación con Categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function envolturas() {}

    // Relación muchos a muchos con Ingredientes (con cantidad_permitida)
    public function ingredientes()
    {
        return $this->belongsToMany(Ingrediente::class)
            ->withPivot('cantidad_permitida');
    }

    // Relación “swappables” (opcionalmente podrías incluir pivot/timestamps)
    public function swappables()
    {
        return $this->belongsToMany(
            Ingrediente::class,
            'producto_swappable_ingredient'
        )
            // ->withPivot('cantidad_permitida') 
            // ->withTimestamps()
        ;
    }
}
