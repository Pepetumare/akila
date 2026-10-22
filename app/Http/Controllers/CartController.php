<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\ProductPricingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index()
    {
        $cart = session('cart', []);
        $total = array_sum(array_column($cart, 'total'));

        return view('cart.index', compact('cart', 'total'));
    }

    public function quote(Request $request, ProductPricingService $pricing)
    {
        $data = $request->validate(['product_id' => 'required|integer|exists:productos,id', 'unidades' => 'sometimes|integer|min:1|max:50']);

        return response()->json(['valid' => true] + $pricing->quote(Producto::findOrFail($data['product_id']), $request->all(), (int) ($data['unidades'] ?? 1)))->header('Cache-Control', 'no-store');
    }

    public function add(Request $request, ProductPricingService $pricing)
    {
        $data = $request->validate(['product_id' => 'required|integer|exists:productos,id', 'unidades' => 'required|integer|min:1|max:50']);
        $product = Producto::findOrFail($data['product_id']);
        $line = $pricing->quote($product, $request->all(), (int) $data['unidades']);
        $cart = session('cart', []);
        if (isset($cart[$line['hash']])) {
            $line = $pricing->quote($product, $line['configuration'], $line['unidades'] + $cart[$line['hash']]['unidades']);
        }
        if (count($cart) >= 100 && ! isset($cart[$line['hash']])) {
            throw ValidationException::withMessages(['cart' => 'Máximo 100 configuraciones por carrito.']);
        }
        $cart[$line['hash']] = $line;
        session(['cart' => $cart]);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'cart_count' => array_sum(array_column($cart, 'unidades'))]);
        }

        return redirect()->route('cart.index')->with('success', 'Producto agregado al carrito.');
    }

    public function update(Request $request, ProductPricingService $pricing)
    {
        $data = $request->validate(['hash' => 'required|string', 'unidades' => 'required|integer|min:1|max:50']);
        $cart = session('cart', []);
        abort_unless(isset($cart[$data['hash']]), 404);
        $cart[$data['hash']]['unidades'] = (int) $data['unidades'];
        session(['cart' => $pricing->refreshCart($cart)]);

        return back();
    }

    public function remove(Request $request)
    {
        $data = $request->validate(['hash' => 'required|string']);
        $cart = session('cart', []);
        unset($cart[$data['hash']]);
        session(['cart' => $cart]);

        return back();
    }

    public function clear()
    {
        session()->forget('cart');

        return back();
    }
}
