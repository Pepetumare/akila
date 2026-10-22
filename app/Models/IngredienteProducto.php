<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Validator;

class IngredienteProducto extends Pivot
{
    public $incrementing = true;

    protected $casts = [
        'cantidad' => 'decimal:3', 'obligatorio' => 'boolean', 'reemplazable' => 'boolean',
        'orden' => 'integer', 'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $pivot) {
            Validator::make($pivot->getAttributes(), [
                'cantidad' => 'nullable|numeric|min:0', 'unidad' => 'nullable|string|max:20',
                'orden' => 'sometimes|integer|min:0',
            ])->validate();
        });
    }
}
