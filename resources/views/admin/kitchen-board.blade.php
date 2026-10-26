<div class="kds-columns">
@foreach(['pendiente'=>['Nuevos','red'],'en preparación'=>['Preparando','orange'],'listo'=>['Listos','green']] as $status=>[$label,$tone])
<section class="kds-column {{ $tone }}"><div class="kds-column-heading"><h2><i class="dot {{ $tone }}"></i>{{ $label }}</h2><span class="count">{{ $orders->where('status',$status)->count() }}</span></div>
@forelse($orders->where('status',$status) as $order)
@php($quantities=app(\App\Services\KitchenMetrics::class)->items($order->items))
@php($since=($status==='listo' ? $order->ready_at : ($status==='en preparación' ? $order->preparing_at : null)) ?? $order->created_at)
@php($late=$status==='en preparación' && $since->lt(now()->subMinutes($operation['overdue_minutes'])))
<article class="kds-card {{ $late ? 'overdue' : '' }}" data-order-id="{{ $order->id }}"><header><a href="{{ route('admin.orders.show',$order) }}">#{{ $order->id }}</a><time>{{ $order->created_at->copy()->setTimezone(config('admin.timezone'))->format('H:i') }}</time></header><div class="kds-meta"><span class="badge {{ $order->metodo_entrega==='delivery' ? 'blue' : 'neutral' }}">{{ $order->metodo_entrega==='delivery' ? 'Delivery' : 'Retiro' }}</span><span data-elapsed="{{ $since->toIso8601String() }}">{{ (int)$since->diffInMinutes(now()) }} min</span></div>@if($late)<span class="badge red">ATRASADO</span>@endif
<div class="kds-quantity">@if($quantities['pieces'])<b>{{ $quantities['pieces'] }} piezas</b>@endif @if($quantities['hand_rolls'])<b>{{ $quantities['hand_rolls'] }} Hand Rolls</b>@endif @if($quantities['units'])<b>{{ $quantities['units'] }} unidades</b>@endif @if($quantities['uncovered'])<small>{{ $quantities['uncovered'] }} líneas legacy: cantidad incluida no confirmada</small>@endif</div>
@foreach($order->items as $item)<div class="kds-item"><strong>{{ $item->unidades }} × {{ data_get($item->snapshot,'product.name',$item->nombre) }}</strong><details><summary>Ver composición y salsas</summary><x-menu-selection-summary :detail="data_get($item->snapshot,'selections',$item->detalle ?? [])"/></details></div>@endforeach
@if($order->cliente_comentarios)<p class="order-note">{{ $order->cliente_comentarios }}</p>@endif
<div class="kds-payment"><x-admin.badge :state="$order->payment_status"/></div>
@include('admin.orders.action',['order'=>$order])
</article>@empty<div class="kds-empty"><x-admin.icon name="check"/><p>Sin pedidos {{ mb_strtolower($label) }}.</p></div>@endforelse</section>@endforeach</div>
