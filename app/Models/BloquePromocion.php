<?php

namespace App\Models;

use App\Enums\UnidadComercial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Validator;

class BloquePromocion extends Model
{
    protected $table = 'bloques_promocion';

    protected $fillable = ['producto_id', 'nombre', 'cantidad_piezas', 'cantidad_unidades', 'unidad_tipo', 'orden', 'metadata'];

    protected $attributes = ['cantidad_piezas' => 10, 'unidad_tipo' => 'pieza'];

    protected $casts = ['cantidad_piezas' => 'integer', 'cantidad_unidades' => 'integer', 'unidad_tipo' => UnidadComercial::class, 'orden' => 'integer', 'metadata' => 'array'];

    protected static function booted(): void
    {
        static::saving(function (self $block) {
            Validator::make($block->getAttributes(), [
                'nombre' => 'required|string|max:255', 'unidad_tipo' => 'required|in:pieza,hand_roll,unidad',
                'cantidad_piezas' => 'required_if:unidad_tipo,pieza|prohibited_unless:unidad_tipo,pieza|nullable|integer|min:1|max:65535',
                'cantidad_unidades' => 'required_unless:unidad_tipo,pieza|prohibited_if:unidad_tipo,pieza|nullable|integer|min:1|max:65535',
                'orden' => 'required|integer|min:0',
            ])->validate();
        });
    }

    public function cantidadComercial(): int
    {
        return $this->unidad_tipo === UnidadComercial::Pieza ? $this->cantidad_piezas : $this->cantidad_unidades;
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function gruposOpciones(): BelongsToMany
    {
        return $this->belongsToMany(GrupoOpcion::class, 'bloque_grupo_opcion', 'bloque_promocion_id', 'grupo_opcion_id')
            ->using(AsignacionGrupo::class)->withPivot(AsignacionGrupo::COLUMNS)->withTimestamps()->orderByPivot('orden');
    }
}
