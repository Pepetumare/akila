<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Validator;

class AsignacionGrupo extends Pivot
{
    public const COLUMNS = ['min_selections', 'max_selections', 'obligatorio', 'orden'];

    public $incrementing = true;

    protected $casts = ['min_selections' => 'integer', 'max_selections' => 'integer', 'obligatorio' => 'boolean', 'orden' => 'integer'];

    protected static function booted(): void
    {
        static::saving(function (self $assignment) {
            Validator::make($assignment->getAttributes(), [
                'min_selections' => 'nullable|integer|min:0|max:65535',
                'max_selections' => 'nullable|integer|min:0|max:65535',
                'obligatorio' => 'nullable|boolean', 'orden' => 'sometimes|integer|min:0',
            ])->validate();
            GrupoOpcion::findOrFail($assignment->grupo_opcion_id)->limites($assignment);
        });
    }
}
