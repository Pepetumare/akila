<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminChange;
use App\Models\GrupoOpcion;
use App\Models\Order;
use App\Models\ReglaPromocion;
use App\Models\User;
use App\Services\KitchenMetrics;
use App\Services\OperationSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    public function settings(OperationSettings $settings)
    {
        return view('admin.settings', ['operation' => $settings->get()]);
    }

    public function saveSettings(Request $request, OperationSettings $settings)
    {
        $data = $request->validate([
            'is_open' => 'required|boolean', 'online_orders' => 'required|boolean', 'delivery_enabled' => 'required|boolean',
            'estimate_min' => 'required|integer|min:0|max:240', 'estimate_max' => 'required|integer|gte:estimate_min|max:240',
            'overdue_minutes' => 'required|integer|min:1|max:240',
            'load_medium' => 'required|integer|min:1|max:100000', 'load_high' => 'required|integer|gt:load_medium|max:100001',
            'message' => 'nullable|string|max:500', 'hours' => 'sometimes|array:0,1,2,3,4,5,6|max:7',
            'hours.*.active' => 'required|boolean', 'hours.*.open' => 'nullable|date_format:H:i', 'hours.*.close' => 'nullable|date_format:H:i',
        ]);
        DB::transaction(function () use ($data, $settings, $request) {
            $before = $settings->get();
            $settings->save($data);
            AdminChange::record($request->user(), 'configuración operativa', ['operation' => ['before' => $before, 'after' => $data]]);
        });

        return back()->with('success', 'Configuración operativa guardada.');
    }

    public function reports(Request $request, KitchenMetrics $metrics)
    {
        $data = $request->validate(['period' => 'nullable|in:today,7,30,custom', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        $period = $data['period'] ?? '7';
        $localNow = \App\Services\AdminClock::now();
        $from = $period === 'custom' ? \Carbon\Carbon::parse($data['from'] ?? $localNow->toDateString(), config('admin.timezone'))->startOfDay() : $localNow->copy()->subDays(match ($period) { 'today' => 0, '30' => 29, default => 6 })->startOfDay();
        $to = $period === 'custom' ? \Carbon\Carbon::parse($data['to'] ?? $localNow->toDateString(), config('admin.timezone'))->endOfDay() : $localNow->copy()->endOfDay();
        abort_if($from->diffInDays($to) > 366, 422, 'El rango máximo es un año.');
        $query = Order::whereBetween('created_at', [$from->copy()->utc(), $to->copy()->utc()]);
        $ordersCount = (clone $query)->count();
        $paid = (clone $query)->where('payment_status', 'approved')->with('items')->get();
        $sales = $paid->sum('total');
        $sold = $metrics->items($paid->flatMap->items);
        $chart = $paid->groupBy(fn ($order) => $order->created_at->setTimezone(config('admin.timezone'))->format('d/m'))->map->sum('total');
        $top = $paid->flatMap->items->groupBy('nombre')->map(fn ($items) => ['quantity' => $items->sum('unidades'), 'total' => $items->sum('total')])->sortByDesc('total')->take(8);

        return view('admin.reports', compact('period', 'from', 'to', 'ordersCount', 'paid', 'sales', 'sold', 'chart', 'top'));
    }

    public function personalization()
    {
        $groups = GrupoOpcion::with('opciones.ingrediente')->withCount('productos', 'bloques')->orderBy('orden')->get();
        $rules = ReglaPromocion::select('tipo', DB::raw('COUNT(*) as total'))->where('activo', true)->groupBy('tipo')->get();

        return view('admin.personalization', compact('groups', 'rules'));
    }

    public function customers()
    {
        $customers = User::whereHas('orders')->withCount('orders')->withSum(['orders as total_spent' => fn ($q) => $q->where('payment_status', 'approved')], 'total')->withMax('orders', 'created_at')->paginate(20);

        return view('admin.customers', compact('customers'));
    }
}
