<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0
        }

        h1,
        h2,
        h3 {
            margin: 8px 0
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            vertical-align: top
        }

        th {
            background: #f2f2f2
        }

        .right {
            text-align: right
        }

        .small {
            font-size: 10px;
            color: #555
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 12px
        }
    </style>
</head>

<body>
    {{-- ===== Encabezado ===== --}}
    <div class="header">
        <h1>Boleta #{{ $order->id }}</h1>
        <p>Fecha: {{ $order->created_at->format('d/m/Y H:i') }}</p>
    </div>

    {{-- ===== Datos Cliente ===== --}}
    <table>
        <tbody>
            <tr>
                <td><strong>Cliente:</strong> {{ $order->cliente_nombre }}</td>
                <td><strong>Teléfono:</strong> {{ $order->cliente_telefono }}</td>
            </tr>
            @if ($order->cliente_direccion)
                <tr>
                    <td colspan="2"><strong>Dirección:</strong> {{ $order->cliente_direccion }}</td>
                </tr>
            @endif
            @if ($order->cliente_comentarios)
                <tr>
                    <td colspan="2"><strong>Comentarios:</strong> {{ $order->cliente_comentarios }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- ===== Detalle Productos ===== --}}
    <h2>Detalle de Productos</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio unit.</th>
                <th>Personalización</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                @php($d = json_decode($item->detalle, true))
                <tr>
                    <td>{{ $item->nombre }}</td>
                    <td class="right">{{ $item->unidades }}</td>
                    <td class="right">${{ number_format($item->precio_unit, 0, ',', '.') }}</td>
                    <td>
                        <div class="small"><strong>Base:</strong> {{ $d['Base'] ?? '—' }}</div>
                        <div class="small"><strong>Proteínas:</strong>
                            {{ collect($d['Proteínas'] ?? [])->join(', ') ?: '—' }}</div>
                        <div class="small"><strong>Vegetales:</strong>
                            {{ collect($d['Vegetales'] ?? [])->join(', ') ?: '—' }}</div>
                        @if ($d['Sin queso'] ?? false)
                            <div class="small">Sin queso crema</div>
                        @endif
                        @if ($d['Sin cebollín'] ?? false)
                            <div class="small">Sin cebollín</div>
                        @endif
                    </td>
                    <td class="right font-bold">
                        ${{ number_format($item->total, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===== Totales ===== --}}
    <table>
        <tbody>
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td class="right">${{ number_format($order->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if (($order->envio ?? 0) > 0)
                <tr>
                    <td><strong>Envío:</strong></td>
                    <td class="right">${{ number_format($order->envio, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr>
                <td><strong>Total:</strong></td>
                <td class="right">${{ number_format($order->total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>¡Gracias por tu compra y buen provecho!</p>
    </div>
</body>

</html>
