@props(['status', 'payment' => false])
@php
    $labels = $payment
        ? ['approved' => 'Aprobado', 'pending' => 'Pendiente', 'in_process' => 'En proceso', 'authorized' => 'Autorizado', 'rejected' => 'Rechazado', 'cancelled' => 'Cancelado', 'refunded' => 'Reembolsado', 'charged_back' => 'Contracargo']
        : ['pendiente' => 'Pendiente', 'en preparación' => 'En preparación', 'entregado' => 'Entregado'];
    $label = $labels[$status] ?? ($status ?: 'Sin verificar');
    $complete = $payment ? $status === 'approved' : $status === 'entregado';
    $attention = in_array($status, ['rejected', 'cancelled', 'refunded', 'charged_back'], true);
@endphp
<span {{ $attributes->class(['inline-flex min-h-8 items-center rounded-full border px-3 py-1 text-xs font-semibold', 'border-green-200 bg-green-50 text-green-800' => $complete, 'border-red-200 bg-red-50 text-red-800' => $attention, 'border-[var(--akila-gray-200)] bg-[var(--akila-gray-100)] text-[var(--akila-gray-700)]' => !$complete && !$attention]) }}>{{ $label }}</span>
