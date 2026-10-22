@extends('admin.layout')
@section('page-title', 'Detalle del pedido')
@section('content')
    <p>Pedido #{{ $order->id }} — {{ $order->cliente_nombre }}</p>
    <p>Estado: {{ $order->status }} — Pago: {{ $order->payment_status ?? 'Sin verificar' }}</p>
    @foreach ($order->items as $item)
        <p>{{ $item->nombre }} × {{ $item->unidades }}: ${{ number_format($item->total, 0, ',', '.') }}</p>
        @if(isset($item->detalle['Bloques']))
            <x-menu-selection-summary :detail="$item->detalle" />
        @endif
    @endforeach
    <a href="{{ route('admin.orders.download', $order) }}">Descargar boleta</a>
    <form method="POST" action="{{ route('admin.orders.update', $order) }}">
        @csrf
        @method('PATCH')
        <select name="status">
            @foreach (['pendiente', 'en preparación', 'entregado'] as $status)
                <option @selected($order->status === $status)>{{ $status }}</option>
            @endforeach
        </select>
        <button type="submit">Actualizar preparación</button>
    </form>
@endsection
