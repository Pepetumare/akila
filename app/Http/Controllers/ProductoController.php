<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Ingrediente;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    // Método principal para mostrar productos filtrados y ordenados
    public function index(Request $request)
    {
        $query = Producto::query();

        // Filtrar por categoría si se especifica
        if ($request->filled('filter')) {
            $query->whereHas('categoria', function ($q) use ($request) {
                $q->where('slug', $request->filter);
            });
        }

        // Aplicar filtros de ordenación si se especifican
        switch ($request->input('sort')) {
            case 'price_asc':
                $query->orderBy('precio', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('precio', 'desc');
                break;
            case 'name_desc':
                $query->orderBy('nombre', 'desc');
                break;
            case 'name_asc':
            default:
                $query->orderBy('nombre', 'asc');
                break;
        }

        // Cargar relaciones
        $productos = $query->with('ingredientes')->paginate(12)->withQueryString();

        // Cargar categorías e ingredientes para filtros y UI
        $categorias = Categoria::all();
        $allIngredients = Ingrediente::orderBy('nombre')->get();

        // Separar ingredientes por tipo para la interfaz de usuario
        $wrappers = $allIngredients->where('tipo', 'envoltura')->values();
        $proteins = $allIngredients->where('tipo', 'proteina')->values();
        $vegetables = $allIngredients->where('tipo', 'vegetal')->values();

        return view('menu.index', compact('productos', 'categorias', 'allIngredients', 'wrappers', 'proteins', 'vegetables'));
    }

    // Retorna datos específicos de un producto en formato JSON
    public function show($id)
    {
        $producto = Producto::with('ingredientes')->findOrFail($id);
        return response()->json($producto);
    }

    // Renderiza un modal con detalles del producto y permite personalización
    public function modal($id)
    {
        $producto = Producto::with('ingredientes')->findOrFail($id);

        // Calcular número de rolls (bloques de 10 piezas)
        foreach ($producto->ingredientes as $ing) {
            $cantidad = $ing->pivot->cantidad_permitida ?? 0;
            $ing->pivot->rolls = intdiv($cantidad, 10);
        }

        $allIngredients = Ingrediente::orderBy('nombre')->get(['id', 'nombre']);

        return view('components.modal-producto', compact('producto', 'allIngredients'));
    }
}