@extends('admin.layout')

@section('title', 'Editar Ingrediente')
@section('page-title', 'Editar Ingrediente')

@section('content')
  <form action="{{ route('admin.ingredientes.update', $ingrediente) }}" method="POST">
    @csrf
    @method('PUT')
    {{-- PASAMOS $tipos (y también $ingrediente) al partial --}}
    @include('admin.ingredientes._form', [
      'ingrediente' => $ingrediente,
      'tipos'       => $tipos,
    ])
    <div class="mt-4">
      <button type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        Actualizar
      </button>
    </div>
  </form>
@endsection
