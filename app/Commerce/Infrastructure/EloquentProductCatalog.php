<?php

namespace App\Commerce\Infrastructure;

use App\Commerce\Catalog\ProductDefinition;
use App\Commerce\Contracts\ProductCatalog;
use App\Models\Producto;

/** Projects the existing schema. A future ACSoft stock Product must not silently replace this identity. */
class EloquentProductCatalog implements ProductCatalog
{
    public const RELATIONS = [
        'categoria', 'ingredientes', 'bloques.gruposOpciones.opciones.ingrediente',
        'gruposOpciones.opciones.ingrediente', 'reglas',
    ];

    public function __construct(private CatalogProjection $projection) {}

    public function definition(Producto $product): ProductDefinition
    {
        $product->loadMissing(self::RELATIONS);

        return new ProductDefinition(
            (int) $product->id, $product->nombre, $product->descripcion, $product->slug, (string) $product->precio,
            $product->activo && (bool) $product->categoria?->activo, $product->personalizable,
            $product->unidad_tipo->value, $product->unidades,
            $product->gruposOpciones->map(fn ($g) => $this->projection->group($g))->all(),
            $product->bloques->map(fn ($b) => [
                'id' => (int) $b->id, 'name' => $b->nombre, 'order' => $b->orden,
                'quantity' => $b->cantidadComercial(), 'unit_type' => $b->unidad_tipo->value,
                'measure' => $b->metadata['rule_units'] ?? $b->cantidadComercial(),
                'groups' => $b->gruposOpciones->map(fn ($g) => $this->projection->group($g))->all(),
            ])->all(),
            $product->ingredientes->map(fn ($c) => [
                'id' => (int) $c->id, 'name' => $c->nombre,
                'removable' => (bool) ($c->pivot->metadata['removible'] ?? false),
                'included' => (bool) ($c->pivot->metadata['included_component'] ?? false),
            ])->all(),
            $this->projection->rules($product),
            $product->catalog_policy ?? [],
        );
    }
}
