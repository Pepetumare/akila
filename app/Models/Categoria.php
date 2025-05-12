<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Categoria extends Model
{
    protected $fillable = ['nombre','slug'];

    // Generar slug automáticamente
    public static function boot()
    {
        parent::boot();
        static::creating(function($categoria){
            $categoria->slug = Str::slug($categoria->nombre);
        });
        static::updating(function($categoria){
            $categoria->slug = Str::slug($categoria->nombre);
        });
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }
}
