{{-- resources/views/admin/productos/edit.blade.php --}}
@extends('admin.layout')

@section('title', 'Editar Producto')
@section('page-title', 'Editar Producto: ' . $producto->nombre)

@section('content')
<div class="container mx-auto p-6 max-w-lg">
    @if ($errors->any())
      <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
        <ul class="list-disc ml-5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('admin.productos.update', $producto) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- FORM PARTIAL --}}
        {{-- Asegúrate de que tu partial _form use $producto, $categorias y $ingredientes --}}
        @include('admin.productos._form')

        <div class="mt-4 flex justify-end">
          <a href="{{ route('admin.productos.index') }}"
             class="mr-2 px-4 py-2 border rounded hover:bg-gray-100">Cancelar</a>
          <button type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Actualizar
          </button>
        </div>
    </form>
</div>
@endsection
