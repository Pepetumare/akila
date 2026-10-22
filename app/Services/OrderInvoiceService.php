<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class OrderInvoiceService
{
    public function download(Order $order)
    {
        $path = "boletas/boleta-{$order->id}.pdf";
        $private = Storage::disk('local');
        if (! $private->exists($path)) {
            $contents = Storage::disk('public')->exists($path)
                ? Storage::disk('public')->get($path)
                : Pdf::loadView('pdf.invoice', ['order' => $order->loadMissing('items')])->setPaper('A4')->output();
            abort_unless($private->put($path, $contents), 500);
        }

        return $private->download($path, "boleta-{$order->id}.pdf", ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store']);
    }
}
