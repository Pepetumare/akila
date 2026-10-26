<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Producto;
use App\Services\CheckoutSessionService;
use App\Services\MercadoPagoService;
use App\Services\OrderAccessService;
use App\Services\OrderInvoiceService;
use App\Services\OrderItemSnapshotService;
use App\Services\ProductPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    /* =========================================================
     * 1. Mostrar resumen de compra
     * =======================================================*/
    public function index(Request $request, ProductPricingService $pricing)
    {
        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        try {
            $fresh = $pricing->refreshCart($cart);
        } catch (ValidationException $e) {
            return redirect()->route('cart.index')->withErrors($e->errors());
        }
        if ($this->pricesChanged($cart, $fresh)) {
            $request->session()->put('cart', $fresh);

            return redirect()->route('cart.index')->withErrors('Los precios cambiaron. Revisa el carrito antes de confirmar.');
        }
        $cart = $fresh;
        $request->session()->put('cart', $cart);

        // ahora pasamos subtotal y cart a la vista
        $subtotal = collect($cart)->sum('total');

        return view('checkout.index', compact('cart', 'subtotal'));
    }

    /* =========================================================
     * 2. Guardar pedido, generar PDF y redirigir a pago
     * =======================================================*/
    public function store(Request $request, MercadoPagoService $mp, ProductPricingService $pricing)
    {
        $data = $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            'cliente_telefono' => 'required|string|max:20',
            'cliente_direccion' => 'nullable|string|max:255|required_if:metodo_entrega,delivery',
            'cliente_comentarios' => 'nullable|string|max:2000',
            'metodo_entrega' => 'required|in:pickup,delivery',
            'zona_delivery' => 'nullable|string|in:dentro,fuera|required_if:metodo_entrega,delivery',
            'kms_fuera' => 'nullable|integer|min:1|max:100',
        ]);

        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        try {
            $fresh = $pricing->refreshCart($cart);
        } catch (ValidationException $e) {
            return redirect()->route('cart.index')->withErrors($e->errors());
        }
        $request->session()->put('cart', $fresh);
        if ($this->pricesChanged($cart, $fresh)) {
            return redirect()->route('cart.index')->withErrors('Los precios cambiaron. Revisa el carrito antes de confirmar.');
        }
        $cart = $fresh;

        $subtotal = collect($cart)->sum(fn ($item) => (float) ($item['total'] ?? 0));
        $deliveryCost = $this->calculateDeliveryCost(
            $data['metodo_entrega'],
            $data['zona_delivery'] ?? null
        );
        $total = $subtotal + $deliveryCost;

        $kmsFuera = $data['metodo_entrega'] === 'delivery' && ($data['zona_delivery'] ?? null) === 'fuera' && isset($data['kms_fuera'])
            ? (int) $data['kms_fuera']
            : null;

        $fingerprint = hash('sha256', json_encode([$data, $cart, auth()->id()]));
        $previousId = $request->session()->get('checkout_attempts.'.$fingerprint);
        $order = $previousId ? Order::find($previousId) : null;
        if ($order && in_array($order->payment_status, ['refunded', 'charged_back'], true)) {
            $order = null;
        }
        if ($order && $order->payment_status === 'approved') {
            return redirect()->route('checkout.thankyou', $order);
        }
        $order ??= DB::transaction(function () use ($data, $cart, $subtotal, $deliveryCost, $total, $kmsFuera) {
            $order = Order::create([
                'cliente_nombre' => $data['cliente_nombre'],
                'cliente_telefono' => $data['cliente_telefono'],
                'cliente_direccion' => $data['metodo_entrega'] === 'delivery'
                    ? $data['cliente_direccion']
                    : null,
                'cliente_comentarios' => $data['cliente_comentarios'] ?? null,
                'metodo_entrega' => $data['metodo_entrega'],
                'zona_delivery' => $data['metodo_entrega'] === 'delivery'
                    ? ($data['zona_delivery'] ?? null)
                    : null,
                'kms_fuera' => $kmsFuera,
                'subtotal' => $subtotal,
                'delivery_cost' => $deliveryCost,
                'total' => $total,
                'status' => 'pendiente',
                'payment_status' => 'pending',
            ]);

            $products = Producto::whereIn('id', array_column($cart, 'producto_id'))->get()->keyBy('id');
            foreach ($cart as $item) {
                $order->items()->create([
                    'product_id' => $item['producto_id'],
                    'nombre' => $item['nombre'],
                    'unidades' => $item['unidades'],
                    'precio_unit' => (float) $item['precio_unit'],
                    'total' => (float) $item['total'],
                    'detalle' => $item['detalle'] ?? [],
                    'snapshot' => app(OrderItemSnapshotService::class)->fromQuote($products->get($item['producto_id']), $item),
                ]);
            }

            if (auth()->check()) {
                $order->user()->associate(auth()->user());
                $order->save();
            }

            return $order->fresh('items');
        });

        $request->session()->put('checkout_attempts.'.$fingerprint, $order->id);
        $request->session()->put('checkout_cart.'.$order->id, hash('sha256', json_encode($cart)));
        if (! $order->user_id) {
            $ids = $request->session()->get('guest_orders', []);
            $ids[] = (int) $order->id;
            $request->session()->put('guest_orders', array_values(array_unique($ids)));
        }

        // Redirigir a Mercado Pago
        try {
            $existingPreference = $request->session()->get('checkout_preferences.'.$order->id);
            if ($existingPreference && $existingPreference['expires'] > time()) {
                return redirect()->away($existingPreference['url']);
            }
            $initPoint = $mp->createPreference($order);
            $request->session()->put('checkout_preferences.'.$order->id, ['url' => $initPoint, 'expires' => time() + 86400]);

            return redirect()->away($initPoint);
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago preference failed', ['order_id' => $order->id, 'exception' => $e::class]);

            return back()->withErrors('No pudimos conectar con Mercado Pago. Intenta nuevamente.');
        }
    }

    private function calculateDeliveryCost(string $method, ?string $zone): float
    {
        if ($method !== 'delivery') {
            return 0.0;
        }

        if ($zone === 'fuera') {
            throw ValidationException::withMessages([
                'zona_delivery' => 'El delivery fuera de San José requiere cotización manual. Coordina la entrega antes de pagar.',
            ]);
        }

        return (float) config('delivery.local_price');
    }

    /* =========================================================
     * 3. Página de agradecimiento
     * =======================================================*/
    public function thankYou(Request $request, Order $order, OrderAccessService $access)
    {
        $access->authorize($request, $order);
        app(CheckoutSessionService::class)->complete($request, $order);
        $downloadUrl = $access->downloadUrl($order);

        return response()->view('checkout.thankyou', compact('order', 'downloadUrl'))->header('Cache-Control', 'private, no-store');
    }

    /* =========================================================
     * 4. Descargar boleta PDF
     * =======================================================*/
    public function download(Request $request, Order $order, OrderAccessService $access, OrderInvoiceService $invoices)
    {
        $access->authorize($request, $order);

        return $invoices->download($order);
    }

    private function pricesChanged(array $old, array $fresh): bool
    {
        foreach ($fresh as $hash => $line) {
            if (! isset($old[$hash]) || (int) round((float) $old[$hash]['precio_unit'] * 100) !== (int) round($line['precio_unit'] * 100)) {
                return true;
            }
        }

        return false;
    }
}
