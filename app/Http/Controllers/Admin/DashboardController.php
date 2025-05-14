<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Ingrediente;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Totales básicos
        $totalOrders     = Order::count();
        $totalCategories = Categoria::count();
        $totalIngredients= Ingrediente::count();
        $totalProducts   = Producto::count();

        // Ventas del día
        $today           = now()->toDateString();
        $salesToday      = Order::whereDate('created_at', $today)->sum('total');

        // Pedidos por estado
        $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count','status');  // colección [ 'pendiente' => 5, 'entregado' => 10, … ]

        // Top 5 productos por ingresos
        $topProducts = OrderItem::select('product_id', DB::raw('SUM(subtotal) as revenue'))
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->with('producto')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalOrders','totalCategories','totalIngredients','totalProducts',
            'salesToday','ordersByStatus','topProducts'
        ));
    }
}