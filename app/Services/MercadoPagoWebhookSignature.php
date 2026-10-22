<?php

namespace App\Services;

use Illuminate\Http\Request;

class MercadoPagoWebhookSignature
{
    public function validate(Request $request): string
    {
        $secret = config('services.mercadopago.webhook_secret');
        abort_unless(is_string($secret) && $secret !== '', 503, 'Webhook no configurado.');
        // PHP normalizes dotted query keys to underscores.
        $id = $request->query('data_id', $request->query('data.id'));
        abort_unless(is_scalar($id) && ctype_digit((string) $id) && strlen((string) $id) <= 18, 400);
        $bodyId = $request->input('data.id');
        abort_if($bodyId !== null && (string) $bodyId !== (string) $id, 400);
        $parts = [];
        foreach (explode(',', (string) $request->header('x-signature')) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }
        $requestId = $request->header('x-request-id');
        $ts = $parts['ts'] ?? '';
        $signature = $parts['v1'] ?? '';
        abort_unless($requestId && ctype_digit($ts) && preg_match('/^[a-f0-9]{64}$/i', $signature), 401);
        $seconds = strlen($ts) > 10 ? (int) floor((float) $ts / 1000) : (int) $ts;
        abort_if(abs(time() - $seconds) > 600, 401);
        $manifest = "id:{$id};request-id:{$requestId};ts:{$ts};";
        abort_unless(hash_equals(hash_hmac('sha256', $manifest, $secret), strtolower($signature)), 401);

        return (string) $id;
    }
}
