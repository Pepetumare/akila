<?php
// app/Http/Controllers/Admin/IngredienteController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingrediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredienteController extends Controller
{
    /**
     * Obtener los posibles valores del enum 'tipo' desde la definición de la columna.
     *
     * @return array
     */
    protected function getTipos(): array
    {
        // Consultar la estructura de la columna 'tipo'
        $column = DB::selectOne("SHOW COLUMNS FROM ingredientes WHERE Field = 'tipo'");
        // Extraer valores entre comillas con regex
        preg_match_all("/'([^']+)'/", $column->Type, $matches);
        return $matches[1] ?? [];
    }

    public function index(Request $request)
    {
        $ingredientes = Ingrediente::orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->only('q'));

        // Obtener los tipos de ingrediente para el select en los modales
        $tipos = $this->getTipos();

        return view('admin.ingredientes.index', compact('ingredientes', 'tipos'));
    }
public function create()
    {
        // Pasar los tipos al formulario de creación
        $tipos = $this->getTipos();
        return view('admin.ingredientes.create', compact('tipos'));
    }

    public function store(Request $request)
    {
        $tipos = $this->getTipos();
        $data = $request->validate([
            'nombre' => 'required|string|unique:ingredientes,nombre',
            'tipo'   => 'required|in:' . implode(',', $tipos),
            'costo'  => 'nullable|numeric|min:0',
        ]);

        if ($data['tipo'] === 'base') {
            $data['costo'] = null;
        }

        Ingrediente::create($data);

        return redirect()
            ->route('admin.ingredientes.index')
            ->with('success', 'Ingrediente creado correctamente.');
    }

    public function edit(Ingrediente $ingrediente)
    {
        // Pasar ingredientes y tipos al formulario de edición
        $tipos = $this->getTipos();
        return view('admin.ingredientes.edit', compact('ingrediente', 'tipos'));
    }

    public function update(Request $request, Ingrediente $ingrediente)
    {
        $tipos = $this->getTipos();
        $data = $request->validate([
            'nombre' => 'required|string|unique:ingredientes,nombre,' . $ingrediente->id,
            'tipo'   => 'required|in:' . implode(',', $tipos),
            'costo'  => 'nullable|numeric|min:0',
        ]);

        if ($data['tipo'] === 'base') {
            $data['costo'] = null;
        }

        $ingrediente->update($data);

        return redirect()
            ->route('admin.ingredientes.index')
            ->with('success', 'Ingrediente actualizado correctamente.');
    }

    public function destroy(Ingrediente $ingrediente)
    {
        $ingrediente->delete();

        return redirect()
            ->route('admin.ingredientes.index')
            ->with('success', 'Ingrediente eliminado correctamente.');
    }
}
