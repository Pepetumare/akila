<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Muestra el contenido del carrito
    public function index()
    {
        $cart = session()->get('cart', []);
        // Calcula total general
        $total = array_reduce($cart, fn($sum, $item) => $sum + $item['total_price'], 0);
        return view('cart.index', compact('cart', 'total'));
    }

    // Añade un producto personalizado al carrito
    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id'    => 'required|exists:productos,id',
            'unidades'      => 'required|integer|min:1',
            'removed_bases' => 'nullable|array',
            'removed_bases.*.*' => 'string', // nombres de ingredientes
            'extras'        => 'nullable|array',
            'extras.*.*.ingredient' => 'required|integer|exists:ingredientes,id',
            'extras.*.*.price'      => 'required|numeric|min:0',
        ]);
    
        $producto = Producto::with('ingredientes')->findOrFail($data['product_id']);
    
        // Armamos un carrito temporal
        $item = [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'unidades' => $data['unidades'],
            'base_price' => $producto->precio,
            'removed_bases' => $data['removed_bases'] ?? [],
            'extras' => [], // vamos a validar y recalcular
        ];
    
        $totalExtras = 0;
        foreach ($data['extras'] as $unit => $extrasUnit) {
            foreach ($extrasUnit as $extra) {
                // Verificar límite pivot
                $pivot = $producto->ingredientes()
                                  ->where('ingrediente_id', $extra['ingredient'])
                                  ->first()
                                  ->pivot;
                $max = $pivot->cantidad_permitida ?? PHP_INT_MAX;
    
                // Contar cuántas han sido agregadas en esta unidad
                $countInUnit = collect($extrasUnit)
                    ->where('ingredient', $extra['ingredient'])
                    ->count();
    
                if ($countInUnit > $max) {
                    return back()
                        ->withErrors("El ingrediente {$pivot->ingrediente->nombre} no puede repetirse más de {$max} veces.")
                        ->withInput();
                }
    
                // Recalcular precio real desde DB, ignorando manipulación de cliente
                $ingrediente = $pivot->ingrediente;
                $priceReal = $ingrediente->precio;
                $item['extras'][$unit][] = [
                    'id' => $ingrediente->id,
                    'nombre' => $ingrediente->nombre,
                    'cantidad' => $countInUnit,
                    'price' => $priceReal,
                ];
                $totalExtras += $priceReal;
            }
        }
    
        // Precio final seguro
        $item['total_price'] = ($producto->precio + $totalExtras) * $data['unidades'];
    
        // Guardar en session (o en DB según tu implementación)
        $cart = session()->get('cart', []);
        $cart[] = $item;
        session()->put('cart', $cart);
    
        return response()->json([
            'success' => true,
            'cart_count' => count($cart),
        ]);
    }
    

    // Elimina un ítem del carrito
    public function remove(Request $request)
    {
        $request->validate(['item_id' => 'required|string']);

        $cart = $request->session()->get('cart', []);
        unset($cart[$request->item_id]);
        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index');
    }

    // Vacía todo el carrito
    public function clear(Request $request)
    {
        $request->session()->forget('cart');
        return redirect()->route('cart.index');
    }
}
