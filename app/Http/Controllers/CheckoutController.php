<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    /**
     * Mostrar formulario de checkout.
     */
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        // Ya no necesitamos cargar productos; la vista usa sólo $cart
        return view('checkout.index', compact('cart'));
    }

    /**
     * Procesar el pedido: validar, guardar Order y OrderItems, generar PDF y redirigir al thank you.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_nombre'      => 'required|string|max:255',
            'cliente_telefono'    => 'required|string|max:20',
            'cliente_comentarios' => 'nullable|string',
        ]);

        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        // Calcular subtotal y total (aquí no consideramos envío)
        $subtotal = collect($cart)->sum(fn($i) => $i['total_price']);
        $total    = $subtotal;

        DB::transaction(function () use ($data, $cart, $subtotal, $total, &$order) {
            // Crear Order
            $order = Order::create([
                'cliente_nombre'      => $data['cliente_nombre'],
                'cliente_telefono'    => $data['cliente_telefono'],
                'cliente_comentarios' => $data['cliente_comentarios'] ?? null,
                'subtotal'            => $subtotal,
                'total'               => $total,
                'status'              => 'pendiente',
            ]);

            // Crear OrderItems
            foreach ($cart as $item) {
                $order->items()->create([
                    'product_id'    => $item['id'],             // ← ¡No olvides esto!
                    'nombre'        => $item['nombre'],
                    'unidades'      => $item['unidades'],
                    'precio_base'   => $item['base_price'],
                    'subtotal'      => $item['total_price'],
                    'removed_bases' => $item['removed_bases'] ?? null,
                    'extras'        => $item['extras']        ?? null,
                ]);
            }
        });

        // Generar PDF de boleta y guardarlo
        $pdf      = Pdf::loadView('pdf.invoice', compact('order'))
            ->setPaper('A4', 'portrait');
        $fileName = "boletas/boleta-{$order->id}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        // Vaciar carrito
        $request->session()->forget('cart');

        return redirect()->route('checkout.thankyou', $order->id);
    }

    /**
     * Página “Gracias por tu compra”.
     */
    public function thankYou(Order $order)
    {
        return view('checkout.thankyou', compact('order'));
    }

    /**
     * Descargar la boleta en PDF.
     */

    public function download(Order $order)
    {
        // Nombre relativo en disk 'public'
        $filename = "boletas/boleta-{$order->id}.pdf";

        // Si no existe, lo generamos y guardamos
        if (! Storage::disk('public')->exists($filename)) {
            $order->load('items'); // Asegura que los items estén cargados
            $pdf = Pdf::loadView('checkout.boleta', compact('order'))
                ->setPaper('A4', 'portrait');
            Storage::disk('public')->put($filename, $pdf->output());
        }

        // Devolver descarga desde el disk público
        return Storage::disk('public')->download(
            $filename,
            "boleta-{$order->id}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}
