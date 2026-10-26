<?php

namespace App\Commerce\Compatibility;

use Illuminate\Support\Facades\DB;

/** Import/backfill ONLY. Translates the old template conventions to stored policies once. */
class SushiCatalogPolicyUpgrade
{
    public function run(?array $productIds = null): void
    {
        DB::transaction(function () use ($productIds) {
            $query = DB::table('productos')->whereNull('catalog_policy');
            if ($productIds !== null) {
                $query->whereIn('id', $productIds);
            }
            foreach ($query->get() as $product) {
                $components = DB::table('ingrediente_producto')->where('producto_id', $product->id)->get();
                $structured = DB::table('bloques_promocion')->where('producto_id', $product->id)->exists()
                    || DB::table('grupo_opcion_producto')->where('producto_id', $product->id)->exists();
                $food = $components->isNotEmpty() || $product->unidad_tipo !== 'unidad';
                $pending = false;
                foreach ($components as $component) {
                    $meta = json_decode($component->metadata ?? '{}', true) ?? [];
                    $pending = $pending || data_get($meta, 'opcion_vegetariana.estado') === 'pendiente_definicion_operativa';
                    $type = DB::table('ingredientes')->where('id', $component->ingrediente_id)->value('tipo');
                    $meta['included_component'] = $type === 'base' || ($meta['composicion_fija'] ?? false);
                    DB::table('ingrediente_producto')->where('producto_id', $product->id)->where('ingrediente_id', $component->ingrediente_id)
                        ->update(['metadata' => json_encode($meta, JSON_UNESCAPED_UNICODE)]);
                }
                DB::table('productos')->where('id', $product->id)->update(['catalog_policy' => json_encode([
                    'matching_units' => $food, 'sum_content' => $food, 'unique_components_per_scope' => $food,
                    'legacy_ingredient_configuration' => ! $structured && $components->isNotEmpty(),
                    'publication_blockers' => $pending ? ['configuration.pending_definition'] : [],
                ])]);
                foreach (DB::table('bloques_promocion')->where('producto_id', $product->id)->get() as $block) {
                    $meta = json_decode($block->metadata ?? '{}', true) ?? [];
                    $meta += ['rule_units' => $block->cantidad_piezas ?? 0,
                        'label' => match ($block->unidad_tipo) { 'pieza' => 'Roll', 'hand_roll' => 'Hand Roll', default => 'Unidad' }.' '.$block->orden];
                    DB::table('bloques_promocion')->where('id', $block->id)->update(['metadata' => json_encode($meta, JSON_UNESCAPED_UNICODE)]);
                }
            }
            // Only imported Sushi groups are converted, never a newly authored generic group by its slug.
            $groupIds = DB::table('grupo_opcion_producto')->when($productIds !== null, fn ($q) => $q->whereIn('producto_id', $productIds))->pluck('grupo_opcion_id')
                ->merge(DB::table('bloque_grupo_opcion')->join('bloques_promocion', 'bloques_promocion.id', '=', 'bloque_grupo_opcion.bloque_promocion_id')
                    ->when($productIds !== null, fn ($q) => $q->whereIn('bloques_promocion.producto_id', $productIds))->pluck('grupo_opcion_id'));
            foreach (DB::table('grupos_opciones')->whereNull('catalog_policy')->whereIn('id', $groupIds)->get() as $group) {
                $filling = ! in_array($group->slug, ['envoltura', 'preparacion', 'salsas-adicionales'], true)
                    && ! str_starts_with($group->slug, 'sabor-') && ! str_starts_with($group->slug, 'envoltura-');
                DB::table('grupos_opciones')->where('id', $group->id)->update(['catalog_policy' => json_encode([
                    'count_component' => $filling, 'change_kind' => $filling ? 'primary' : 'secondary',
                    'mixed_label' => str_starts_with($group->slug, 'sabor-'),
                ])]);
            }
            foreach (DB::table('reglas_promocion')->when($productIds !== null, fn ($q) => $q->whereIn('producto_id', $productIds))->get() as $rule) {
                $meta = json_decode($rule->metadata ?? '{}', true) ?? [];
                if (isset($meta['condicion'])) {
                    $meta += ['change_kind' => match ($meta['condicion']) {
                        'modificacion_relleno' => 'primary', 'modificacion_envoltura' => 'secondary', default => 'unsupported_condition',
                    }];
                }
                if ($rule->tipo === 'max_repeticiones') { $meta += ['configurable_only' => true]; }
                if (in_array($meta['uso'] ?? null, ['preparacion_global', 'salsa_incluida'], true)) { $meta += ['display_only' => true]; }
                if ($meta !== (json_decode($rule->metadata ?? '{}', true) ?? [])) {
                    DB::table('reglas_promocion')->where('id', $rule->id)->update(['metadata' => json_encode($meta, JSON_UNESCAPED_UNICODE)]);
                }
            }
        });
    }
}
