<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $categorias = Categoria::withCount('productos')->withCount(['productos as activos_count' => fn ($q) => $q->where('activo', true)])->orderBy('orden')->paginate(15);
        return view('admin.categorias.index', compact('categorias'));
    }

    public function create() { return view('admin.categorias.edit', ['categoria' => new Categoria]); }

    public function edit(Categoria $categoria) { return view('admin.categorias.edit', compact('categoria')); }

    private function data(Request $request, ?Categoria $category = null): array
    {
        return $request->validate(['nombre' => ['required', 'string', 'max:255', Rule::unique('categorias')->ignore($category?->id)],
            'activo' => 'sometimes|boolean', 'orden' => 'sometimes|integer|min:0|max:100000']);
    }

    public function store(Request $request)
    {
        Categoria::create($this->data($request));
        return redirect()->route('admin.categorias.index')->with('success', 'Categoría creada.');
    }

    public function update(Request $request, Categoria $categoria)
    {
        $categoria->update($this->data($request, $categoria));
        return redirect()->route('admin.categorias.index')->with('success', 'Categoría actualizada.');
    }

    public function destroy(Categoria $categoria)
    {
        if ($categoria->productos()->exists()) { return back()->withErrors('La categoría contiene productos. Consérvala y desactívala.'); }
        $categoria->delete();
        return back()->with('success', 'Categoría eliminada.');
    }
}
