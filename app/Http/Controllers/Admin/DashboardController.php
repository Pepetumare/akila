<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingrediente;
use App\Models\Order;
use App\Models\Producto;
use App\Services\KitchenMetrics;
use App\Services\KitchenWorkflow;
use App\Services\OperationSettings;

class DashboardController extends Controller
{
    public function index(KitchenMetrics $metrics, OperationSettings $settings)
    {
        $operation = $settings->get();
        $today = Order::whereBetween('created_at', \App\Services\AdminClock::dayBounds());
        $paid = (clone $today)->where('payment_status', 'approved');
        $sales = (clone $paid)->sum('total');
        $paidCount = (clone $paid)->count();
        $ordersToday = (clone $today)->count();
        $sold = $metrics->items((clone $paid)->with('items')->get()->flatMap->items);
        $active = Order::whereIn('status', KitchenWorkflow::ACTIVE)->with('items')->oldest()->get();
        $load = $metrics->items($active->flatMap->items);
        $level = $load['pieces'] >= $operation['load_high'] ? 'Alta' : ($load['pieces'] >= $operation['load_medium'] ? 'Media' : 'Baja');
        $products = Producto::where('activo', true)->count();
        $ingredients = Ingrediente::count();
        $unavailable = Ingrediente::where('is_available', false)->count();
        $overdue = $active->filter(fn ($o) => $o->status === 'en preparación' && ($o->preparing_at ?? $o->created_at)->lt(now()->subMinutes($operation['overdue_minutes'])))->count();
        $pendingPayments = $active->whereNotIn('payment_status', ['approved', 'refunded', 'charged_back', 'rejected', 'cancelled'])->count();
        $chart = (clone $paid)->get(['created_at', 'total'])->groupBy(fn ($o) => $o->created_at->setTimezone(config('admin.timezone'))->format('H'))->map->sum('total');

        return view('admin.dashboard', compact('operation', 'sales', 'paidCount', 'ordersToday', 'sold', 'active', 'load', 'level', 'products', 'ingredients', 'unavailable', 'overdue', 'pendingPayments', 'chart'));
    }
}
