<?php

namespace App\Services;

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use App\Models\Order;

class MercadoPagoService
{
    protected PreferenceClient $client;

    public function __construct()
    {
        // 1) Autenticación con tu token de .env
        MercadoPagoConfig::setAccessToken(
            config('services.mercadopago.access_token')
        );

        // 2) Para pruebas locales, desactiva la verificación SSL
        //    (usa HTTP interno o skip de SSL)
        if (app()->environment('local')) {
            MercadoPagoConfig::setRuntimeEnvironment(
                MercadoPagoConfig::LOCAL
            );  // :contentReference[oaicite:0]{index=0}
        }

        // 3) Instancia el client
        $this->client = new PreferenceClient();
    }

    /**
     * Crea la preferencia y obtiene la URL de checkout.
     */
    public function createPreference(Order $order): string
    {
        $order->loadMissing('items');

        $items = $order->items->map(function ($item) {
            return [
                'id'          => (string) $item->product_id,
                'title'       => $item->nombre,
                'quantity'    => (int) $item->unidades,
                'unit_price'  => (float) $item->precio_unit,
                'currency_id' => 'CLP',
            ];
        })->values()->all();

        if (empty($items)) {
            throw new \RuntimeException('No se pudieron generar los items para Mercado Pago.');
        }

        $payload = [
            'items'              => $items,
            'external_reference' => (string) $order->id,
            'payer'              => [
                'name'  => $order->cliente_nombre,
                'phone' => ['number' => $order->cliente_telefono],
            ],
            'back_urls'      => [
                'success' => route('checkout.success'),
                'failure' => route('checkout.failure'),
                'pending' => route('checkout.pending'),
            ],
            'auto_return'      => 'approved',
            'notification_url' => route('checkout.webhook'),
            'statement_descriptor' => 'Sushi Akila',
        ];

        if ($order->metodo_entrega === 'delivery') {
            $payload['shipments'] = [
                'receiver_address' => array_filter([
                    'street_name' => $order->cliente_direccion,
                    'zip_code'    => '0',
                    'city_name'   => 'San José',
                    'state_name'  => 'Costa Rica',
                ], fn ($value) => !is_null($value) && $value !== ''),
                'cost' => (float) $order->delivery_cost,
                'mode' => 'not_specified',
            ];
        }

        $preference = $this->client->create($payload);

        return app()->environment('production')
            ? $preference->init_point
            : $preference->sandbox_init_point;
    }
}
