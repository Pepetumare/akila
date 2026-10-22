<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CheckoutSessionService;
use App\Services\MercadoPagoPaymentService;
use App\Services\MercadoPagoWebhookSignature;
use App\Services\OrderAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MercadoPagoController extends Controller
{
    public function success(Request $request, MercadoPagoPaymentService $payments, OrderAccessService $access)
    {
        return $this->paymentReturn($request, $payments, $access);
    }

    public function failure(Request $request, MercadoPagoPaymentService $payments, OrderAccessService $access)
    {
        return $this->paymentReturn($request, $payments, $access);
    }

    public function pending(Request $request, MercadoPagoPaymentService $payments, OrderAccessService $access)
    {
        return $this->paymentReturn($request, $payments, $access);
    }

    private function paymentReturn(Request $request, MercadoPagoPaymentService $payments, OrderAccessService $access)
    {
        $data = $request->validate(['order' => 'nullable|integer|min:1', 'external_reference' => 'nullable|integer|min:1', 'payment_id' => 'nullable|string|regex:/^[1-9][0-9]{0,17}$/']);
        // The external reference is only a locator, never evidence of payment/access.
        $order = Order::findOrFail($data['order'] ?? $data['external_reference'] ?? 0);
        $access->authorize($request, $order, true);
        $verificationError = null;
        if (! empty($data['payment_id'])) {
            try {
                $order = $payments->synchronize($data['payment_id'], $order);
            } catch (\Throwable $e) {
                Log::warning('Mercado Pago return could not be verified', ['order_id' => $order->id, 'exception' => $e::class]);
                $verificationError = 'Aún no pudimos verificar el pago. No vuelvas a pagar si ya se realizó el cobro.';
            }
        }
        app(CheckoutSessionService::class)->complete($request, $order);
        $downloadUrl = $access->downloadUrl($order);

        return response()->view('checkout.thankyou', compact('order', 'downloadUrl', 'verificationError'))->header('Cache-Control', 'private, no-store');
    }

    public function webhook(Request $request, MercadoPagoWebhookSignature $signature, MercadoPagoPaymentService $payments)
    {
        $id = $signature->validate($request);
        if ($request->input('type') !== 'payment') {
            return response()->json(['received' => true, 'ignored' => true]);
        }
        try {
            $payments->synchronize($id);
        } catch (ValidationException) {
            return response()->json(['error' => 'invalid_payment'], 422);
        } catch (\Throwable $e) {
            Log::error('Mercado Pago webhook temporarily unavailable', ['payment_id' => $id, 'exception' => $e::class]);

            return response()->json(['error' => 'verification_unavailable'], 503);
        }

        return response()->json(['received' => true]);
    }
}
