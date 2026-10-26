@extends('admin.layout')
@section('page-title','Inventario')
@section('content')
<section class="card empty inventory-empty"><x-admin.icon name="box"/><span class="badge neutral">Próxima fase</span><h2>Inventario con información confiable.</h2><p>Aún no existen recetas operacionales, saldos ni movimientos de stock. No se presentan existencias calculadas a partir de piezas comerciales.</p><a class="btn" href="{{ route('admin.ingredientes.index') }}">Gestionar disponibilidad de ingredientes</a></section>
@endsection
