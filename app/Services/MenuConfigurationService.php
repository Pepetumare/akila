<?php

namespace App\Services;

use App\Enums\TipoReglaPromocion;
use App\Enums\UnidadComercial;
use App\Models\Opcion;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MenuConfigurationService
{
    public const RELATIONS = [
        'categoria', 'ingredientes', 'bloques.gruposOpciones.opciones.ingrediente',
        'gruposOpciones.opciones.ingrediente', 'reglas',
    ];

    public function __construct(private PromotionRuleEvaluator $rules) {}

    public function supports(Producto $product): bool
    {
        $product->loadMissing('bloques', 'gruposOpciones');

        return $product->bloques->isNotEmpty() || $product->gruposOpciones->isNotEmpty();
    }

    public function quote(Producto $product, array $input, int $quantity): array
    {
        $product->loadMissing(self::RELATIONS);
        $this->ensure($product->activo && $product->categoria?->activo, 'Este producto no está disponible.');
        $data = Validator::make($input, [
            'version' => 'sometimes|integer|in:2',
            'blocks' => 'sometimes|array|list|max:100',
            'blocks.*.block_id' => 'required|integer|distinct',
            'blocks.*.option_ids' => 'required|array|list|max:100',
            'blocks.*.option_ids.*' => 'required|integer',
            'option_ids' => 'sometimes|array|list|max:100', 'option_ids.*' => 'required|integer|distinct',
            'removed_base_ids' => 'sometimes|array|list|max:100', 'removed_base_ids.*' => 'required|integer|distinct',
        ])->validate();
        $fixed = ! $product->personalizable;
        if (! $fixed) {
            $this->ensure(isset($data['blocks']) || $product->bloques->isEmpty(), 'Completa la elección de todos los rolls antes de agregar al carrito.');
        }
        $submitted = collect($data['blocks'] ?? []);
        $ids = $submitted->pluck('block_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $expected = $product->bloques->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        if (isset($data['blocks']) || ! $fixed) {
            $this->ensure($ids === $expected, 'Los bloques no corresponden a esta promoción. Completa todos sus rolls.');
        }
        $submitted = $submitted->keyBy(fn ($block) => (int) $block['block_id']);
        $normalized = [];
        $readable = [];
        $selections = [];
        $charges = [];
        foreach ($product->bloques->sortBy('id') as $block) {
            $this->ensure($block->unidad_tipo === $product->unidad_tipo, 'La unidad del bloque no corresponde al producto.');
            $selected = $submitted->get($block->id)['option_ids'] ?? null;
            [$optionIds, $groups] = $this->select($product, $block->gruposOpciones, $selected, $fixed, $block->nombre, $block->cantidad_piezas ?? 0, $selections, $charges);
            $identity = ['block_id' => $block->id, 'pieces' => $block->cantidad_piezas, 'option_ids' => $optionIds];
            if ($block->unidad_tipo !== UnidadComercial::Pieza) {
                $identity += ['unit_type' => $block->unidad_tipo->value, 'units' => $block->cantidad_unidades];
            }
            $normalized[] = $identity;
            $readable[] = ['block_id' => $block->id, 'nombre' => $block->nombre, 'orden' => $block->orden,
                'piezas' => $block->cantidad_piezas, 'grupos' => $groups,
                'etiqueta' => $block->unidad_tipo->blockLabel().' '.$block->orden,
                'unidad_tipo' => $block->unidad_tipo->value, 'cantidad' => $block->cantidadComercial(),
                'cantidad_etiqueta' => $block->cantidadComercial().' '.$block->unidad_tipo->label($block->cantidadComercial())];
        }
        $this->ensure($product->bloques->isEmpty() || $product->bloques->sum(fn ($b) => $b->cantidadComercial()) === $product->unidades, 'La composición de unidades necesita revisión.');
        [$productIds, $productGroups] = $this->select($product, $product->gruposOpciones,
            $data['option_ids'] ?? null, $fixed, 'este producto', 0, $selections, $charges);
        $removed = array_map('intval', $data['removed_base_ids'] ?? []);
        sort($removed, SORT_NUMERIC);
        $allowedRemovals = $product->ingredientes->filter(fn ($i) => $i->pivot->metadata['removible'] ?? false)->pluck('id')->all();
        $this->ensure(array_diff($removed, $allowedRemovals) === [], 'La base seleccionada no se puede quitar.');
        $this->rules->validate($product, $selections);
        $config = ['version' => 2, 'blocks' => $normalized, 'option_ids' => $productIds, 'removed_base_ids' => $removed];
        $base = MenuMoney::cents($product->precio);
        $surcharge = array_sum(array_map(fn ($charge) => MenuMoney::cents($charge['amount']), $charges));
        $unit = $base + $surcharge;
        $details = [
            'Base' => $this->base($product)->whereNotIn('id', $removed)->pluck('nombre')->implode(', '),
            'Unidad comercial' => $product->unidad_tipo->value, 'Cantidad incluida' => $product->unidades,
            'Bloques' => collect($readable)->sortBy('orden')->values()->all(), 'Grupos' => $productGroups,
            'Sin base' => $product->ingredientes->whereIn('id', $removed)->pluck('nombre')->values()->all(),
            'Preparación' => $this->preparations($product),
        ];

        return [
            'valid' => true, 'hash' => hash('sha256', json_encode([$product->id, $config], JSON_UNESCAPED_UNICODE)),
            'producto_id' => $product->id, 'nombre' => $product->nombre, 'unidades' => $quantity,
            'precio_base' => $base / 100, 'recargo' => $surcharge / 100,
            'precio_unit' => $unit / 100, 'total' => ($unit * $quantity) / 100,
            'configuration' => $config, 'detalle' => $details, 'charges' => $charges,
        ];
    }

    private function select(Producto $product, Collection $groups, ?array $submitted, bool $fixed, string $label,
        int $pieces, array &$selections, array &$charges): array
    {
        $activeGroups = $groups->where('activo', true);
        $this->ensure($activeGroups->count() === $groups->count(), "La configuración de {$label} no está disponible.");
        $ids = $submitted === null ? [] : array_map('intval', $submitted);
        $scopeStart = count($selections);
        $this->ensure(count($ids) === count(array_unique($ids)), "No repitas una opción dentro de {$label}.");
        $allAllowed = $activeGroups->flatMap(fn ($g) => $g->opciones->where('activo', true)->pluck('id'))->all();
        $this->ensure(array_diff($ids, $allAllowed) === [], "La opción seleccionada no está disponible para {$label}.");
        $result = [];
        foreach ($activeGroups->sortBy('id') as $group) {
            $options = $group->opciones->where('activo', true);
            [$min, $max] = $group->limites($group->pivot);
            $baseline = $options->count() === 1 && $min === 1 ? [(int) $options->first()->id] : [];
            $groupIds = array_values(array_intersect($ids, $options->pluck('id')->all()));
            if ($fixed) {
                $this->ensure($baseline !== [] || $min === 0, "La composición fija de {$label} necesita revisión.");
                if ($submitted !== null) {
                    $this->ensure($groupIds === $baseline, "No puedes modificar la composición fija de {$label}.");
                }
                $groupIds = $baseline;
            }
            $this->ensure(count($groupIds) >= $min && count($groupIds) <= $max,
                "Completa la elección de {$label}: {$group->nombre} permite entre {$min} y {$max} opciones.");
            $picked = [];
            foreach ($options->whereIn('id', $groupIds)->sortBy('id') as $option) {
                $optionCharges = $this->rules->surcharge($option, $group, $product->reglas, $baseline);
                array_push($charges, ...$optionCharges);
                $selections[] = ['option' => $option, 'filling' => $this->rules->isFilling($group), 'pieces' => $pieces];
                $picked[] = ['option_id' => $option->id, 'nombre' => $option->nombre,
                    'ingrediente_id' => $option->ingrediente_id, 'tipo' => $option->ingrediente?->tipo,
                    'recargo' => MenuMoney::decimal(array_sum(array_map(fn ($c) => MenuMoney::cents($c['amount']), $optionCharges)))];
            }
            $result[] = ['group_id' => $group->id, 'nombre' => $group->nombre, 'opciones' => $picked];
        }
        $ids = collect($result)->flatMap(fn ($g) => array_column($g['opciones'], 'option_id'))->sort()->values()->all();
        if (! $fixed) {
            $ingredients = collect(array_slice($selections, $scopeStart))->where('filling', true)
                ->map(fn ($s) => $s['option']->ingrediente_id)->filter();
            $this->ensure($ingredients->count() === $ingredients->unique()->count(), 'El segundo ingrediente debe ser distinto del relleno de la misma unidad.');
        }

        return [$ids, $result];
    }

    public function schema(Producto $product): array
    {
        $product->loadMissing(self::RELATIONS);
        $groups = function ($groups) use ($product) {
            return $groups->where('activo', true)->map(function ($group) use ($product) {
                [$min, $max] = $group->limites($group->pivot);
                $options = $group->opciones->where('activo', true);
                $baseline = $options->count() === 1 && $min === 1 ? [(int) $options->first()->id] : [];

                return ['id' => $group->id, 'nombre' => $group->nombre, 'min' => $min, 'max' => $max,
                    'selection' => $group->tipo_seleccion, 'categorize' => $this->rules->isFilling($group),
                    'options' => $options->map(function ($option) use ($group, $product, $baseline) {
                        $charges = $this->rules->surcharge($option, $group, $product->reglas, $baseline);

                        return ['id' => $option->id, 'nombre' => $option->nombre, 'tipo' => $option->ingrediente?->tipo,
                            'surcharge' => MenuMoney::decimal(array_sum(array_map(fn ($c) => MenuMoney::cents($c['amount']), $charges)))];
                    })->values()->all()];
            })->values()->all();
        };

        return ['version' => 2, 'product_id' => $product->id, 'customizable' => $product->personalizable,
            'pieces' => $product->unidad_tipo === UnidadComercial::Pieza ? $product->unidades : null,
            'unit_type' => $product->unidad_tipo->value, 'quantity' => $product->unidades,
            'quantity_label' => $product->unidades.' '.$product->unidad_tipo->label($product->unidades),
            'base' => $this->base($product)->map(fn ($i) => ['id' => $i->id, 'nombre' => $i->nombre,
                'removable' => (bool) ($i->pivot->metadata['removible'] ?? false)])->values()->all(),
            'groups' => $groups($product->gruposOpciones),
            'blocks' => $product->bloques->map(fn ($b) => ['id' => $b->id, 'nombre' => $b->nombre,
                'label' => $b->unidad_tipo->blockLabel().' '.$b->orden,
                'quantity_label' => $b->cantidadComercial().' '.$b->unidad_tipo->label($b->cantidadComercial()),
                'pieces' => $b->cantidad_piezas, 'groups' => $groups($b->gruposOpciones)])->values()->all(),
            'preparations' => $this->preparations($product)];
    }

    private function base(Producto $product): Collection
    {
        return $product->ingredientes->filter(fn ($i) => $i->tipo === 'base' || ($i->pivot->metadata['composicion_fija'] ?? false));
    }

    private function preparations(Producto $product): array
    {
        // These are published counts, not a guessed mapping of cold/fried pieces to blocks.
        $rules = $product->reglas->where('activo', true)->where('tipo', TipoReglaPromocion::CantidadIncluida)
            ->filter(fn ($r) => ($r->metadata['uso'] ?? null) === 'preparacion_global');
        $options = $product->bloques->flatMap(fn ($b) => $b->gruposOpciones->flatMap(fn ($g) => $g->opciones))
            ->merge($product->gruposOpciones->flatMap(fn ($g) => $g->opciones))->keyBy('id');
        // Load only referenced public preparation names, never the entire ingredient catalog.
        $missing = $rules->pluck('opcion_id')->diff($options->keys());
        if ($missing->isNotEmpty()) {
            $options = $options->union(Opcion::whereIn('id', $missing)->get()->keyBy('id'));
        }

        return $rules->map(fn ($r) => ['nombre' => $options->get($r->opcion_id)?->nombre, 'piezas' => (int) $r->valor])->values()->all();
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['blocks' => $message]);
        }
    }
}
