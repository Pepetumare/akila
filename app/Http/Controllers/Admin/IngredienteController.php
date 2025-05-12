<?php
// app/Http/Controllers/Admin/IngredienteController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingrediente;
use Illuminate\Http\Request;

class IngredienteController extends Controller
{
    public function index()
    {
        $ingredientes = Ingrediente::orderBy('nombre')->get();
        return view('admin.ingredientes.index', compact('ingredientes'));
    }

    public function create()
    {
        return view('admin.ingredientes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|unique:ingredientes,nombre',
            'tipo'   => 'required|in:base,extra',
            'costo'  => 'nullable|numeric|min:0',
        ]);

        // Si es base, costo se forza a null
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
        return view('admin.ingredientes.edit', compact('ingrediente'));
    }

    public function update(Request $request, Ingrediente $ingrediente)
    {
        $data = $request->validate([
            'nombre' => 'required|string|unique:ingredientes,nombre,'.$ingrediente->id,
            'tipo'   => 'required|in:base,extra',
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
