<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\URL;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoService
{
    private function configure(): void
    {
        $token = config('services.mercadopago.access_token');
        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('Mercado Pago no está configurado.');
        }
        MercadoPagoConfig::setAccessToken($token);
        // Keep the SDK's server runtime and TLS verification in every environment.
    }

    public function getPayment(string $id): array
    {
        $this->configure();
        $payment = (new PaymentClient)->get((int) $id);

        return [
            'id' => $payment->id, 'external_reference' => $payment->external_reference,
            'status' => $payment->status, 'transaction_amount' => $payment->transaction_amount,
            'currency_id' => $payment->currency_id, 'date_last_updated' => $payment->date_last_updated,
        ];
    }

    public function createPreference(Order $order): string
    {
        $this->configure();
        $order->loadMissing('items');
        $items = $order->items->map(fn ($item) => [
            'id' => (string) $item->product_id, 'title' => $item->nombre,
            'quantity' => (int) $item->unidades, 'unit_price' => (float) $item->precio_unit,
            'currency_id' => 'CLP',
        ])->values()->all();
        if ($items === []) {
            throw new \RuntimeException('Pedido sin productos.');
        }
        // Delivery is an explicit CLP item, so the verified amount includes it.
        if ((float) $order->delivery_cost > 0) {
            $items[] = ['id' => 'delivery', 'title' => 'Delivery', 'quantity' => 1,
                'unit_price' => (float) $order->delivery_cost, 'currency_id' => 'CLP'];
        }
        $backUrls = [];
        foreach (['success', 'failure', 'pending'] as $name) {
            $backUrls[$name] = URL::temporarySignedRoute('checkout.'.$name, now()->addDays(2), ['order' => $order->id]);
        }
        $payload = [
            'items' => $items, 'external_reference' => (string) $order->id,
            'payer' => ['name' => $order->cliente_nombre, 'phone' => ['number' => $order->cliente_telefono]],
            'back_urls' => $backUrls, 'auto_return' => 'approved',
            'notification_url' => route('checkout.webhook'), 'statement_descriptor' => 'Sushi Akila',
            'expires' => true,
            'expiration_date_to' => now()->addDay()->toIso8601String(),
        ];
        // No fabricated postcode, city or province. CLP identifies the Chilean checkout;
        // the actual delivery address remains on the order for restaurant fulfillment.
        $preference = (new PreferenceClient)->create($payload);
        $url = app()->environment('production') ? $preference->init_point : $preference->sandbox_init_point;
        if (! is_string($url) || ! str_starts_with($url, 'https://')) {
            throw new \RuntimeException('URL de pago inválida.');
        }

        return $url;
    }
}
