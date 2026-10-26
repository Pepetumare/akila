@extends('admin.layout')
@section('page-title','Cocina')
@section('content')
<section data-kds data-poll-url="{{ route('admin.kitchen') }}">
<div class="page-intro"><div><h2>Todo bajo control, en cocina.</h2><p>Pedidos activos · actualización cada 15 segundos · <span data-poll-status role="status">Conectado</span></p></div><div class="button-row"><button class="btn" data-kds-sound aria-pressed="false">Sonido OFF</button><button class="btn" data-fullscreen>Pantalla completa</button></div></div>
<div data-kds-board>@include('admin.kitchen-board')</div></section>
@endsection
