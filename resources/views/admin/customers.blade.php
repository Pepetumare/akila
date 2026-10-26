@extends('admin.layout')
@section('page-title','Clientes')
@section('content')
<div class="page-intro"><div><h2>Clientes registrados</h2><p>Solo identidades vinculadas por usuario. No se fusionan invitados por nombre o teléfono.</p></div></div><section class="card table-card"><div class="table-scroll"><table><thead><tr><th>Cliente</th><th>Email</th><th>Pedidos</th><th>Última compra</th><th>Total pagado</th></tr></thead><tbody>@forelse($customers as $customer)<tr><td>{{ $customer->name }}</td><td>{{ $customer->email }}</td><td>{{ $customer->orders_count }}</td><td>{{ $customer->orders_max_created_at }}</td><td>${{ number_format($customer->total_spent ?? 0,0,',','.') }}</td></tr>@empty<tr><td colspan="5" class="empty">No hay clientes registrados con pedidos.</td></tr>@endforelse</tbody></table></div><x-admin.pagination :paginator="$customers"/></section>
@endsection
