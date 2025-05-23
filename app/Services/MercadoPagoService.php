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
        // 1) Autenticación una sola vez
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

        // 2) Instancia del client
        $this->client = new PreferenceClient();
    }

    /**
     * Crea la preferencia y devuelve la URL de Checkout Pro.
     */
    public function createPreference(Order $order): string
    {
        // ---- Ítems en formato array ----
        $items = $order->items->map(fn ($it) => [
            'title'       => $it->nombre,
            'quantity'    => $it->unidades,
            'unit_price'  => (float) $it->precio_unit,
            'currency_id' => 'CLP',
        ])->all();

        if ($order->envio > 0) {
            $items[] = [
                'title'       => 'Envío',
                'quantity'    => 1,
                'unit_price'  => (float) $order->envio,
                'currency_id' => 'CLP',
            ];
        }

        // ---- Crea la preferencia ----
        $preference = $this->client->create([
            'items'              => $items,
            'external_reference' => (string) $order->id,
            'payer'              => [
                'name'  => $order->cliente_nombre,
                'phone' => ['number' => $order->cliente_telefono],
            ],
            'back_urls' => [
                'success' => route('mp.success',  $order->id),
                'failure' => route('mp.failure',  $order->id),
                'pending' => route('mp.pending',  $order->id),
            ],
            'auto_return'      => 'approved',
            'notification_url' => route('mp.webhook'),
        ]);

        // En sandbox usa ->sandbox_init_point; en producción ->init_point
        return $preference->sandbox_init_point;
    }
}
