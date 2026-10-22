@extends('admin.layout')
@section('page-title', 'Pedidos')
@section('content')
    @foreach ($orders as $order)
        <p><a href="{{ route('admin.orders.show', $order) }}">Pedido #{{ $order->id }}</a>
            — {{ $order->status }} — Pago: {{ $order->payment_status ?? 'Sin verificar' }}</p>
    @endforeach
    {{ $orders->links() }}
@endsection
