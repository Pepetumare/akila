<?php

namespace Database\Seeders\SushiAkila;

use App\Enums\TipoProducto;
use App\Enums\UnidadComercial;
use App\Models\BloquePromocion;
use App\Models\Categoria;
use App\Models\GrupoOpcion;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MenuBatchImporter
{
    private array $products = [];

    private string $batch;

    private array $ingredients = [];

    private array $groups = [];

    private array $groupDefinitions = [];

    public function run(array $products, string $batch, array $ingredients = [], array $groups = []): void
    {
        DB::transaction(function () use ($products, $batch, $ingredients, $groups) {
            $this->products = $products;
            $this->batch = $batch;
            $this->ingredients = [];
            $this->groups = [];
            $this->groupDefinitions = $groups;
            foreach (array_merge(Batch01Data::ingredients(), $ingredients) as $name => $type) {
                $this->ingredients[$name] = MenuIdentity::ingredient($name, $type);
            }
            $categories = [];
            foreach ($products as $data) {
                if (isset($data['categoria'])) {
                    $name = $data['categoria'];
                    $categories[Str::slug($name)] = Categoria::updateOrCreate(['slug' => Str::slug($name)], [
                        'nombre' => $name, 'activo' => true, 'orden' => $data['categoria_orden'],
                    ]);
                }
            }
            foreach (['Promociones', 'Promociones a elección'] as $index => $name) {
                $categories[$index] = Categoria::updateOrCreate(['slug' => Str::slug($name)], [
                    'nombre' => $name, 'activo' => true, 'orden' => $index + 1,
                ]);
            }
            $this->seedGroups();
            foreach ($this->products as $index => $data) {
                $this->seedProduct($index, $data, $categories);
            }
        });
    }

    private function seedGroups(): void
    {
        $this->group('proteina', 'Proteína', Batch01Data::PROTEINS);
        $this->group('vegetal', 'Vegetal', Batch01Data::VEGETABLES);
        $this->group('envoltura', 'Envoltura', Batch01Data::WRAPPERS);
        $this->group('relleno-30-piezas-a-eleccion', 'Relleno — 30 Piezas a Elección', Batch01Data::CHOICE_FILLINGS);
        $this->group('preparacion', 'Preparación', ['Envuelto', 'Frío']);
        foreach ($this->products as $data) {
            if (! ($data['personalizable'] ?? false)) {
                continue;
            }
            if (isset($data['grupo_relleno']) && ! isset($this->groups[$data['grupo_relleno']])) {
                $this->group($data['grupo_relleno'], 'Relleno', $data['rellenos_disponibles']);
            }
            if (isset($data['grupo_envoltura']) && ! isset($this->groups[$data['grupo_envoltura']])) {
                $this->group($data['grupo_envoltura'], 'Envoltura', $data['envolturas_disponibles']);
            }
        }
        $fixedFillings = [];
        foreach ($this->products as $data) {
            foreach ($data['bloques'] as $block) {
                $fixedFillings = array_merge($fixedFillings, $block['relleno'] ?? []);
            }
        }
        foreach (array_unique($fixedFillings) as $name) {
            $this->group('relleno-fijo-'.Str::slug($name), 'Relleno fijo: '.$name, [$name]);
        }
        foreach (['Frito', 'Palta', 'Queso crema'] as $name) {
            $this->group('envoltura-fija-'.Str::slug($name), 'Envoltura fija: '.$name, [$name]);
        }
        foreach ($this->groupDefinitions as $slug => $definition) {
            $this->group($slug, $definition['nombre'], $definition['opciones'], $definition['recargo'] ?? '0.00');
        }
    }

    private function group(string $slug, string $name, array $options, string $surcharge = '0.00'): GrupoOpcion
    {
        $group = GrupoOpcion::updateOrCreate(['slug' => $slug], [
            'nombre' => $name, 'tipo_seleccion' => 'single', 'min_selections' => 1,
            'max_selections' => 1, 'activo' => true, 'orden' => count($this->groups) + 1,
        ]);
        foreach ($options as $index => $optionName) {
            $group->opciones()->updateOrCreate(['slug' => Str::slug($optionName)], [
                'nombre' => $optionName, 'ingrediente_id' => $this->ingredients[$optionName]->id ?? null,
                'recargo' => $surcharge, 'activo' => true, 'orden' => $index + 1,
            ]);
        }
        $this->groups[$slug] = $group;

        return $group;
    }

    private function seedProduct(string $slug, array $data, array $categories): void
    {
        $unit = UnidadComercial::from($data['unidad_tipo'] ?? 'pieza');
        $quantity = $data['unidades'] ?? $data['piezas'];
        $blocksQuantity = array_sum(array_map(fn ($block) => $block['cantidad'] ?? ($unit === UnidadComercial::Pieza ? 10 : 1), $data['bloques']));
        if ($data['bloques'] !== [] && $blocksQuantity !== $quantity) {
            throw new RuntimeException("Cantidad de bloques inconsistente: {$slug}");
        }
        $custom = $data['personalizable'] ?? false;
        $product = MenuIdentity::product($slug, [
            'nombre' => $data['nombre'], 'descripcion' => $data['descripcion'], 'precio' => $data['precio'],
            'categoria_id' => $categories[isset($data['categoria']) ? Str::slug($data['categoria']) : ($custom ? 1 : 0)]->id,
            'tipo' => $data['tipo'] ?? TipoProducto::Promocion, 'personalizable' => $custom, 'unidades' => $quantity, 'unidad_tipo' => $unit,
            'activo' => true, 'orden' => $data['orden'] ?? array_search($slug, array_keys($this->products), true) + 1,
            'rolls_total' => $unit === UnidadComercial::Pieza ? intdiv($quantity, 10) : 0,
            'rolls_fritos' => intdiv($data['preparacion']['frito'] ?? 0, 10),
            'rolls_envueltos' => intdiv($data['preparacion']['envuelto'] ?? 0, 10),
        ]);
        if (($data['promocion'] ?? true)) {
            Promocion::updateOrCreate(['producto_id' => $product->id], [
                'nombre' => $data['nombre'], 'descripcion' => $data['descripcion'], 'precio' => $data['precio'],
                'cantidad_rolls' => $unit === UnidadComercial::Pieza ? intdiv($quantity, 10) : $quantity, 'personalizable' => $custom,
                'categoria' => isset($data['categoria']) ? Str::slug($data['categoria']) : ($custom ? 'promociones-a-eleccion' : 'promociones'),
            ]);
        }
        $this->composition($product, $data);
        foreach ($data['bloques'] as $index => $definition) {
            $block = $product->bloques()->updateOrCreate(['orden' => $index + 1], [
                'nombre' => ($unit === UnidadComercial::Pieza ? 'Bloque' : $unit->blockLabel()).' '.($index + 1), 'unidad_tipo' => $unit,
                'cantidad_piezas' => $unit === UnidadComercial::Pieza ? ($definition['cantidad'] ?? 10) : null,
                'cantidad_unidades' => $unit !== UnidadComercial::Pieza ? ($definition['cantidad'] ?? 1) : null,
                'metadata' => ['importacion' => $this->batch, 'foto' => $data['foto']],
            ]);
            $this->blockGroups($block, $definition, $data);
        }
        $productAssignments = [];
        foreach ($data['grupos_producto'] ?? [] as $order => $groupSlug) {
            $productAssignments[$this->groups[$groupSlug]->id] = $this->assignment($order + 1);
        }
        $product->gruposOpciones()->syncWithoutDetaching($productAssignments);
        $this->rules($product, $slug, $data);
        // Keep superseded records/IDs for traceability; never delete historical configuration.
        foreach ($product->reglas()->whereIn('codigo', $data['reglas_superadas'] ?? [])->get() as $rule) {
            if (in_array($rule->metadata['importacion'] ?? null, ['batch01', $this->batch], true)) {
                $rule->update(['activo' => false, 'metadata' => array_merge($rule->metadata ?? [], [
                    'superada_por' => $this->batch, 'foto_autoridad' => $data['foto'],
                ])]);
            }
        }
    }

    private function composition(Producto $product, array $data): void
    {
        $fillings = [];
        $wrappers = [];
        $pieces = ($data['unidad_tipo'] ?? 'pieza') === 'pieza';
        foreach ($data['bloques'] as $block) {
            foreach ($block['relleno'] ?? [] as $name) {
                $fillings[$name] = ($fillings[$name] ?? 0) + ($block['cantidad'] ?? ($pieces ? 10 : 1));
            }
            $wrapper = $block['envoltura'] ?? null;
            if (isset($this->ingredients[$wrapper ?? ''])) {
                $wrappers[$wrapper] = ($wrappers[$wrapper] ?? 0) + 10;
            }
        }
        $names = array_unique(array_merge(Batch01Data::BASE, $data['composicion_fija'] ?? [], array_keys($fillings), array_keys($wrappers)));
        $sync = [];
        foreach ($names as $index => $name) {
            $base = in_array($name, Batch01Data::BASE, true);
            $sync[$this->ingredients[$name]->id] = [
                'cantidad' => null, 'unidad' => null,
                // Legacy roll-count contract, never a physical kitchen quantity.
                'cantidad_permitida' => $base || ! $pieces ? null : intdiv($fillings[$name] ?? 0, 10),
                'obligatorio' => ! $base || ! ($data['base_removible'] ?? true), 'reemplazable' => isset($data['composicion_fija']) ? false : ! $base, 'orden' => $index + 1,
                'metadata' => ['importacion' => $this->batch, 'foto' => $data['foto'],
                    'removible' => $base && ($data['base_removible'] ?? true),
                    'composicion_fija' => in_array($name, $data['composicion_fija'] ?? [], true), 'piezas_relleno' => $pieces ? ($fillings[$name] ?? null) : null,
                    'unidades_relleno' => ! $pieces ? ($fillings[$name] ?? null) : null,
                    'piezas_envoltura' => $wrappers[$name] ?? null],
            ];
        }
        $product->ingredientes()->syncWithoutDetaching($sync);
    }

    private function blockGroups(BloquePromocion $block, array $definition, array $data): void
    {
        $sync = [];
        if ($definition['a_eleccion'] ?? false) {
            $sync[$this->groups[$data['grupo_relleno'] ?? 'relleno-30-piezas-a-eleccion']->id] = $this->assignment(1);
            // Available wrappers, no invented mandatory/default wrapper in the source.
            if (! array_key_exists('envolturas_disponibles', $data) || $data['envolturas_disponibles'] !== []) {
                $sync[$this->groups[$data['grupo_envoltura'] ?? 'envoltura']->id] = $this->assignment(2, false);
            }
        } else {
            foreach ($definition['relleno'] as $index => $name) {
                $sync[$this->groups['relleno-fijo-'.Str::slug($name)]->id] = $this->assignment($index + 1);
            }
            if (isset($definition['envoltura'])) {
                $sync[$this->groups['envoltura-fija-'.Str::slug($definition['envoltura'])]->id] = $this->assignment(10);
            }
        }
        if (isset($data['segundo_ingrediente'])) {
            $sync[$this->groups[$data['segundo_ingrediente']]->id] = $this->assignment(20, false);
        }
        $block->gruposOpciones()->syncWithoutDetaching($sync);
    }

    private function assignment(int $order, bool $required = true): array
    {
        return ['min_selections' => $required ? 1 : 0, 'max_selections' => 1, 'obligatorio' => $required, 'orden' => $order];
    }

    private function rules(Producto $product, string $slug, array $data): void
    {
        $rulePhoto = $data['foto_reglas'] ?? (in_array($data['foto'], [3, 10], true) ? $data['foto'] : 2);
        $fillings = $data['rellenos_disponibles'] ?? ($slug === '30-piezas-a-eleccion'
            ? Batch01Data::CHOICE_FILLINGS : array_merge(Batch01Data::PROTEINS, Batch01Data::VEGETABLES));
        // The specific max-2 clauses constrain optional choices/changes, not fixed recipes.
        if ($data['repeticiones'] ?? in_array($slug, ['30-piezas-a-eleccion', 'lo-nuevo'], true)) {
            foreach ($fillings as $name) {
                $this->rule($product, 'repeticion-'.Str::slug($name), 'max_repeticiones',
                    ['ingrediente_id' => $this->ingredients[$name]->id], $data['limites_repeticion'][$name] ?? ($name === 'Salmón' ? 1 : 2),
                    ['foto' => $data['foto'], 'ambito' => 'promocion', 'uso' => 'selecciones_personalizadas']);
            }
        }
        foreach (($data['cambios'] ?? true) ? $fillings : [] as $name) {
            $this->rule($product, 'cambio-relleno-'.Str::slug($name), 'recargo',
                ['ingrediente_id' => $this->ingredients[$name]->id], '1000.00',
                ['foto' => $rulePhoto,
                    'condicion' => 'modificacion_relleno', 'unidad' => 'roll']);
        }
        $wrapperOptions = $this->groups[$data['grupo_envoltura'] ?? 'envoltura']->opciones()->get();
        if (array_key_exists('envolturas_disponibles', $data)) {
            $wrapperOptions = $wrapperOptions->whereIn('nombre', $data['envolturas_disponibles']);
        }
        foreach (($data['cambios'] ?? true) ? $wrapperOptions : [] as $option) {
            $this->rule($product, 'cambio-envoltura-'.$option->slug, 'recargo', ['opcion_id' => $option->id], '1000.00',
                ['foto' => $rulePhoto,
                    'condicion' => 'modificacion_envoltura', 'unidad' => 'roll']);
        }
        foreach ($data['recargos_ingredientes'] ?? [] as $name => $amount) {
            $this->rule($product, 'premium-'.Str::slug($name), 'recargo', ['ingrediente_id' => $this->ingredients[$name]->id],
                $amount, ['foto' => $data['foto_reglas'] ?? $data['foto'], 'unidad' => 'unidad']);
        }
        foreach ($data['preparacion'] as $name => $pieces) {
            $group = $this->groups[$name === 'frito' ? 'envoltura' : 'preparacion'];
            $option = $group->opciones()->where('slug', $name)->sole();
            $this->rule($product, 'piezas-'.$name, 'cantidad_incluida', ['opcion_id' => $option->id], $pieces,
                ['foto' => $data['foto'], 'unidad' => 'pieza', 'uso' => 'preparacion_global',
                    'envolturas_declaradas' => $data['envolturas_declaradas'] ?? [],
                    'distribucion_por_bloque' => count(array_filter($data['bloques'], fn ($block) => isset($block['envoltura']))) === count($data['bloques'])]);
        }
    }

    private function rule(Producto $product, string $code, string $type, array $subject, int|string $value, array $metadata): void
    {
        $product->reglas()->updateOrCreate(['codigo' => $code], [
            'tipo' => $type, 'ingrediente_id' => $subject['ingrediente_id'] ?? null,
            'opcion_id' => $subject['opcion_id'] ?? null, 'valor' => $value, 'activo' => true,
            'metadata' => ['importacion' => $this->batch] + $metadata,
        ]);
    }
}
