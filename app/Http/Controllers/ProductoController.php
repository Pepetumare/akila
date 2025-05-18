<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Ingrediente;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index($filter = null)
    {
        $query = Producto::query();

        // Filtros opcionales
        switch ($filter) {
            case 'price_asc':
                $query->orderBy('precio', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('precio', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('nombre', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('nombre', 'desc');
                break;
            default:
                $query->orderBy('nombre', 'asc');
                break;
        }

        $productos = $query->get(); // o paginate si prefieres
        $categorias = Categoria::all(); // 👈 esto es lo que necesitas
        $allIngredients = Ingrediente::orderBy('nombre')->get(['id', 'nombre']);

        return view('menu.index', compact('productos', 'categorias', 'allIngredients'));
    }


    public function show($id)
    {
        $producto = Producto::find($id);
        return response()->json($producto);
    }

    public function modal($id)
    {
        // Traer el producto con sus ingredientes
        $producto = Producto::with('ingredientes')->findOrFail($id);

        // Calcular número de rolls (bloques de 10 piezas)
        foreach ($producto->ingredientes as $ing) {
            $cantidad = $ing->pivot->cantidad_permitida ?? 0;
            $ing->pivot->rolls = intdiv($cantidad, 10);
        }

        // Traer todos los ingredientes para permitir el swap
        $allIngredients = \App\Models\Ingrediente::orderBy('nombre')
            ->get(['id', 'nombre']);

        // Pasar ambos a la vista
        return view('components.modal-producto', compact('producto', 'allIngredients'));
    }
}
