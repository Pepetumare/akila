<?php

namespace App\Commerce\Compatibility;

use App\Commerce\Configuration\ProductConfigurationInput;
use App\Commerce\Configuration\ProductRuleEvaluator;
use App\Commerce\Exceptions\ConfigurationException;
use App\Commerce\Infrastructure\EloquentProductCatalog;
use App\Commerce\Infrastructure\CatalogProjection;
use App\Commerce\Pricing\CommerceMoney as MenuMoney;
use App\Commerce\Pricing\ProductQuote;
use App\Enums\TipoReglaPromocion;
use App\Enums\UnidadComercial;
use App\Models\GrupoOpcion;
use App\Models\Ingrediente;
use App\Models\Opcion;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Existing catalog semantics and Spanish UI projection only.
 * Compatibility layer: no price arithmetic, selection cardinality or rule evaluation.
 */
class SushiConfigurationAdapter
{
    public const RELATIONS = EloquentProductCatalog::RELATIONS;

    public function __construct(private ProductRuleEvaluator $ruleEvaluator, private CatalogProjection $projection) {}

    public function input(array $input): ProductConfigurationInput
    {
        // Never discard a legacy choice when the product uses the structured contract.
        foreach (['envoltura_id', 'base_id', 'Proteínas', 'vegetales', 'cream_cheese', 'scallions'] as $key) {
            if (! empty($input[$key])) {
                throw new ConfigurationException('invalid_option');
            }
        }
        if (array_key_exists('removed_base_ids', $input)) {
            if (isset($input['removed_component_ids']) && $input['removed_component_ids'] !== $input['removed_base_ids']) {
                throw ValidationException::withMessages(['removed_base_ids' => 'Componentes contradictorios.']);
            }
            $input['removed_component_ids'] = $input['removed_base_ids'];
        }

        return ProductConfigurationInput::fromArray($input);
    }

    public function isFilling(GrupoOpcion $group): bool
    {
        return $group->catalog_policy['count_component'] ?? true;
    }

    public function group(GrupoOpcion $group): array
    {
        return $this->projection->group($group);
    }

    public function option(Opcion $option): array
    {
        return $this->projection->option($option);
    }

    public function rules(Producto $product): array
    {
        return $this->projection->rules($product);
    }

    public function mapRules(Collection $rules): Collection
    {
        return $this->projection->mapRules($rules);
    }

    public function surcharge(Opcion $option, GrupoOpcion $group, Collection $rules, array $baseline): array
    {
        return $this->ruleEvaluator->surcharge($this->option($option),
            ['change_kind' => $group->catalog_policy['change_kind'] ?? 'primary'],
            $this->mapRules($rules)->values()->all(), $baseline);
    }

    public function present(Producto $product, ProductQuote $quote): ProductQuote
    {
        $groupsById = $product->gruposOpciones
            ->merge($product->bloques->flatMap(fn ($b) => $b->gruposOpciones))->keyBy('id');
        $groups = function (array $groups) use ($groupsById): array {
            return array_map(function ($g) use ($groupsById) {
                $result = ['group_id' => $g['group_id'], 'nombre' => $g['name'],
                    'opciones' => array_map(fn ($o) => [
                        'option_id' => $o['option_id'], 'nombre' => $o['name'],
                        'ingrediente_id' => $o['component_id'], 'tipo' => $o['component_type'], 'recargo' => $o['surcharge'],
                    ], $g['options'])];
                if ($groupsById[$g['group_id']]->catalog_policy['mixed_label'] ?? false) {
                    $result['mixto'] = count($g['options']) > 1;
                }

                return $result;
            }, $groups);
        };
        $blocksById = $product->bloques->keyBy('id');
        $normalized = array_map(function ($b) use ($blocksById) {
            $block = $blocksById[$b['block_id']];
            $identity = ['block_id' => $block->id, 'pieces' => $block->cantidad_piezas, 'option_ids' => $b['option_ids']];
            if ($block->unidad_tipo !== UnidadComercial::Pieza) {
                $identity += ['unit_type' => $block->unidad_tipo->value, 'units' => $block->cantidad_unidades];
            }

            return $identity;
        }, $quote->selection->blocks);
        $readable = array_map(function ($b) use ($blocksById, $groups) {
            $block = $blocksById[$b['block_id']];

            return ['block_id' => $block->id, 'nombre' => $block->nombre, 'orden' => $block->orden,
                'piezas' => $block->cantidad_piezas, 'grupos' => $groups($b['groups']),
                'etiqueta' => $block->metadata['label'] ?? $block->nombre,
                'unidad_tipo' => $block->unidad_tipo->value, 'cantidad' => $block->cantidadComercial(),
                'cantidad_etiqueta' => $block->cantidadComercial().' '.$block->unidad_tipo->label($block->cantidadComercial())];
        }, $quote->details['blocks']);
        $removed = $quote->selection->removedComponentIds;
        $configuration = ['version' => 2, 'blocks' => $normalized,
            'option_ids' => $quote->selection->optionIds, 'removed_base_ids' => $removed];
        $details = [
            'Base' => $this->base($product)->whereNotIn('id', $removed)->pluck('nombre')->implode(', '),
            'Unidad comercial' => $product->unidad_tipo->value, 'Cantidad incluida' => $product->unidades,
            'Bloques' => $readable, 'Grupos' => $groups($quote->details['groups']),
            'Sin base' => $product->ingredientes->whereIn('id', $removed)->pluck('nombre')->values()->all(),
            'Preparación' => $this->preparations($product), 'Salsas incluidas' => $this->includedSauces($product),
        ];

        return $quote->withRepresentation($configuration, $details);
    }

