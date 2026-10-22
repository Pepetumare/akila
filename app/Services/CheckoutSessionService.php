<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;

class CheckoutSessionService
{
    public function complete(Request $request, Order $order): void
    {
        if ($order->payment_status !== 'approved') {
            return;
        }
        $snapshot = $request->session()->get('checkout_cart.'.$order->id);
        if ($snapshot && hash_equals($snapshot, hash('sha256', json_encode($request->session()->get('cart', []))))) {
            $request->session()->forget('cart');
        }
        foreach ($request->session()->get('checkout_attempts', []) as $key => $id) {
            if ((int) $id === (int) $order->id) {
                $request->session()->forget('checkout_attempts.'.$key);
            }
        }
        $request->session()->forget(['checkout_cart.'.$order->id, 'checkout_preferences.'.$order->id]);
    }
}
