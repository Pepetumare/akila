<?php
// app/Http/Controllers/Admin/ProductoController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Categoria;
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

        $categorias = Categoria::pluck('nombre', 'id');

        return view('admin.productos.index', compact('productos', 'categorias'));
    }


    public function create()
    {
        $categorias = Categoria::orderBy('nombre')->pluck('nombre', 'id');
        return view('admin.productos.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        // Validación
        $data = $request->validate([
            'nombre'         => 'required|string',
            'descripcion'    => 'nullable|string',
            'precio'         => 'required|numeric|min:0',
            'categoria_id'   => 'required|exists:categorias,id',
            'imagen'         => 'nullable|image|max:2048',
            'personalizable' => 'required|in:0,1',
            'unidades'       => 'required|integer|min:1',
            'swappables'     => 'nullable|array',
            'swappables.*'   => 'exists:ingredientes,id',
        ]);

        // Convertir a booleano
        $data['personalizable'] = (bool) $data['personalizable'];

        // Subir imagen si viene
        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')
                ->store('productos', 'public');
        }

        // Crear producto y obtener instancia
        $producto = Producto::create($data);

        // Gestionar “A tu pinta”
        if ($producto->categoria->slug === 'a-tu-pinta') {
            $producto->swappables()->sync($request->input('swappables', []));
        } else {
            $producto->swappables()->detach();
        }

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto)
    {
        $categorias = Categoria::orderBy('nombre')->pluck('nombre', 'id');
        return view('admin.productos.edit', compact('producto', 'categorias'));
    }

    public function update(Request $request, Producto $producto)
    {
        // Validación
        $data = $request->validate([
            'nombre'         => 'required|string',
            'descripcion'    => 'nullable|string',
            'precio'         => 'required|numeric|min:0',
            'categoria_id'   => 'required|exists:categorias,id',
            'imagen'         => 'nullable|image|max:2048',
            'personalizable' => 'required|in:0,1',
            'unidades'       => 'required|integer|min:1',
            'swappables'     => 'nullable|array',
            'swappables.*'   => 'exists:ingredientes,id',
        ]);

        // Convertir personalizable a booleano
        $data['personalizable'] = (bool) $data['personalizable'];

        // Manejar imagen nueva
        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            // Subir la nueva
            $data['imagen'] = $request->file('imagen')
                ->store('productos', 'public');
        }

        // Actualizar datos del producto
        $producto->update($data);

        // Gestionar “A tu pinta”
        if ($producto->categoria->slug === 'a-tu-pinta') {
            $producto->swappables()->sync($request->input('swappables', []));
        } else {
            $producto->swappables()->detach();
        }

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        // Borra imagen si existe
        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }
        $producto->delete();

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto eliminado correctamente.');
    }
}
