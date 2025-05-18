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
        // 1) Validación incluyendo campos de rolls e ingredientes
        $data = $request->validate([
            'nombre'               => 'required|string',
            'descripcion'          => 'nullable|string',
            'precio'               => 'required|numeric|min:0',
            'categoria_id'         => 'required|exists:categorias,id',
            'imagen'               => 'nullable|image|max:2048',
            'personalizable'       => 'required|in:0,1',
            'unidades'             => 'required|integer|min:1',
            // Validación de rolls
            'rolls_total'          => 'required|integer|min:0',
            'rolls_envueltos'      => 'required|integer|min:0|lte:rolls_total',
            'rolls_fritos'         => 'required|integer|min:0|lte:rolls_total',
            // Validación de ingredientes
            'ingredientes'         => 'nullable|array',
            'ingredientes.*'       => 'exists:ingredientes,id',
            'cantidad_permitida'   => 'nullable|array',
            'cantidad_permitida.*' => 'integer|min:1',
        ]);

        // 2) Cast del checkbox
        $data['personalizable'] = (bool) $data['personalizable'];

        // 3) Guardar imagen si existe
        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        // 4) Crear el producto con los campos básicos + rolls
        $producto = Producto::create([
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'],
            'precio'         => $data['precio'],
            'categoria_id'   => $data['categoria_id'],
            'personalizable' => $data['personalizable'],
            'unidades'       => $data['unidades'],
            'rolls_total'    => $data['rolls_total'],
            'rolls_envueltos' => $data['rolls_envueltos'],
            'rolls_fritos'   => $data['rolls_fritos'],
            'imagen'         => $data['imagen'] ?? null,
        ]);

        // 5) Sincronizar pivote producto_ingrediente
        $syncData = [];
        foreach ($request->input('ingredientes', []) as $ingId) {
            $syncData[$ingId] = [
                'cantidad_permitida' => $request->input("cantidad_permitida.{$ingId}", 1),
            ];
        }
        $producto->ingredientes()->sync($syncData);

        // 6) Redirigir con mensaje de éxito
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
        // 1) Validación incluyendo campos de rolls e ingredientes
        $data = $request->validate([
            'nombre'               => 'required|string',
            'descripcion'          => 'nullable|string',
            'precio'               => 'required|numeric|min:0',
            'categoria_id'         => 'required|exists:categorias,id',
            'imagen'               => 'nullable|image|max:2048',
            'personalizable'       => 'required|in:0,1',
            'unidades'             => 'required|integer|min:1',
            // Validación de rolls
            'rolls_total'          => 'required|integer|min:0',
            'rolls_envueltos'      => 'required|integer|min:0|lte:rolls_total',
            'rolls_fritos'         => 'required|integer|min:0|lte:rolls_total',
            // Validación de ingredientes
            'ingredientes'         => 'nullable|array',
            'ingredientes.*'       => 'exists:ingredientes,id',
            'cantidad_permitida'   => 'nullable|array',
            'cantidad_permitida.*' => 'integer|min:1',
        ]);

        // 2) Cast del checkbox
        $data['personalizable'] = (bool) $data['personalizable'];

        // 3) Procesar posible nueva imagen
        if ($request->hasFile('imagen')) {
            if ($producto->imagen) {
                \Storage::disk('public')->delete($producto->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        // 4) Actualizar campos básicos + rolls
        $producto->update([
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'],
            'precio'         => $data['precio'],
            'categoria_id'   => $data['categoria_id'],
            'personalizable' => $data['personalizable'],
            'unidades'       => $data['unidades'],
            'rolls_total'    => $data['rolls_total'],
            'rolls_envueltos' => $data['rolls_envueltos'],
            'rolls_fritos'   => $data['rolls_fritos'],
            'imagen'         => $data['imagen'] ?? $producto->imagen,
        ]);

        // 5) Sincronizar pivote ingrediente_→producto
        $syncData = [];
        foreach ($request->input('ingredientes', []) as $ingId) {
            $syncData[$ingId] = [
                'cantidad_permitida' => $request->input("cantidad_permitida.{$ingId}", 1),
            ];
        }
        // Esto borrará todas las relaciones no incluidas en $syncData
        $producto->ingredientes()->sync($syncData);

        // 6) Redirigir con éxito
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
