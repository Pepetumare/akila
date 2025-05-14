<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
      body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        margin: 0;
        padding: 0;
      }
      h1, h2, h3 {
        margin: 8px 0;
      }
      table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
      }
      th, td {
        border: 1px solid #333;
        padding: 6px;
        vertical-align: top;
      }
      th {
        background: #f2f2f2;
      }
      .right {
        text-align: right;
      }
      .small {
        font-size: 10px;
        color: #555;
      }
      .header, .footer {
        text-align: center;
        margin-bottom: 12px;
      }
    </style>
</head>
<body>
    <div class="header">
      <h1>Boleta Pedido #{{ $order->id }}</h1>
      <p>Fecha: {{ $order->created_at->format('d/m/Y H:i') }}</p>
    </div>

    <table>
      <tbody>
        <tr>
          <td><strong>Cliente:</strong> {{ $order->cliente_nombre }}</td>
          <td><strong>Teléfono:</strong> {{ $order->cliente_telefono }}</td>
        </tr>
        @if($order->cliente_comentarios)
        <tr>
          <td colspan="2"><strong>Comentarios:</strong> {{ $order->cliente_comentarios }}</td>
        </tr>
        @endif
      </tbody>
    </table>

    <h2>Detalle de Productos</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio Base</th>
                <th>Personalización</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->nombre }}</td>
                <td class="right">{{ $item->unidades }}</td>
                <td class="right">${{ number_format($item->precio_base,0,',','.') }}</td>
                <td>
                    {{-- Bases quitadas --}}
                    @foreach($item->removed_bases ?? [] as $unit => $bases)
                        @if(!empty($bases))
                            <div class="small">
                                (U{{ $unit }}) Quitó: {{ implode(', ', $bases) }}
                            </div>
                        @endif
                    @endforeach

                    {{-- Extras --}}
                    @foreach($item->extras ?? [] as $unit => $extrasUnit)
                        @foreach($extrasUnit as $e)
                            <div class="small">
                                (U{{ $unit }}) +{{ $e['nombre'] }} ×{{ $e['cantidad'] }}
                                — ${{ number_format($e['price'] * $e['cantidad'],0,',','.') }}
                            </div>
                        @endforeach
                    @endforeach

                    {{-- Sin personalización --}}
                    @if(empty($item->removed_bases) && empty(array_filter($item->extras ?? [])))
                        <div class="small">Sin personalización</div>
                    @endif
                </td>
                <td class="right font-bold">
                  ${{ number_format($item->subtotal,0,',','.') }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table>
      <tbody>
        <tr>
          <td><strong>Subtotal:</strong></td>
          <td class="right">${{ number_format($order->subtotal,0,',','.') }}</td>
        </tr>
        {{-- Si hay costo de envío --}}
        @if(isset($order->envio) && $order->envio > 0)
        <tr>
          <td><strong>Envío:</strong></td>
          <td class="right">${{ number_format($order->envio,0,',','.') }}</td>
        </tr>
        @endif
        <tr>
          <td><strong>Total:</strong></td>
          <td class="right">${{ number_format($order->total,0,',','.') }}</td>
        </tr>
      </tbody>
    </table>

    <div class="footer">
      <p>¡Gracias por tu compra!</p>
    </div>
</body>
</html>
