<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;

class Opcion extends Model
{
    protected $table = 'opciones';

    protected $fillable = ['grupo_opcion_id', 'ingrediente_id', 'nombre', 'slug', 'recargo', 'activo', 'orden'];

    protected $attributes = ['recargo' => '0.00', 'activo' => true, 'orden' => 0];

    protected $casts = ['recargo' => 'decimal:2', 'activo' => 'boolean', 'orden' => 'integer'];

    protected static function booted(): void
    {
        static::saving(function (self $option) {
            Validator::make($option->getAttributes(), [
                'nombre' => 'required|string|max:255', 'slug' => 'required|string|max:255',
                'recargo' => 'required|numeric|min:0|max:99999999.99|decimal:0,2',
                'orden' => 'required|integer|min:0',
            ])->validate();
        });
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(GrupoOpcion::class, 'grupo_opcion_id');
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class);
    }
}
