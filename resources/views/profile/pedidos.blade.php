@extends('profile.layout')

@section('panel-content')
<h2 class="text-xl font-bold mb-6">📦 Mis pedidos</h2>

@if($orders->isEmpty())
    <p class="text-gray-600">No tienes pedidos aún.</p>
@else
    <ul class="space-y-4">
        @foreach($orders as $order)
            <li class="p-4 border rounded bg-gray-50">
                <div class="flex justify-between items-center">
                    <span class="font-medium">Pedido #{{ $order->id }}</span>
                    <span class="text-sm text-gray-500">{{ $order->created_at->diffForHumans() }}</span>
                </div>
                <div class="text-sm mt-1 text-gray-600">
                    Estado: {{ $order->status }} <br>
                    Total: ${{ number_format($order->total, 0, ',', '.') }}
                </div>
            </li>
        @endforeach
    </ul>
@endif
@endsection
