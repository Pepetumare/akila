<?php

namespace App\Http\Controllers;

use App\Models\Producto;
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

        return view('checkout.index', compact('cart'));
    }

    /* =========================================================
     * 2. Guardar pedido, generar PDF y vaciar carrito
     * =======================================================*/
    public function store(Request $request, MercadoPagoService $mp)
    {
        $data = $request->validate([
            'cliente_nombre'      => 'required|string|max:255',
            'cliente_telefono'    => 'required|string|max:20',
            'cliente_comentarios' => 'nullable|string',
            'cliente_direccion' => 'required|string|max:255',
        ]);

        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        $subtotal = collect($cart)->sum('total');   // ← cambio
        $total    = $subtotal;                      // (sin envío aún)

        /* ---------- Transacción BD ---------- */
        DB::transaction(function () use ($data, $cart, $subtotal, $total, &$order) {
            /* Crear Order principal */
            $order = Order::create([
                'cliente_nombre'      => $data['cliente_nombre'],
                'cliente_telefono'    => $data['cliente_telefono'],
                'cliente_comentarios' => $data['cliente_comentarios'] ?? null,
                'subtotal'            => $subtotal,
                'total'               => $total,
                'status'              => 'pendiente',
            ]);

            /* Crear cada OrderItem */
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

        /* ---------- Generar y guardar PDF ---------- */
        $pdf      = Pdf::loadView('pdf.invoice', compact('order'))
            ->setPaper('A4', 'portrait');
        $fileName = "boletas/boleta-{$order->id}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        /* Vaciar carrito */
        //$request->session()->forget('cart');

        // return redirect()->route('checkout.thankyou', $order->id);
        try {
            $initPoint = $mp->createPreference($order);

            // ② Redirige al pago
            return redirect()->away($initPoint);
        } catch (\Exception $e) {
            // Si Mercado Pago falla, muestra error al usuario
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
