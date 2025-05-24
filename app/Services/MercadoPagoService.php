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
        MercadoPagoConfig::setRuntimeEnviroment(
            MercadoPagoConfig::LOCAL
        );  // :contentReference[oaicite:0]{index=0}

        // 3) Instancia el client
        $this->client = new PreferenceClient();
    }

    /**
     * Crea la preferencia y obtiene la URL de checkout.
     */
    public function createPreference(Order $order): string
    {
        // ... tu código para armar $items, back_urls, etc. ...

        $preference = $this->client->create([
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
        ]);

        return app()->environment('production')
            ? $preference->init_point
            : $preference->sandbox_init_point;
    }
}
