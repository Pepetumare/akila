<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class OrderAccessService
{
    public function authorize(Request $request, Order $order, bool $return = false): void
    {
        if ($order->user_id !== null) {
            abort_unless($request->user() && (int) $request->user()->id === (int) $order->user_id, 403);

            return;
        }
        $signed = $return
            ? $request->hasValidSignatureWhileIgnoring(['collection_id', 'collection_status', 'payment_id', 'status', 'payment_status', 'external_reference', 'payment_type', 'merchant_order_id', 'preference_id', 'site_id', 'processing_mode', 'merchant_account_id'])
            : $request->hasValidSignature();
        abort_unless($signed || in_array((int) $order->id, $request->session()->get('guest_orders', []), true), 403);
    }

    public function downloadUrl(Order $order): string
    {
        return $order->user_id !== null ? route('checkout.download', $order)
            : URL::temporarySignedRoute('checkout.download', now()->addHours(24), ['order' => $order->id]);
    }
}
