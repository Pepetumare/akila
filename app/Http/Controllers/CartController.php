<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Ingrediente;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart  = session('cart', []);
        $total = array_sum(array_column($cart, 'total'));

        return view('cart.index', compact('cart', 'total'));
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id'       => 'required|exists:productos,id',
            'unidades'         => 'required|integer|min:1',
            'base_id'          => 'nullable|exists:ingredientes,id',
            'Proteínas'        => 'nullable|array',
            'Proteínas.*'      => 'exists:ingredientes,id',
            'vegetales'        => 'nullable|array',
            'vegetales.*'      => 'exists:ingredientes,id',
            'cream_cheese'     => 'sometimes|boolean',
            'scallions'        => 'sometimes|boolean',
            'price_adjustment' => 'nullable|integer|min:0',
        ]);

        $producto    = Producto::findOrFail($data['product_id']);
        $priceUnit   = $producto->precio + ($data['price_adjustment'] ?? 0);
        $descripcion = $this->buildDescription($data);
        $hash        = $this->makeHash([
            'product_id'       => $data['product_id'],
            'base_id'          => $data['base_id'] ?? 0,
            'Proteinas'        => $data['Proteínas'] ?? [],
            'vegetales'        => $data['vegetales'] ?? [],
            'cream_cheese'     => $data['cream_cheese'] ?? false,
            'scallions'        => $data['scallions'] ?? false,
            'price_adjustment' => $data['price_adjustment'] ?? 0,
        ]);

        $cart = session('cart', []);

        if (isset($cart[$hash])) {
            $cart[$hash]['unidades'] += $data['unidades'];
            $cart[$hash]['total']     = $cart[$hash]['unidades'] * $priceUnit;
        } else {
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

        return redirect()->route('cart.index')->with('success', 'Producto agregado al carrito.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'hash'     => 'required|string',
            'unidades' => 'required|integer|min:1',
        ]);

        $cart = session('cart', []);
        if (!isset($cart[$request->hash])) {
            return back()->withErrors('Ítem no encontrado');
        }

        $cart[$request->hash]['unidades'] = $request->unidades;
        $cart[$request->hash]['total']    = $request->unidades * $cart[$request->hash]['precio_unit'];

        session(['cart' => $cart]);

        return back();
    }

    public function remove(Request $request)
    {
        $request->validate(['hash' => 'required|string']);

        $cart = session('cart', []);
        unset($cart[$request->hash]);
        session(['cart' => $cart]);

        return back();
    }

    public function clear()
    {
        session()->forget('cart');
        return back();
    }

    private function makeHash(array $data): string
    {
        return hash('sha256', json_encode([
            $data['product_id'],
            $data['base_id'] ?? 0,
            collect($data['Proteinas'] ?? [])->sort()->values(),
            collect($data['vegetales'] ?? [])->sort()->values(),
            $data['cream_cheese'] ?? 0,
            $data['scallions'] ?? 0,
            $data['price_adjustment'] ?? 0,
        ]));
    }

    private function formatearIngredientes(array $ids): array
    {
        $conteo = array_count_values($ids);

        return collect($conteo)->map(function ($cantidad, $id) {
            $nombre = optional(Ingrediente::find($id))->nombre ?? 'Desconocido';
            return ($cantidad > 1 ? "{$cantidad}x " : '') . $nombre;
        })->values()->toArray();
    }


    private function buildDescription(array $data): array
    {
        return [
            'Base'         => optional(Ingrediente::find($data['base_id'] ?? null))->nombre ?? 'Sin base',
            'Proteínas'    => $this->formatearIngredientes($data['Proteínas'] ?? []),
            'Vegetales'    => $this->formatearIngredientes($data['vegetales'] ?? []),
            'Sin queso'    => !empty($data['cream_cheese']),
            'Sin cebollín' => !empty($data['scallions']),
        ];
    }
}
