<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoProducto;
use App\Enums\UnidadComercial;
use App\Http\Controllers\Controller;
use App\Models\AdminChange;
use App\Models\Categoria;
use App\Models\Producto;
use App\Services\MenuConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'ingredientes'])->withCount('bloques', 'reglas');
        if ($q = $request->string('q')->limit(100)->toString()) {
            $query->where('nombre', 'like', "%{$q}%");
        }
        if ($request->filled('categoria')) { $query->where('categoria_id', $request->integer('categoria')); }
        if (in_array($request->input('activo'), ['0', '1'], true)) { $query->where('activo', $request->boolean('activo')); }
        if (in_array($request->input('personalizable'), ['0', '1'], true)) { $query->where('personalizable', $request->boolean('personalizable')); }
        $productos = $query->orderBy('orden')->orderBy('id')->paginate(15)->withQueryString();
        $categorias = Categoria::orderBy('orden')->pluck('nombre', 'id');

        return view('admin.productos.index', compact('productos', 'categorias'));
    }

    public function create()
    {
        $producto = new Producto(['precio' => 0, 'unidades' => 1, 'activo' => false, 'personalizable' => false]);
        $categorias = Categoria::orderBy('orden')->pluck('nombre', 'id');
        $history = collect();

        return view('admin.productos.edit', compact('producto', 'categorias', 'history'));
    }

    public function edit(Producto $producto)
    {
        $producto->load(MenuConfigurationService::RELATIONS);
        $producto->load('reglas.ingrediente', 'reglas.opcion');
        $categorias = Categoria::orderBy('orden')->pluck('nombre', 'id');
        $history = AdminChange::where('entity_type', $producto->getMorphClass())->where('entity_id', $producto->id)->latest()->limit(40)->get();

        return view('admin.productos.edit', compact('producto', 'categorias', 'history'));
    }

    private function data(Request $request, ?Producto $producto = null): array
    {
        if (! $producto && ! $request->filled('slug')) {
            $request->request->remove('slug');
        }
        return $request->validate([
            'nombre' => 'required|string|max:255', 'slug' => ['sometimes', 'required', 'alpha_dash', 'max:255', Rule::unique('productos')->ignore($producto?->id)],
            'descripcion' => 'nullable|string|max:10000', 'precio' => 'required|numeric|min:0.01|max:999999.99|decimal:0,2',
            'categoria_id' => 'required|exists:categorias,id', 'activo' => 'sometimes|boolean',
            'orden' => 'sometimes|integer|min:0|max:100000', 'unidades' => 'sometimes|integer|min:1|max:65535',
            'tipo' => ['sometimes', Rule::enum(TipoProducto::class)], 'unidad_tipo' => ['sometimes', Rule::enum(UnidadComercial::class)],
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        // New products start unpublished until composition has been reviewed.
        $data['activo'] = false;
        $data['personalizable'] = false;
        if ($request->hasFile('imagen')) { $data['imagen'] = $request->file('imagen')->store('productos', 'public'); }
        $producto = DB::transaction(function () use ($data) {
            $product = Producto::create($data);
            if (isset($data['slug'])) { $product->forceFill(['slug' => $data['slug']])->saveQuietly(); }
            AdminChange::record($product, 'creado', ['nombre' => ['before' => null, 'after' => $product->nombre]]);

            return $product;
        });

        return redirect()->route('admin.productos.edit', $producto)->with('success', 'Borrador creado, todavía inactivo. Completa y revisa su composición antes de publicarlo.');
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $this->data($request, $producto);
        if (! $request->hasFile('imagen')) {
            unset($data['imagen']);
        }
        DB::transaction(function () use ($data, $request, $producto) {
            $locked = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
            $locked->load('ingredientes', 'bloques', 'gruposOpciones');
            if (($data['activo'] ?? $locked->activo) && $locked->ingredientes->contains(fn ($i) => data_get($i->pivot->metadata, 'opcion_vegetariana.estado') === 'pendiente_definicion_operativa')) {
                throw ValidationException::withMessages(['activo' => 'Composición diferencial pendiente: este producto debe permanecer inactivo.']);
            }
            if ($locked->bloques->isNotEmpty() && ((int) ($data['unidades'] ?? $locked->unidades) !== (int) $locked->unidades || ($data['unidad_tipo'] ?? $locked->unidad_tipo->value) !== $locked->unidad_tipo->value)) {
                throw ValidationException::withMessages(['unidades' => 'Las unidades están vinculadas a bloques. Se requiere editar y validar la configuración completa.']);
            }
            $before = $locked->only(array_keys($data));
            if ($request->hasFile('imagen')) { $data['imagen'] = $request->file('imagen')->store('productos', 'public'); }
            $locked->fill($data)->save();
            if (isset($data['slug'])) { $locked->forceFill(['slug' => $data['slug']])->saveQuietly(); }
            $changes = [];
            foreach (array_keys($data) as $key) {
                if (($before[$key] ?? null) != $locked->getAttribute($key)) { $changes[$key] = ['before' => $before[$key] ?? null, 'after' => $locked->getAttribute($key)]; }
            }
            AdminChange::record($locked, 'actualizado', $changes);
        });

        return back()->with('success', 'Producto guardado. Bloques, grupos, opciones y reglas conservados.');
    }

    public function availability(Request $request, Producto $producto)
    {
        $request->validate(['activo' => 'required|boolean']);
        $request->merge($producto->only('nombre', 'precio', 'categoria_id'));
        return $this->update($request, $producto);
    }

    public function duplicate(Producto $producto)
    {
        $copy = DB::transaction(function () use ($producto) {
            $producto->load('ingredientes', 'gruposOpciones', 'bloques.gruposOpciones', 'reglas', 'promocion.productos');
            $copy = $producto->replicate();
            $copy->nombre .= ' (copia)';
            $copy->activo = false;
            $copy->save();
            foreach ($producto->ingredientes as $ingredient) {
                $copy->ingredientes()->attach($ingredient->id, $ingredient->pivot->only(['cantidad_permitida', 'cantidad', 'unidad', 'obligatorio', 'reemplazable', 'orden', 'metadata']));
            }
            $assign = fn ($groups) => $groups->mapWithKeys(fn ($group) => [$group->id => $group->pivot->only(['min_selections', 'max_selections', 'obligatorio', 'orden'])])->all();
            $copy->gruposOpciones()->sync($assign($producto->gruposOpciones));
            foreach ($producto->bloques as $block) {
                $newBlock = $block->replicate();
                $newBlock->producto_id = $copy->id;
                $newBlock->save();
                $newBlock->gruposOpciones()->sync($assign($block->gruposOpciones));
            }
            foreach ($producto->reglas as $rule) {
                $newRule = $rule->replicate();
                $newRule->producto_id = $copy->id;
                $newRule->save();
            }
            if ($producto->promocion) {
                $promotion = $producto->promocion->replicate();
                $promotion->producto_id = $copy->id;
                $promotion->nombre = $copy->nombre;
                $promotion->save();
                foreach ($producto->promocion->productos as $component) {
                    $promotion->productos()->attach($component->id, ['cantidad' => $component->pivot->cantidad]);
                }
            }
            AdminChange::record($copy, 'duplicado', ['source' => ['before' => $producto->id, 'after' => $copy->id]]);

            return $copy;
        });

        return redirect()->route('admin.productos.edit', $copy)->with('success', 'Copia creada inactiva. Los grupos compartidos se conservan; no se publica automáticamente.');
    }

    public function destroy(Producto $producto)
    {
        if ($producto->orderItems()->exists() || $producto->promocion()->exists()) {
            return back()->withErrors('Este producto tiene referencias históricas o una promoción vinculada. Consérvalo y desactívalo.');
        }
        return back()->withErrors('Utiliza desactivar para retirar un producto conservando su configuración.');
    }
}
