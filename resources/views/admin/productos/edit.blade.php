@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6 max-w-lg">
    <h2 class="text-2xl font-bold mb-4">Editar Producto</h2>

    <form action="{{ route('admin.productos.update', $producto) }}" method="POST" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.productos._form')
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
            Actualizar
        </button>
    </form>
</div>
@endsection
