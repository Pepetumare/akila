<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class MercadoPagoController extends Controller
{
    /**
     * Callback cuando el pago fue aprobado (auto_return=approved).
     * URL: /checkout/success?status=approved&payment_id=...&external_reference=ORDER_ID
     */
    public function success(Request $request)
    {
        $orderId    = $request->query('external_reference');
        $status     = $request->query('status');
        $paymentId  = $request->query('payment_id');
        $order      = Order::findOrFail($orderId);

        // Solo marcamos pagado si MP confirmó approved
        if ($status === 'approved') {
            $order->update([
                'status'         => 'pagado',
                'mp_payment_id'  => $paymentId,
            ]);
            // Limpiamos el carrito de la sesión
            $request->session()->forget('cart');
        } else {
            // otros posibles estados: rejected, in_process, etc.
            $order->update(['status' => $status]);
        }

        return view('checkout.thankyou', compact('order'));
    }

    /**
     * Callback si el usuario no completa el pago o MP lo rechaza.
     * URL: /checkout/failure?status=rejected&payment_id=...&external_reference=ORDER_ID
     */
    public function failure(Request $request)
    {
        $orderId = $request->query('external_reference');
        $order   = Order::findOrFail($orderId);
        $order->update(['status' => 'rechazado']);

        return view('checkout.error', compact('order'));
    }

    /**
     * Callback para pagos pendientes.
     * URL: /checkout/pending?status=pending&payment_id=...&external_reference=ORDER_ID
     */
    public function pending(Request $request)
    {
        $orderId = $request->query('external_reference');
        $order   = Order::findOrFail($orderId);
        $order->update(['status' => 'pendiente_mp']);

        return view('checkout.pending', compact('order'));
    }

    /**
     * Webhook para notificaciones de Mercado Pago.
     * Aquí puedes verificar el payment_id o topic y actualizar el Order.
     */
    public function webhook(Request $request)
    {
        // Ejemplo muy básico: sólo respondemos 200
        // Para producción deberías:
        // 1) Verificar $request->input('type') === 'payment'
        // 2) Recuperar el payment_id: $request->input('data.id')
        // 3) Consultar la API de MP y actualizar tu Order según payment_status
        return response()->json(['received' => true], 200);
    }
}
