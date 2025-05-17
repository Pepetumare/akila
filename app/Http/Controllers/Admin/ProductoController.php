<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Ingrediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        // Generamos la URL de la imagen para la vista
        $productos->getCollection()->transform(function ($producto) {
            if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
                // /storage/productos/archivo.jpg
                $producto->url_imagen = Storage::disk('public')->url($producto->imagen);
            } else {
                // public/images/default-product.png
                $producto->url_imagen = asset('images/default-product.png');
            }
            return $producto;
        });

        $categorias = Categoria::pluck('nombre', 'id')
            ->map(fn($nombre) => str_replace('\\', ' / ', $nombre));

        $ingredientes = Ingrediente::orderBy('nombre')->get();

        return view('admin.productos.index', compact('productos', 'categorias', 'ingredientes'));
    }

    public function create()
    {
        $categorias   = Categoria::orderBy('nombre')->pluck('nombre', 'id');
        $ingredientes = Ingrediente::orderBy('nombre')->get();

        return view('admin.productos.create', compact('categorias', 'ingredientes'));
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
            'ingredientes'         => 'nullable|array',
            'ingredientes.*'       => 'exists:ingredientes,id',
            'cantidad_permitida'   => 'nullable|array',
            'cantidad_permitida.*' => 'integer|min:1',
        ]);

        $data['personalizable'] = (bool) $data['personalizable'];

        if ($request->hasFile('imagen')) {
            // Se guarda en storage/app/public/productos
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto = Producto::create($data);

        // Sincronizar pivot ingredientes
        $syncData = [];
        foreach ($request->input('ingredientes', []) as $id) {
            $syncData[$id] = [
                'cantidad_permitida' => $request->input("cantidad_permitida.{$id}", 1),
            ];
        }
        $producto->ingredientes()->sync($syncData);

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto)
    {
        $categorias   = Categoria::orderBy('nombre')->pluck('nombre', 'id');
        $ingredientes = Ingrediente::orderBy('nombre')->get();

        return view('admin.productos.edit', compact('producto', 'categorias', 'ingredientes'));
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
            'ingredientes'         => 'nullable|array',
            'ingredientes.*'       => 'exists:ingredientes,id',
            'cantidad_permitida'   => 'nullable|array',
            'cantidad_permitida.*' => 'integer|min:1',
        ]);

        $data['personalizable'] = (bool) $data['personalizable'];

        if ($request->hasFile('imagen')) {
            // Borramos la anterior si existe
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto->update($data);

        // Sincronizar pivot ingredientes
        $syncData = [];
        foreach ($request->input('ingredientes', []) as $id) {
            $syncData[$id] = [
                'cantidad_permitida' => $request->input("cantidad_permitida.{$id}", 1),
            ];
        }
        $producto->ingredientes()->sync($syncData);

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
