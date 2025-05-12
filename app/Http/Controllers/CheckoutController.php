<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage; // <— Importar Storage

class CheckoutController extends Controller
{
    // Mostrar formulario de checkout
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        // IDs y productos
        $productIds = array_column($cart, 'product_id');
        $productos  = Producto::whereIn('id', $productIds)
                              ->get()
                              ->keyBy('id');

        return view('checkout.index', compact('cart', 'productos'));
    }

    // Guardar pedido, generar PDF y redirigir a "thank you"
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'     => 'required|string',
            'telefono'   => 'required|string',
            'comentarios'=> 'nullable|string',
        ]);

        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        // Sumamos precios de todos los ítems
        $total = collect($cart)->sum('price');

        // Crear pedido
        $order = Order::create([
            'cliente_nombre'   => $data['nombre'],
            'cliente_telefono' => $data['telefono'],
            'comentarios'      => $data['comentarios'] ?? null,
            'total'            => $total,
        ]);

        // Crear ítems relacionados
        foreach ($cart as $item) {
            $order->items()->create([
                'product_id'    => $item['product_id'],
                'removed_bases' => $item['removed_bases'] ?? [],
                'extras'        => $item['extras']        ?? [],
                'price'         => $item['price'],
            ]);
        }

        // Generar PDF de la boleta
        $pdf      = Pdf::loadView('pdf.invoice', compact('order'));
        $fileName = "boletas/boleta-{$order->id}.pdf";

        // Guardar en storage/app/public/boletas
        Storage::disk('public')->put($fileName, $pdf->output());

        // Vaciar carrito
        $request->session()->forget('cart');

        // Redirigir a página de agradecimiento
        return redirect()->route('checkout.thankyou', ['order' => $order->id]);
    }

    // Página de "Gracias por tu compra"
    public function thankYou($order)
    {
        // Recibimos el ID para mostrar enlace de descarga
        return view('checkout.thankyou', compact('order'));
    }

    public function download($orderId)
    {
        $file = storage_path("app/public/boletas/boleta-{$orderId}.pdf");

        if (!file_exists($file)) {
            abort(404, 'Boleta no encontrada.');
        }

        return response()->download($file, "boleta-{$orderId}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
