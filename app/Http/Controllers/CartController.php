<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Ingrediente;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /* ========================
     * 1. Mostrar carrito
     * ======================*/
    public function index()
    {
        $cart  = session('cart', []);
        $total = array_sum(array_column($cart, 'total'));

        return view('cart.index', compact('cart', 'total'));
    }

    /* ========================
     * 2. Añadir / combinar
     * ======================*/
    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id'       => 'required|exists:productos,id',
            'unidades'         => 'required|integer|min:1',
            'base_id'          => 'required|exists:ingredientes,id',
            'Proteínas'        => 'nullable|array',
            'Proteínas.*'      => 'exists:ingredientes,id',
            'vegetales'        => 'nullable|array',
            'vegetales.*'      => 'exists:ingredientes,id',
            'cream_cheese'     => 'sometimes|boolean',
            'scallions'        => 'sometimes|boolean',
            'price_adjustment' => 'nullable|integer|min:0',
        ]);

        /* ==== Construir item ==== */
        $producto         = Producto::findOrFail($data['product_id']);
        $priceUnit        = $producto->precio + ($data['price_adjustment'] ?? 0);
        $descripcion      = $this->buildDescription($data);
        $hash             = $this->makeHash($data + ['product_id'=>$producto->id]);

        $cart = session('cart', []);

        if (isset($cart[$hash])) {
            // mismo combo → sólo sumamos unidades
            $cart[$hash]['unidades'] += $data['unidades'];
            $cart[$hash]['total']     = $cart[$hash]['unidades'] * $priceUnit;
        } else {
            // nuevo ítem
            $cart[$hash] = [
                'hash'        => $hash,
                'producto_id' => $producto->id,
                'nombre'      => $producto->nombre,
                'unidades'    => $data['unidades'],
                'precio_unit' => $priceUnit,
                'total'       => $priceUnit * $data['unidades'],
                'detalle'     => $descripcion,
            ];
        }

        session(['cart' => $cart]);

        // al final del método add()
return redirect()->route('cart.index')
                 ->with('success', 'Producto agregado al carrito');

    }

    /* ========================
     * 3. Cambiar unidades
     * ======================*/
    public function update(Request $request)
    {
        $request->validate([
            'hash'     => 'required|string',
            'unidades' => 'required|integer|min:1',
        ]);

        $cart = session('cart', []);
        if (! isset($cart[$request->hash])) {
            return back()->withErrors('Ítem no encontrado');
        }

        $cart[$request->hash]['unidades'] = $request->unidades;
        $cart[$request->hash]['total']    =
            $cart[$request->hash]['unidades'] * $cart[$request->hash]['precio_unit'];

        session(['cart' => $cart]);

        return back();
    }

    /* ========================
     * 4. Eliminar ítem
     * ======================*/
    public function remove(Request $request)
    {
        $request->validate(['hash' => 'required|string']);

        $cart = session('cart', []);
        unset($cart[$request->hash]);
        session(['cart' => $cart]);

        return back();
    }

    /* ========================
     * 5. Vaciar carrito
     * ======================*/
    public function clear()
    {
        session()->forget('cart');
        return back();
    }

    /* =================================================
     * Helpers
     * ===============================================*/

    /** Crea un hash determinista basado en la personalización */
    private function makeHash(array $data): string
    {
        return hash('sha256', json_encode([
            $data['product_id'],
            $data['base_id'],
            collect($data['Proteínas'] ?? [])->sort()->values(),
            collect($data['vegetales'] ?? [])->sort()->values(),
            $data['cream_cheese'] ?? 0,
            $data['scallions'] ?? 0,
            $data['price_adjustment'] ?? 0,
        ]));
    }

    /** Devuelve arreglo descriptivo para la vista */
    private function buildDescription(array $data): array
    {
        return [
            'Base'      => Ingrediente::find($data['base_id'])->nombre,
            'Proteínas' => Ingrediente::findMany($data['Proteínas'] ?? [])->pluck('nombre'),
            'Vegetales' => Ingrediente::findMany($data['vegetales'] ?? [])->pluck('nombre'),
            'Sin queso' => !empty($data['cream_cheese']),
            'Sin cebollín' => !empty($data['scallions']),
        ];
    }
}
