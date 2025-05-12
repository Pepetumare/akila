<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
      body { font-family: DejaVu Sans, sans-serif; }
      table { width: 100%; border-collapse: collapse; }
      th, td { border: 1px solid #333; padding: 8px; text-align: left; }
      th { background: #eee; }
    </style>
</head>
<body>
    <h1>Boleta de Pedido #{{ $order->id }}</h1>
    <p><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
    <p><strong>Cliente:</strong> {{ $order->cliente_nombre }}</p>
    <p><strong>Teléfono:</strong> {{ $order->cliente_telefono }}</p>
    @if($order->comentarios)
      <p><strong>Comentarios:</strong> {{ $order->comentarios }}</p>
    @endif

    <h2>Detalle de Productos</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Personalización</th>
                <th>Precio</th>
            </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->producto->nombre }}</td>
                <td>
                    {{-- Bases quitadas por unidad --}}
                    @foreach($item->removed_bases ?? [] as $unit => $bases)
                        @if(!empty($bases))
                            <small>Unidad {{ $unit }} quitó: {{ implode(', ', $bases) }}</small><br>
                        @endif
                    @endforeach

                    {{-- Extras por unidad --}}
                    @foreach($item->extras ?? [] as $unit => $extrasUnit)
                        @if(!empty($extrasUnit))
                            <small>Unidad {{ $unit }} extras: {{ implode(', ', array_column($extrasUnit, 'ingredient')) }}</small><br>
                        @endif
                    @endforeach

                    @if(empty($item->removed_bases) && empty(array_filter($item->extras ?? [])))
                        <small>Sin personalización</small>
                    @endif
                </td>
                <td>${{ number_format($item->price,0,',','.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3>Total: ${{ number_format($order->total,0,',','.') }}</h3>
</body>
</html>