    public function translate(ConfigurationException $error): never
    {
        $label = $error->context['label'] ?? 'este producto';
        $name = $error->context['name'] ?? '';
        $limit = $error->context['limit'] ?? 0;
        $message = match ($error->reason) {
            'invalid_blocks' => 'Los bloques no corresponden a esta promoción. Completa todos sus rolls.',
            'max_repetitions' => "{$name} puede repetirse como máximo {$limit} ".($limit === 1 ? 'vez' : 'veces').' en esta promoción.',
            'excluded_option' => $name.' no está disponible en esta promoción.',
            'duplicate_component' => 'El segundo ingrediente debe ser distinto del relleno de la misma unidad.',
            'invalid_removal' => 'La base seleccionada no se puede quitar.',
            default => null,
        };
        if ($message !== null) {
            $error->validator->errors()->forget('blocks');
            $error->validator->errors()->add('blocks', $message);
        }

        throw $error;
    }

    public function schema(Producto $product): array
    {
        $product->loadMissing(self::RELATIONS);
        $groups = function ($groups) use ($product) {
            return $groups->where('activo', true)->map(function ($group) use ($product) {
                [$min, $max] = $group->limites($group->pivot);
                $options = $group->opciones->where('activo', true)->where('disponible', true)->filter(fn ($option) => (! $product->personalizable && $min > 0) || $option->ingrediente?->is_available !== false);
                $baseline = $options->count() === 1 && $min === 1 ? [(int) $options->first()->id] : [];

                return ['id' => $group->id, 'nombre' => $group->nombre, 'min' => $min, 'max' => $max,
                    'selection' => $group->tipo_seleccion, 'categorize' => $this->isFilling($group),
                    'options' => $options->map(function ($option) use ($group, $product, $baseline) {
                        $charges = $this->surcharge($option, $group, $product->reglas, $baseline);

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
                'label' => $b->metadata['label'] ?? $b->nombre,
                'quantity_label' => $b->cantidadComercial().' '.$b->unidad_tipo->label($b->cantidadComercial()),
                'pieces' => $b->cantidad_piezas, 'groups' => $groups($b->gruposOpciones)])->values()->all(),
            'preparations' => $this->preparations($product)];
    }

    private function base(Producto $product): Collection
    {
        return $product->ingredientes->filter(fn ($i) => $i->tipo === 'base' || ($i->pivot->metadata['composicion_fija'] ?? false));
    }

    private function includedSauces(Producto $product): array
    {
        $rules = $product->reglas->where('activo', true)->where('tipo', TipoReglaPromocion::CantidadIncluida)
            ->filter(fn ($r) => ($r->metadata['uso'] ?? null) === 'salsa_incluida');
        $ingredients = Ingrediente::whereIn('id', $rules->pluck('ingrediente_id'))->get()->keyBy('id');

        return $rules->map(fn ($r) => ['nombre' => $ingredients->get($r->ingrediente_id)?->nombre,
            'cantidad' => (int) $r->valor, 'unidad' => 'pote', 'precio' => '0.00'])->values()->all();
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
}
