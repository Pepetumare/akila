@extends('layouts.app')

@section('title', 'Mi Perfil')
@section('content')
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4">¡Bienvenido, {{ $user->name }}!</h2>

    <div class="mb-6">
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <!-- aquí más datos de perfil si quieres -->
    </div>

    @if($user->isAdmin())
        <a href="{{ route('admin.dashboard') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Ir al Dashboard de Admin
        </a>
    @endif
</div>
@endsection
