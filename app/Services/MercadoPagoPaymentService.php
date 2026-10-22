<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MercadoPagoPaymentService
{
    public function __construct(private MercadoPagoService $gateway) {}

    public function synchronize(string $paymentId, ?Order $expected = null): Order
    {
        $this->valid(ctype_digit($paymentId) && strlen($paymentId) <= 18 && (int) $paymentId > 0, 'invalid_payment_id');
        $payment = $this->gateway->getPayment($paymentId);
        $this->valid((string) ($payment['id'] ?? '') === $paymentId, 'payment_id_mismatch');
        $reference = (string) ($payment['external_reference'] ?? '');
        $this->valid(ctype_digit($reference) && $reference !== '', 'invalid_reference');
        $this->valid(! $expected || (string) $expected->id === $reference, 'reference_mismatch');
        $order = Order::find($reference);
        $this->valid($order !== null, 'unknown_order');

        return DB::transaction(function () use ($order, $payment, $paymentId) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->valid(($payment['currency_id'] ?? null) === 'CLP', 'currency_mismatch');
            $amount = $payment['transaction_amount'] ?? null;
            $this->valid(is_scalar($amount) && preg_match('/^[0-9]{1,10}(\.[0-9]{1,2})?$/', (string) $amount) === 1
                && (int) round((float) $amount * 100) === (int) round((float) $order->total * 100), 'amount_mismatch');
            $status = $payment['status'] ?? null;
            $this->valid(in_array($status, ['pending', 'in_process', 'authorized', 'approved', 'rejected', 'cancelled', 'refunded', 'charged_back'], true), 'invalid_status');
            $this->valid(! empty($payment['date_last_updated']), 'missing_payment_date');
            try {
                $updated = CarbonImmutable::parse($payment['date_last_updated'])->utc()->startOfSecond();
            } catch (\Throwable) {
                $this->valid(false, 'invalid_payment_date');
            }
            $samePayment = (string) $order->mp_payment_id === $paymentId;
            if ($samePayment && $order->payment_updated_at && $updated->lessThan($order->payment_updated_at)) {
                return $order;
            }
            // Successful/terminal payments cannot be downgraded by another attempt or a stale return.
            if (in_array($order->payment_status, ['approved', 'refunded', 'charged_back'], true)) {
                if (! $samePayment || ! in_array($status, ['approved', 'refunded', 'charged_back'], true)) {
                    return $order;
                }
                if (in_array($order->payment_status, ['refunded', 'charged_back'], true) && $status === 'approved') {
                    return $order;
                }
            }
            if ($order->payment_status === null && $order->status === 'pagado' && ! in_array($status, ['approved', 'refunded', 'charged_back'], true)) {
                return $order;
            }
            if ($samePayment && $order->payment_status === $status && $order->payment_updated_at?->equalTo($updated)) {
                return $order;
            }
            $order->forceFill(['payment_status' => $status, 'mp_payment_id' => $paymentId, 'payment_updated_at' => $updated])->save();
            Log::info('Mercado Pago payment verified', ['order_id' => $order->id, 'payment_id' => $paymentId, 'payment_status' => $status]);

            return $order;
        });
    }

    private function valid(bool $valid, string $reason): void
    {
        if (! $valid) {
            Log::warning('Mercado Pago verification rejected', ['reason' => $reason]);
            throw ValidationException::withMessages(['payment' => 'No se pudo verificar el pago.']);
        }
    }
}
