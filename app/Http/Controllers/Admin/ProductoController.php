<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Ingrediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::query();

        if ($q = $request->input('q')) {
            $query->where('nombre', 'like', "%{$q}%");
        }

        $productos = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->only('q'));

        $productos->getCollection()->transform(function ($producto) {
            $producto->url_imagen = $producto->imagen && Storage::disk('public')->exists($producto->imagen)
                ? Storage::disk('public')->url($producto->imagen)
                : asset('img/no_disponible.png');
            return $producto;
        });

        $categorias = Categoria::pluck('nombre', 'id');
        // para filtros rápidos, cargamos todos los ingredientes
        $ingredientes = Ingrediente::orderBy('nombre')->get();

        $wrappers   = $ingredientes->where('tipo', 'envoltura');
        $proteins   = $ingredientes->where('tipo', 'proteina');
        $vegetables = $ingredientes->where('tipo', 'vegetal');

        return view('admin.productos.index', compact(
            'productos',
            'categorias',
            'ingredientes',
            'wrappers',
            'proteins',
            'vegetables'
        ));
    }
    // public function index(Request $request)
    // {
    //     $productos = Producto::with('ingredientes')
    //         ->orderBy('created_at', 'desc')
    //         ->paginate(12)
    //         ->appends($request->only('filter'));

    //     $categorias = Categoria::orderBy('nombre')->get();

    //     return view('menu', compact('productos', 'categorias'));
    // }


    public function create()
    {
        $categorias   = Categoria::orderBy('nombre')->pluck('nombre', 'id');
        $wrappers     = Ingrediente::where('tipo', 'envoltura')->orderBy('nombre')->get();
        $proteins     = Ingrediente::where('tipo', 'proteina')->orderBy('nombre')->get();
        $vegetables   = Ingrediente::where('tipo', 'vegetal')->orderBy('nombre')->get();

        return view('admin.productos.create', compact('categorias', 'wrappers', 'proteins', 'vegetables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'               => 'required|string',
            'descripcion'          => 'nullable|string',
            'precio'               => 'required|numeric|min:0',
            'categoria_id'         => 'required|exists:categorias,id',
            'imagen'               => 'nullable|image|max:2048',
            'personalizable'       => 'required|in:0,1',
            'unidades'             => 'required|integer|min:1',
            'envolturas'           => 'required|array|min:1',
            'envolturas.*'         => 'exists:ingredientes,id',
            'Proteínas'            => 'nullable|array',
            'Proteínas.*'          => 'exists:ingredientes,id',
            'cantidad_proteina'    => 'nullable|array',
            'cantidad_proteina.*'  => 'integer|min:1',
            'vegetales'            => 'nullable|array',
            'vegetales.*'          => 'exists:ingredientes,id',
            'cantidad_vegetal'     => 'nullable|array',
            'cantidad_vegetal.*'   => 'integer|min:1',
        ]);

        // Cast Boolean
        $data['personalizable'] = (bool) $data['personalizable'];

        // Procesar imagen
        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        // —— Validación adicional de cantidades según reglas de negocio ——
        // Proteínas:
        // foreach ($data['Proteínas'] ?? [] as $id) {
        //     $ing   = Ingrediente::findOrFail($id);
        //     $qty   = $data['cantidad_proteina'][$id] ?? 1;
        //     $max   = in_array(strtolower($ing->nombre), ['salmón', 'salmon', 'carne', 'atún', 'atun']) ? 1 : 2;
        //     if ($qty > $max) {
        //         throw ValidationException::withMessages([
        //             "cantidad_proteina.{$id}" => "Máximo {$max} unidad(es) permitido(s) para {$ing->nombre}."
        //         ]);
        //     }
        // }
        // // Vegetales:
        // foreach ($data['vegetales'] ?? [] as $id) {
        //     $qty = $data['cantidad_vegetal'][$id] ?? 1;
        //     if ($qty > 2) {
        //         $ing = Ingrediente::findOrFail($id);
        //         throw ValidationException::withMessages([
        //             "cantidad_vegetal.{$id}" => "Máximo 2 unidades permitido para {$ing->nombre}."
        //         ]);
        //     }
        // }

        // Crear producto
        $producto = Producto::create([
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'] ?? '',
            'precio'         => $data['precio'],
            'categoria_id'   => $data['categoria_id'],
            'personalizable' => $data['personalizable'],
            'unidades'       => $data['unidades'],
            'imagen'         => $data['imagen'] ?? null,
        ]);

        // Sincronizar envoltura + Proteínas + vegetales
        $sync = [];
        // cada base elegida por el admin
        foreach ($data['envolturas'] as $id) {
            $sync[$id] = ['cantidad_permitida' => 1];
        }


        foreach ($data['Proteínas'] ?? [] as $id) {
            $sync[$id] = ['cantidad_permitida' => $data['cantidad_proteina'][$id] ?? 1];
        }
        foreach ($data['vegetales'] ?? [] as $id) {
            $sync[$id] = ['cantidad_permitida' => $data['cantidad_vegetal'][$id] ?? 1];
        }
        $producto->ingredientes()->sync($sync);

        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');

            // 👇 Depurar antes de guardar
            logger('Nombre original: ' . $file->getClientOriginalName());
            logger('Extensión: ' . $file->getClientOriginalExtension());

            $path = $file->store('productos', 'public');

            logger('Guardado en: ' . $path);

            $data['imagen'] = $path;
        }

        
        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto)
    {
        $categorias   = Categoria::orderBy('nombre')->pluck('nombre', 'id');
        $wrappers     = Ingrediente::where('tipo', 'envoltura')->orderBy('nombre')->get();
        $proteins     = Ingrediente::where('tipo', 'proteina')->orderBy('nombre')->get();
        $vegetables   = Ingrediente::where('tipo', 'vegetal')->orderBy('nombre')->get();

        return view('admin.productos.edit', compact('producto', 'categorias', 'wrappers', 'proteins', 'vegetables'));
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'nombre'               => 'required|string',
            'descripcion'          => 'nullable|string',
            'precio'               => 'required|numeric|min:0',
            'categoria_id'         => 'required|exists:categorias,id',
            'imagen'               => 'nullable|image|max:2048',
            'personalizable'       => 'required|in:0,1',
            'unidades'             => 'required|integer|min:1',
            'envolturas'           => 'required|array|min:1',
            'envolturas.*'         => 'exists:ingredientes,id',
            'Proteínas'            => 'nullable|array',
            'Proteínas.*'          => 'exists:ingredientes,id',
            'cantidad_proteina'    => 'nullable|array',
            'cantidad_proteina.*'  => 'integer|min:1',
            'vegetales'            => 'nullable|array',
            'vegetales.*'          => 'exists:ingredientes,id',
            'cantidad_vegetal'     => 'nullable|array',
            'cantidad_vegetal.*'   => 'integer|min:1',
        ]);

        $data['personalizable'] = (bool) $data['personalizable'];

        if ($request->hasFile('imagen')) {
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        // Validación extra idéntica a store()
        // foreach ($data['Proteínas'] ?? [] as $id) {
        //     $ing = Ingrediente::findOrFail($id);
        //     $qty = $data['cantidad_proteina'][$id] ?? 1;
        //     $max = in_array(strtolower($ing->nombre), ['salmón', 'salmon', 'carne', 'atún', 'atun']) ? 1 : 2;
        //     if ($qty > $max) {
        //         throw ValidationException::withMessages([
        //             "cantidad_proteina.{$id}" => "Máximo {$max} unidad(es) permitido(s) para {$ing->nombre}."
        //         ]);
        //     }
        // }
        // foreach ($data['vegetales'] ?? [] as $id) {
        //     $qty = $data['cantidad_vegetal'][$id] ?? 1;
        //     if ($qty > 2) {
        //         $ing = Ingrediente::findOrFail($id);
        //         throw ValidationException::withMessages([
        //             "cantidad_vegetal.{$id}" => "Máximo 2 unidades permitido para {$ing->nombre}."
        //         ]);
        //     }
        // }

        // Actualizar producto
        $producto->update([
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'] ?? '',
            'precio'         => $data['precio'],
            'categoria_id'   => $data['categoria_id'],
            'personalizable' => $data['personalizable'],
            'unidades'       => $data['unidades'],
            'imagen'         => $data['imagen'] ?? $producto->imagen,
        ]);

        // Sincronizar ingrediente-producto
        $sync = [];
        // cada base elegida por el admin
        foreach ($data['envolturas'] as $id) {
            $sync[$id] = ['cantidad_permitida' => 1];
        }

        foreach ($data['Proteínas'] ?? [] as $id) {
            $sync[$id] = ['cantidad_permitida' => $data['cantidad_proteina'][$id] ?? 1];
        }
        foreach ($data['vegetales'] ?? [] as $id) {
            $sync[$id] = ['cantidad_permitida' => $data['cantidad_vegetal'][$id] ?? 1];
        }
        $producto->ingredientes()->sync($sync);

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }
        $producto->delete();

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto eliminado correctamente.');
    }
}
