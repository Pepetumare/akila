<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\KitchenWorkflow;
use App\Services\OperationSettings;
use App\Services\OrderInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->withCount('items');
        if ($request->filled('status') && array_key_exists($request->string('status')->toString(), KitchenWorkflow::LABELS)) {
            $query->where('status', $request->string('status')->toString());
        }
        if (in_array($request->input('type'), ['pickup', 'delivery'], true)) {
            $query->where('metodo_entrega', $request->input('type'));
        }
        if ($request->filled('date')) {
            $request->validate(['date' => 'date_format:Y-m-d']);
            $date = \Carbon\Carbon::parse($request->input('date'), config('admin.timezone'));
            $query->whereBetween('created_at', [$date->copy()->startOfDay()->utc(), $date->copy()->endOfDay()->utc()]);
        }
        if ($request->filled('payment')) {
            $query->where('payment_status', $request->string('payment')->limit(40)->toString());
        }
        if ($q = $request->string('q')->limit(100)->toString()) {
            $query->where(fn ($q1) => $q1->where('cliente_nombre', 'like', "%{$q}%")->orWhere('id', ctype_digit($q) ? (int) $q : -1));
        }
        $orders = $query->latest()->paginate(15)->withQueryString();
        $selected = $request->filled('order') ? Order::with('items')->findOrFail($request->integer('order')) : $orders->first()?->load('items');

        return view('admin.orders.index', compact('orders', 'selected'));
    }

    public function show(Order $order)
    {
        $order->load('items');

        return view('admin.orders.show', compact('order'));
    }

    public function print(Order $order)
    {
        $order->load('items');

        return view('admin.orders.print', compact('order'));
    }

    public function download(Order $order, OrderInvoiceService $invoices)
    {
        return $invoices->download($order);
    }

    public function update(Request $request, Order $order, KitchenWorkflow $workflow)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(KitchenWorkflow::LABELS))], 'expected_status' => 'required|string']);
        $workflow->transition($order, $data['status'], $data['expected_status']);

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('success', 'Estado de cocina actualizado. El pago no se modifica.');
    }

    public function kitchen(Request $request, OperationSettings $settings)
    {
        $orders = Order::whereIn('status', KitchenWorkflow::ACTIVE)->with('items')->oldest()->get();
        $operation = $settings->get();
        if ($request->expectsJson()) {
            return response()->json(['html' => view('admin.kitchen-board', compact('orders', 'operation'))->render(), 'new_ids' => $orders->where('status', 'pendiente')->pluck('id')->values()])->header('Cache-Control', 'no-store');
        }

        return view('admin.kitchen', compact('orders', 'operation'));
    }
}
