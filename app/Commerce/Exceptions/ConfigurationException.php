<?php

namespace App\Commerce\Exceptions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Stable machine-readable reason, plus a neutral Spanish default for existing Laravel handlers. */
class ConfigurationException extends ValidationException
{
    public function __construct(
        public readonly string $reason,
        public readonly array $context = [],
        string $field = 'blocks',
    ) {
        $name = $context['name'] ?? 'La opción';
        $label = $context['label'] ?? 'este producto';
        $limit = $context['limit'] ?? 0;
        $message = match ($reason) {
            'product_unavailable' => 'Este producto no está disponible.',
            'invalid_quantity' => 'Cantidad permitida: 1 a 50.',
            'invalid_blocks' => 'Completa la selección de todos los componentes del producto.',
            'option_unavailable' => $name.' agotado. Elige otra opción.',
            'duplicate_selection' => "No repitas una opción dentro de {$label}.",
            'invalid_option' => "La opción seleccionada no está disponible para {$label}.",
            'group_unavailable' => "La configuración de {$label} no está disponible.",
            'fixed_configuration' => "No puedes modificar la composición fija de {$label}.",
            'invalid_fixed_configuration' => "La composición fija de {$label} necesita revisión.",
            'required_selection', 'too_many_selections' => "Completa la elección de {$label}: ".($context['group'] ?? '').' permite entre '.($context['min'] ?? 0).' y '.($context['max'] ?? 0).' opciones.',
            'max_repetitions' => "{$name} puede repetirse como máximo {$limit} ".($limit === 1 ? 'vez' : 'veces').' en esta configuración.',
            'excluded_option' => $name.' no está disponible en esta configuración.',
            'incompatible_options' => 'Las opciones seleccionadas son incompatibles.',
            'invalid_removal' => 'El componente seleccionado no se puede quitar.',
            'duplicate_component' => 'Los componentes seleccionados deben ser distintos dentro de la misma configuración.',
            'content_mismatch' => 'La composición de unidades necesita revisión.',
            default => 'La configuración del producto necesita revisión.',
        };
        $validator = Validator::make([], []);
        $validator->errors()->add($field, $message);
        parent::__construct($validator);
    }

    public static function check(bool $condition, string $reason, array $context = [], string $field = 'blocks'): void
    {
        if (! $condition) {
            throw new self($reason, $context, $field);
        }
    }
}
