@extends('admin.layout')

@section('title', 'Crear Ingrediente')
@section('page-title', 'Nuevo Ingrediente')

@section('content')
  <form action="{{ route('admin.ingredientes.store') }}" method="POST">
    @csrf
    {{-- PASAMOS $tipos al partial --}}
    @include('admin.ingredientes._form', ['tipos' => $tipos])
    <div class="mt-4">
      <button type="submit"
              class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
        Guardar
      </button>
    </div>
  </form>
@endsection
