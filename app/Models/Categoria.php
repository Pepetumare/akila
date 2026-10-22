<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Categoria extends Model
{
    protected $fillable = ['nombre', 'slug', 'activo', 'orden'];

    protected $casts = ['activo' => 'boolean', 'orden' => 'integer'];

    // Generar slug automáticamente
    public static function boot()
    {
        parent::boot();
        static::creating(function ($categoria) {
            $categoria->slug = Str::slug($categoria->nombre);
        });
        static::updating(function ($categoria) {
            $categoria->slug = Str::slug($categoria->nombre);
        });
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }
}
