<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class MercadoPagoController extends Controller
{
    public function success(Order $order, Request $request)
    {
        $order->update(['status' => 'pagado']);

        // ✔️ ahora sí limpiamos el carrito
        $request->session()->forget('cart');

        return view('checkout.thankyou', compact('order'));
    }


    public function failure(Order $order)
    {
        $order->update(['status' => 'rechazado']);
        return view('checkout.error', compact('order'));
    }

    public function pending(Order $order)
    {
        $order->update(['status' => 'pendiente_mp']);
        return view('checkout.pending', compact('order'));
    }

    // Webhook para pagos offline / confirmaciones
    public function webhook(Request $request)
    {
        // Verifica topic = payment, etc. y actualiza Order según payment_status
        // Responde 200 a Mercado Pago
        return response()->json([], 200);
    }
}
