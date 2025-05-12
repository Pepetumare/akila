<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Mostrar listado de pedidos.
     */
    public function index()
    {
        $orders = Order::latest()->paginate(20);
        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Ver detalle de un pedido.
     */
    public function show(Order $order)
    {
        // Carga relaciones necesarias
        $order->load('items.producto');
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Actualizar el estado del pedido.
     */
    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:pendiente,en preparación,entregado',
        ]);

        $order->update($data);

        return back()->with('success', 'Estado de pedido actualizado.');
    }
}
