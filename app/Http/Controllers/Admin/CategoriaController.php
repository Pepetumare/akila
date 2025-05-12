<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        // Obtenemos el término de búsqueda (si hay)
        $q = $request->input('q');

        // Construimos la consulta
        $query = Categoria::query();
        if ($q) {
            $query->where('nombre', 'like', "%{$q}%");
        }

        // Paginamos en lugar de get()
        $categorias = $query
            ->orderBy('nombre')
            ->paginate(10)            // 10 por página, ajústalo a tu gusto
            ->appends(['q' => $q]);   // mantiene el parámetro q en los links

        return view('admin.categorias.index', compact('categorias'));
    }

    public function create()
    {
        return view('admin.categorias.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|unique:categorias,nombre',
        ]);

        Categoria::create($data);
        return redirect()->route('admin.categorias.index')
                         ->with('success','Categoría creada.');
    }

    public function edit(Categoria $categoria)
    {
        return view('admin.categorias.edit', compact('categoria'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $data = $request->validate([
            'nombre' => 'required|string|unique:categorias,nombre,'.$categoria->id,
        ]);

        $categoria->update($data);
        return redirect()->route('admin.categorias.index')
                         ->with('success','Categoría actualizada.');
    }

    public function destroy(Categoria $categoria)
    {
        $categoria->delete();
        return redirect()->route('admin.categorias.index')
                         ->with('success','Categoría eliminada.');
    }
}
