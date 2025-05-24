<?php

namespace App\Services;

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use App\Models\Order;
use MercadoPago\Net\MPDefaultHttpClient;

class MercadoPagoService
{
    protected PreferenceClient $client;

    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
        $this->client = new PreferenceClient();

        MPDefaultHttpClient::$disableSSLVerification = true;
    }

    public function createPreference(Order $order): string
    {
        $items = $order->items->map(fn($it) => [
            'title'       => $it->nombre,
            'quantity'    => $it->unidades,
            'unit_price'  => (float) $it->precio_unit,
            'currency_id' => 'CLP',
        ])->all();

        if ($order->delivery_cost > 0) {
            $items[] = [
                'title'       => 'Envío',
                'quantity'    => 1,
                'unit_price'  => (float) $order->delivery_cost,
                'currency_id' => 'CLP',
            ];
        }

        $preference = $this->client->create([
            'items'              => $items,
            'external_reference' => (string) $order->id,
            'payer'              => [
                'name'  => $order->cliente_nombre,
                'phone' => ['number' => $order->cliente_telefono],
            ],
            'back_urls' => [
                'success' => route('checkout.success'),
                'failure' => route('checkout.failure'),
                'pending' => route('checkout.pending'),
            ],
            'auto_return'      => 'approved',
            'notification_url' => route('checkout.webhook'),
        ]);

        return app()->environment('production')
            ? $preference->init_point
            : $preference->sandbox_init_point;
    }
}
