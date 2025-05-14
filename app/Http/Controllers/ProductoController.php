<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
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
    
        return view('menu.index', compact('productos', 'categorias'));
    }
    

    public function show($id)
    {
        $producto = Producto::find($id);
        return response()->json($producto);
    }

    public function modal($id)
    {
        // Cargar producto con sus ingredientes y pivote
        $producto = Producto::with('ingredientes')->findOrFail($id);
        return view('partials.modal-producto', compact('producto'));
    }
}
