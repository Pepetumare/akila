@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4">Crear Categoría</h2>
    <form action="{{ route('admin.categorias.store') }}" method="POST">
        @include('admin.categorias._form')
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Guardar</button>
    </form>
</div>
@endsection
