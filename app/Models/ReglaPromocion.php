<?php

namespace App\Models;

use App\Enums\TipoReglaPromocion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReglaPromocion extends Model
{
    protected $table = 'reglas_promocion';

    protected $fillable = ['producto_id', 'codigo', 'tipo', 'ingrediente_id', 'opcion_id', 'valor', 'activo', 'metadata'];

    protected $casts = ['tipo' => TipoReglaPromocion::class, 'valor' => 'decimal:2', 'activo' => 'boolean', 'metadata' => 'array'];

    protected static function booted(): void
    {
        static::saving(function (self $rule) {
            Validator::make($rule->getAttributes(), [
                'codigo' => 'required|string|max:255',
                'tipo' => 'required|in:'.implode(',', array_column(TipoReglaPromocion::cases(), 'value')),
                'valor' => 'required|numeric|min:0|max:9999999999.99|decimal:0,2',
            ])->validate();
            if (($rule->ingrediente_id !== null) === ($rule->opcion_id !== null)) {
                throw ValidationException::withMessages(['subject' => 'La regla debe referenciar un ingrediente o una opción, exclusivamente.']);
            }
            if ($rule->tipo !== TipoReglaPromocion::Recargo && (float) $rule->valor != (int) $rule->valor) {
                throw ValidationException::withMessages(['valor' => 'La cantidad de una regla debe ser entera.']);
            }
            if ($rule->tipo === TipoReglaPromocion::Excluir && (float) $rule->valor !== 0.0) {
                throw ValidationException::withMessages(['valor' => 'Una exclusión debe tener valor cero.']);
            }
            if ($rule->tipo === TipoReglaPromocion::Incompatible) {
                Validator::make($rule->metadata ?? [], [
                    'option_ids' => 'required|array|list|min:1|max:100',
                    'option_ids.*' => 'required|integer|distinct|min:1',
                ])->validate();
            }
        });
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class);
    }

    public function opcion(): BelongsTo
    {
        return $this->belongsTo(Opcion::class);
    }
}
