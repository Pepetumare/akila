@extends('admin.layout')
@section('page-title','Detalle del pedido')
@section('content')
<div class="page-intro"><a href="{{ route('admin.orders.index') }}">← Volver a pedidos</a></div><div class="detail-page">@include('admin.orders.detail')</div>
@endsection
