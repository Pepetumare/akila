<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminChange;
use App\Models\Ingrediente;
use App\Services\IngredientImpact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IngredienteController extends Controller
{
    private const TYPES = ['proteina', 'vegetal', 'envoltura', 'base', 'salsa', 'topping', 'otros'];

    public function index(Request $request)
    {
        $query = Ingrediente::withCount('productos', 'opciones')->with(['opciones' => fn ($q) => $q->whereHas('grupo', fn ($g) => $g->where('slug', 'ingredientes-extras'))]);
        if ($q = $request->string('q')->limit(100)->toString()) { $query->where('nombre', 'like', "%{$q}%"); }
        if ($request->filled('tipo')) { $query->where('tipo', $request->input('tipo')); }
        $ingredientes = $query->orderBy('nombre')->paginate(15)->withQueryString();
        $tipos = self::TYPES;

        return view('admin.ingredientes.index', compact('ingredientes', 'tipos'));
    }

    public function create()
    {
        return view('admin.ingredientes.edit', ['ingrediente' => new Ingrediente, 'tipos' => self::TYPES, 'affected' => collect()]);
    }

    public function edit(Ingrediente $ingrediente, IngredientImpact $impact)
    {
        return view('admin.ingredientes.edit', ['ingrediente' => $ingrediente->loadCount('opciones'), 'tipos' => self::TYPES, 'affected' => $impact->products($ingrediente)]);
    }

    private function data(Request $request, ?Ingrediente $ingredient = null): array
    {
        return $request->validate(['nombre' => ['required', 'string', 'max:255', Rule::unique('ingredientes')->ignore($ingredient?->id)],
            'tipo' => ['required', Rule::in(self::TYPES)], 'costo' => 'nullable|numeric|min:0|max:999999.99|decimal:0,2']);
    }

    public function store(Request $request)
    {
        $ingredient = Ingrediente::create($this->data($request));
        AdminChange::record($ingredient, 'creado', ['nombre' => ['before' => null, 'after' => $ingredient->nombre]]);
        return redirect()->route('admin.ingredientes.index')->with('success', 'Ingrediente creado.');
    }

    public function update(Request $request, Ingrediente $ingrediente)
    {
        $data = $this->data($request, $ingrediente);
        DB::transaction(function () use ($ingrediente, $data) {
            $before = $ingrediente->only(array_keys($data));
            $ingrediente->update($data);
            AdminChange::record($ingrediente, 'actualizado', ['fields' => ['before' => $before, 'after' => $data]]);
        });
        return back()->with('success', 'Ingrediente actualizado.');
    }

    public function availability(Request $request, Ingrediente $ingrediente)
    {
        $data = $request->validate(['is_available' => 'required|boolean']);
        DB::transaction(function () use ($ingrediente, $data) {
            $before = $ingrediente->is_available;
            $ingrediente->update($data);
            AdminChange::record($ingrediente, 'disponibilidad', ['is_available' => ['before' => $before, 'after' => $ingrediente->is_available]]);
        });
        return back()->with('success', 'Disponibilidad actualizada. Las composiciones fijas requieren decisión del administrador.');
    }

    public function destroy(Ingrediente $ingrediente)
    {
        return back()->withErrors('Conserva el ingrediente y utiliza disponibilidad. El historial y sus relaciones no deben eliminarse.');
    }
}
