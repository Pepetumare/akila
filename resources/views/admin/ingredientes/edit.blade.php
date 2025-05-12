@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6 max-w-lg">
    <h2 class="text-2xl font-bold mb-4">Editar Ingrediente</h2>

    <form action="{{ route('admin.ingredientes.update', $ingrediente) }}" method="POST">
        @method('PUT')
        @include('admin.ingredientes._form')
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
            Actualizar
        </button>
    </form>
</div>
@endsection
