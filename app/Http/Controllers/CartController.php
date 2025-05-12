<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CartController extends Controller
{
    // Muestra el contenido del carrito
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        return view('cart.index', compact('cart'));
    }

    // Añade un producto personalizado al carrito
    public function add(Request $request)
    {
        // Incluimos 'unidades' en la validación
        $data = $request->validate([
            'product_id'    => 'required|integer',
            'unidades'      => 'required|integer|min:1',
            'removed_bases' => 'nullable|array',
            'extras'        => 'nullable|array',
            'price'         => 'required|numeric',
        ]);

        $cart = $request->session()->get('cart', []);

        // Generar un identificador único para este ítem
        $itemId = uniqid();

        // Guardamos 'unidades' junto al resto de datos
        $cart[$itemId] = [
            'product_id'    => $data['product_id'],
            'unidades'      => $data['unidades'],
            'removed_bases' => $data['removed_bases'] ?? [],
            'extras'        => $data['extras'] ?? [],
            'price'         => $data['price'],
        ];

        $request->session()->put('cart', $cart);

        return response()->json([
            'success'    => true,
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
