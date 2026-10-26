<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Ingrediente;
use App\Models\Producto;
use App\Services\MenuConfigurationService;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    // Método principal para mostrar productos filtrados y ordenados
    public function index(Request $request)
    {
        $query = Producto::disponible();

        if ($request->filled('q') && is_string($request->input('q'))) {
            $query->where('nombre', 'like', '%'.mb_substr(trim($request->input('q')), 0, 100).'%');
        }

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
        $productos = $query->with(['categoria', 'ingredientes', 'gruposOpciones.opciones'])
            ->withCount('bloques')->withMin('bloques', 'cantidad_piezas')->withMax('bloques', 'cantidad_piezas')
            ->paginate(12)->withQueryString();

        // The modal loads one product on demand; do not hydrate all menu configurations.
        $categorias = Categoria::where('activo', true)
            ->whereHas('productos', fn ($q) => $q->where('activo', true))
            ->withCount(['productos as disponibles_count' => fn ($q) => $q->where('activo', true)])
            ->orderBy('orden')->orderBy('id')->get();

        return view('menu.index', compact('productos', 'categorias'));
    }

    // Retorna datos específicos de un producto en formato JSON
    public function show($id)
    {
        $producto = Producto::disponible()->with('ingredientes', 'swappables')->findOrFail($id);

        return response()->json($producto);
    }

    // Renderiza un modal con detalles del producto y permite personalización
    public function modal($id, MenuConfigurationService $menu)
    {
        $producto = Producto::with(MenuConfigurationService::RELATIONS)->findOrFail($id);
        abort_unless($producto->activo && $producto->categoria?->activo, 404);
        if ($menu->supports($producto)) {
            $menuConfig = $menu->schema($producto);

            return response()->view('components.menu-product-modal', compact('producto', 'menuConfig'))->header('Cache-Control', 'no-store');
        }

        $producto->loadMissing('swappables');
        $allIngredients = Ingrediente::orderBy('nombre')->get(['id', 'nombre', 'tipo']);

        return response()->view('components.modal-producto', compact('producto', 'allIngredients'))->header('Cache-Control', 'no-store');
    }
}
