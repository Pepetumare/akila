<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Muestra el contenido del carrito.
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        $total = array_reduce($cart, fn($sum, $item) => $sum + ($item['total_price'] ?? 0), 0);

        return view('cart.index', compact('cart', 'total'));
    }

    /**
     * Añade un producto personalizado al carrito.
     */
    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id'               => 'required|exists:productos,id',
            'unidades'                 => 'required|integer|min:1',
            'personalization'          => 'nullable|array',
            'personalization.rolls'    => 'nullable|array',
            'personalization.recargo'  => 'nullable|integer|min:0',
            'price'                    => 'required|integer|min:0',
        ]);

        $producto = Producto::findOrFail($data['product_id']);

        // Construir el ítem a guardar
        $item = [
            'id'          => $producto->id,
            'nombre'      => $producto->nombre,
            'unidades'    => $data['unidades'],
            'base_price'  => $producto->precio,
            'total_price' => $data['price'],  // ya incluye recargo
        ];

        if (! empty($data['personalization'])) {
            $item['personalization'] = [
                'rolls'   => $data['personalization']['rolls']   ?? [],
                'recargo' => $data['personalization']['recargo'] ?? 0,
            ];
        }

        // Agregar al carrito en sesión
        $cart = session()->get('cart', []);
        $cart[] = $item;
        session()->put('cart', $cart);

        return response()->json([
            'success'    => true,
            'cart_count' => count($cart),
        ]);
    }

    /**
     * Elimina un ítem del carrito.
     */
    public function remove(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
        ]);

        $cart = session()->get('cart', []);
        if (isset($cart[$request->item_id])) {
            unset($cart[$request->item_id]);
            // Reindexar el arreglo
            session()->put('cart', array_values($cart));
        }

        return redirect()->route('cart.index');
    }

    /**
     * Vacía todo el carrito.
     */
    public function clear(Request $request)
    {
        $request->session()->forget('cart');
        return redirect()->route('cart.index');
    }
}
