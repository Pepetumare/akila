<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GrupoOpcion extends Model
{
    protected $table = 'grupos_opciones';

    protected $fillable = ['nombre', 'slug', 'tipo_seleccion', 'min_selections', 'max_selections', 'activo', 'orden'];

    protected $attributes = ['tipo_seleccion' => 'single', 'min_selections' => 0, 'max_selections' => 1, 'activo' => true, 'orden' => 0];

    protected $casts = ['min_selections' => 'integer', 'max_selections' => 'integer', 'activo' => 'boolean', 'orden' => 'integer'];

    protected static function booted(): void
    {
        static::saving(function (self $group) {
            Validator::make($group->getAttributes(), [
                'nombre' => 'required|string|max:255', 'slug' => 'required|string|max:255',
                'tipo_seleccion' => 'required|in:single,multiple',
                'min_selections' => 'required|integer|min:0|max:65535',
                'max_selections' => 'required|integer|gte:min_selections|max:65535',
                'orden' => 'required|integer|min:0',
            ])->validate();
            $group->limites();
            // An edit to the global definition must not invalidate existing overrides.
            foreach (['grupo_opcion_producto', 'bloque_grupo_opcion'] as $table) {
                foreach (DB::table($table)->where('grupo_opcion_id', $group->id)->get() as $row) {
                    $group->limites(new Pivot((array) $row));
                }
            }
        });
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(Opcion::class, 'grupo_opcion_id');
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'grupo_opcion_producto', 'grupo_opcion_id', 'producto_id')
            ->using(AsignacionGrupo::class)->withPivot(AsignacionGrupo::COLUMNS)->withTimestamps();
    }

    public function bloques(): BelongsToMany
    {
        return $this->belongsToMany(BloquePromocion::class, 'bloque_grupo_opcion', 'grupo_opcion_id', 'bloque_promocion_id')
            ->using(AsignacionGrupo::class)->withPivot(AsignacionGrupo::COLUMNS)->withTimestamps();
    }

    public function limites(?Pivot $assignment = null): array
    {
        $min = $assignment?->min_selections ?? $this->min_selections;
        $max = $assignment?->max_selections ?? $this->max_selections;
        if ($assignment?->obligatorio !== null) {
            $min = $assignment->obligatorio ? max(1, $min) : 0;
        }
        if ($min < 0 || $max < $min || ($this->tipo_seleccion === 'single' && $max > 1)) {
            throw ValidationException::withMessages(['selections' => 'Límites de selección incompatibles con el grupo.']);
        }

        return [(int) $min, (int) $max];
    }

    /** Structural validation only; prices and promotion-wide rules are a later phase. */
    public function validarSelecciones(array $ids, ?Pivot $assignment = null): void
    {
        Validator::make(['opciones' => $ids], ['opciones' => 'array', 'opciones.*' => 'required|integer|distinct'])->validate();
        [$min, $max] = $this->limites($assignment);
        if (! $this->activo || count($ids) < $min || count($ids) > $max
            || $this->opciones()->where('activo', true)->whereIn('id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['opciones' => 'Selección fuera de límites o con opciones no disponibles en el grupo.']);
        }
    }
}
