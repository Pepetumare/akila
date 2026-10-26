<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('page-title', 'Resumen') · Sushi Akila Admin</title><link rel="icon" href="{{ asset('img/logo/logo-icon.png') }}">
@vite(['resources/css/admin.css', 'resources/js/admin.js'])</head>
@php
$adminOperation = app(\App\Services\OperationSettings::class)->get();
$adminAlerts = \App\Models\Order::whereIn('status', \App\Services\KitchenWorkflow::ACTIVE)->where(fn($q)=>$q->where('status','pendiente')->orWhere(fn($q)=>$q->where('status','en preparación')->where('preparing_at','<',now()->subMinutes($adminOperation['overdue_minutes']))))->count();
$nav = [
['home','Resumen','admin.dashboard','admin.dashboard'],['orders','Pedidos','admin.orders.index','admin.orders.*'],['kitchen','Cocina','admin.kitchen','admin.kitchen'],
['menu','Carta','menu','none'],['portion','Productos','admin.productos.index','admin.productos.*'],['grid','Categorías','admin.categorias.index','admin.categorias.*'],
['leaf','Ingredientes','admin.ingredientes.index','admin.ingredientes.*'],['sliders','Personalización','admin.personalization','admin.personalization'],
['box','Inventario','admin.inventory','admin.inventory'],['account','Clientes','admin.customers','admin.customers'],['chart','Reportes','admin.reports','admin.reports']];
@endphp
<body class="admin-app">
<a class="skip-link" href="#main">Saltar al contenido</a>
<button class="admin-scrim" data-nav-close aria-label="Cerrar navegación" hidden></button>
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Navegación administrativa">
<a class="admin-brand" href="{{ route('admin.dashboard') }}"><img src="{{ asset('img/logo/logo-light-transparent.png') }}" alt="Sushi Akila"><span>Administración</span></a>
<nav>@foreach($nav as [$icon,$label,$route,$match])<a href="{{ route($route) }}" class="{{ request()->routeIs($match) ? 'active' : '' }}" @if(request()->routeIs($match)) aria-current="page" @endif @if($route==='menu') target="_blank" rel="noopener" @endif><x-admin.icon :name="$icon"/><span>{{ $label }}</span>@if($label==='Pedidos' && $adminAlerts)<small>{{ $adminAlerts }}</small>@endif</a>@endforeach</nav>
<a class="admin-settings-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="{{ route('admin.settings') }}"><x-admin.icon name="settings"/>Configuración</a>
<div class="admin-sidebar-bottom"><span class="store-status"><i class="{{ $adminOperation['is_open'] ? 'dot green' : 'dot red' }}"></i> Local {{ $adminOperation['is_open'] ? 'abierto' : 'cerrado' }}</span><div class="admin-user"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>Administrador</small></div></div><form method="post" action="{{ route('logout') }}">@csrf<button class="logout" type="submit"><x-admin.icon name="logout"/>Cerrar sesión</button></form></div>
</aside>
<div class="admin-workspace">
<header class="admin-topbar"><div class="admin-heading"><button class="icon-button nav-toggle" data-nav-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Abrir navegación"><x-admin.icon name="menu"/></button><div><span class="breadcrumb">Sushi Akila / Administración</span><h1>@yield('page-title', 'Resumen')</h1></div></div>
<div class="topbar-actions"><time>{{ \App\Services\AdminClock::now()->locale('es')->translatedFormat('D d M') }}</time><a class="store-status" href="{{ route('admin.settings') }}"><i class="dot {{ $adminOperation['is_open'] ? 'green' : 'red' }}"></i>{{ $adminOperation['is_open'] ? 'Local abierto' : 'Local cerrado' }}</a><a class="icon-button notification" href="{{ route('admin.dashboard') }}#alerts" aria-label="{{ $adminAlerts }} alertas operativas"><x-admin.icon name="bell"/>@if($adminAlerts)<small>{{ $adminAlerts }}</small>@endif</a><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span></div></header>
<main id="main" class="admin-main">
@if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="notice danger" role="alert"><strong>Revisa los datos</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
<footer class="admin-page-footer">Sushi Akila · Centro operativo <span>{{ \App\Services\AdminClock::now()->format('Y') }}</span></footer>
</main></div>
<dialog id="admin-confirm" aria-labelledby="confirm-title"><form method="dialog"><h2 id="confirm-title">Confirmar acción</h2><p data-confirm-message></p><div class="button-row"><button value="cancel" class="btn">Volver</button><button value="confirm" class="btn primary">Confirmar</button></div></form></dialog>
@stack('scripts')
</body></html>
