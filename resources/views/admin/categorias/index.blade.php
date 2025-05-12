@extends('admin.layout')

@section('title', 'Categorías')
@section('page-title', 'Gestión de Categorías')

@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
  {{-- Búsqueda --}}
  <form action="{{ route('admin.categorias.index') }}" method="GET" class="flex items-center space-x-2">
    <input 
      type="text" 
      name="q" 
      value="{{ request('q') }}" 
      placeholder="Buscar categoría..." 
      class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"
    >
    <button 
      type="submit" 
      class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 focus:outline-none">
      Buscar
    </button>
  </form>

  {{-- Nueva categoría --}}
  <a 
    href="{{ route('admin.categorias.create') }}" 
    class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 focus:outline-none">
    + Nueva Categoría
  </a>
</div>

{{-- Mensaje de éxito --}}
@if(session('success'))
  <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
    {{ session('success') }}
  </div>
@endif

{{-- Tabla de categorías --}}
<div class="overflow-x-auto bg-white rounded shadow">
  <table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
      <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creado</th>
        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
      @forelse($categorias as $cat)
      <tr class="hover:bg-gray-50">
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $cat->id }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $cat->nombre }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $cat->slug }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $cat->created_at->format('d/m/Y') }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
          <a 
            href="{{ route('admin.categorias.edit', $cat) }}" 
            class="text-blue-600 hover:underline">
            Editar
          </a>
          <form 
            action="{{ route('admin.categorias.destroy', $cat) }}" 
            method="POST" 
            class="inline"
            onsubmit="return confirm('¿Eliminar categoría {{ $cat->nombre }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-red-600 hover:underline">
              Eliminar
            </button>
          </form>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
          No se encontraron categorías.
        </td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>

{{-- Paginación --}}
<div class="mt-4">
  {{ $categorias->appends(request()->only('q'))->links() }}
</div>
@endsection
