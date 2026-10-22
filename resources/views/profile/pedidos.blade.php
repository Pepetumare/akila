@extends('profile.layout')
@section('panel-content')
    <div class="mb-6"><h2 class="mb-2 text-2xl font-bold tracking-tight">Mis pedidos</h2><p class="mb-0 text-sm text-[var(--akila-gray-700)]">Consulta el estado de tus pedidos y pagos.</p></div>
    @if($orders->isEmpty())
        <div class="rounded-2xl border border-[var(--akila-gray-200)] bg-white px-6 py-12 text-center"><x-store.icon name="orders" class="mx-auto mb-4 h-9 w-9 text-[var(--akila-red)]" /><h3 class="mb-3 text-xl font-bold">Tu primer pedido te espera</h3><p class="mb-6 text-sm text-[var(--akila-gray-700)]">Todavía no tienes pedidos asociados a tu cuenta.</p><a href="{{ route('menu') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[var(--akila-red)] px-6 py-3 text-sm font-bold text-white no-underline hover:bg-[var(--akila-black)] hover:text-white">Explorar la carta</a></div>
    @else
        <ul class="m-0 list-none space-y-4 p-0">
            @foreach($orders as $order)
                <li class="min-w-0 rounded-2xl border border-[var(--akila-gray-200)] bg-white p-6">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3"><div><h3 class="mb-2 text-lg font-bold">Pedido #{{ $order->id }}</h3><time datetime="{{ $order->created_at->toIso8601String() }}" class="text-xs text-[var(--akila-gray-700)]">{{ $order->created_at->format('d/m/Y · H:i') }}</time></div><x-store.price :amount="$order->total" class="text-xl" /></div>
                    <dl class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2"><div><dt class="mb-2 text-xs font-semibold uppercase tracking-wider text-[var(--akila-gray-700)]">Estado del pedido</dt><dd class="mb-0"><x-store.order-status :status="$order->status" /></dd></div><div><dt class="mb-2 text-xs font-semibold uppercase tracking-wider text-[var(--akila-gray-700)]">Estado del pago</dt><dd class="mb-0"><x-store.order-status :status="$order->payment_status" :payment="true" /></dd></div></dl>
                    <a href="{{ route('checkout.thankyou', $order) }}" aria-label="Ver detalle del pedido {{ $order->id }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-[var(--akila-gray-200)] px-5 py-3 text-sm font-bold text-[var(--akila-gray-900)] no-underline hover:border-[var(--akila-red)] hover:text-[var(--akila-red)] sm:w-auto">Ver detalle →</a>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
