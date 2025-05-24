<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MercadoPagoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CheckoutController extends Controller
{
    /* =========================================================
     * 1. Mostrar resumen de compra
     * =======================================================*/
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        // ahora pasamos subtotal y cart a la vista
        $subtotal = collect($cart)->sum('total');
        return view('checkout.index', compact('cart', 'subtotal'));
    }

    /* =========================================================
     * 2. Guardar pedido, generar PDF y redirigir a pago
     * =======================================================*/
    public function store(Request $request, MercadoPagoService $mp)
    {
        $data = $request->validate([
            'cliente_nombre'      => 'required|string|max:255',
            'cliente_telefono'    => 'required|string|max:20',
            'cliente_direccion'   => 'required|string|max:255',
            'cliente_comentarios' => 'nullable|string',
            'metodo_entrega'      => 'required|in:pickup,delivery',
            'zona_delivery'       => 'nullable|in:dentro,fuera',
            'kms_fuera'           => 'nullable|integer|min:1',
            'delivery_cost'       => 'required|integer|min:0',
        ]);

        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        $subtotal     = collect($cart)->sum('total');
        $deliveryCost = $data['delivery_cost'];
        $total        = $subtotal + $deliveryCost;

        // Transacción para crear Order y OrderItems
        DB::transaction(function () use ($data, $cart, $subtotal, $deliveryCost, $total, &$order) {
            $order = Order::create([
                'cliente_nombre'      => $data['cliente_nombre'],
                'cliente_telefono'    => $data['cliente_telefono'],
                'cliente_direccion'   => $data['cliente_direccion'],
                'cliente_comentarios' => $data['cliente_comentarios'] ?? null,
                'metodo_entrega'      => $data['metodo_entrega'],
                'zona_delivery'       => $data['zona_delivery'] ?? null,
                'kms_fuera'           => $data['kms_fuera'] ?? null,
                'subtotal'            => $subtotal,
                'delivery_cost'       => $deliveryCost,
                'total'               => $total,
                'status'              => 'pendiente',
            ]);

            foreach ($cart as $item) {
                $order->items()->create([
                    'product_id'  => $item['producto_id'],
                    'nombre'      => $item['nombre'],
                    'unidades'    => $item['unidades'],
                    'precio_unit' => $item['precio_unit'],
                    'total'       => $item['total'],
                    'detalle'     => json_encode($item['detalle']),
                ]);
            }
        });

        // Generar y guardar PDF de la boleta
        $pdf      = Pdf::loadView('pdf.invoice', compact('order'))
            ->setPaper('A4', 'portrait');
        $fileName = "boletas/boleta-{$order->id}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        // Redirigir a Mercado Pago
        try {
            $initPoint = $mp->createPreference($order);
            return redirect()->away($initPoint);
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors('No pudimos conectar con Mercado Pago. Intenta nuevamente.');
        }
    }

    /* =========================================================
     * 3. Página de agradecimiento
     * =======================================================*/
    public function thankYou(Order $order)
    {
        return view('checkout.thankyou', compact('order'));
    }

    /* =========================================================
     * 4. Descargar boleta PDF
     * =======================================================*/
    public function download(Order $order)
    {
        $filename = "boletas/boleta-{$order->id}.pdf";

        if (! Storage::disk('public')->exists($filename)) {
            $order->load('items');
            $pdf = Pdf::loadView('pdf.invoice', compact('order'))
                ->setPaper('A4', 'portrait');
            Storage::disk('public')->put($filename, $pdf->output());
        }

        return Storage::disk('public')->download(
            $filename,
            "boleta-{$order->id}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}
