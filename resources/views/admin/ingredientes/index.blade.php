@extends('admin.layout')

@section('title', 'Ingredientes')
@section('page-title', 'Gestión de Ingredientes')

@section('content')
<div x-data="ingredienteModal()" x-cloak class="container mx-auto p-6">

  {{-- Mensaje de éxito --}}
  @if (session('success'))
    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
      {{ session('success') }}
    </div>
  @endif

  {{-- Header: Nuevo Ingrediente --}}
  <div class="mb-6 flex justify-end">
    <button
      type="button"
      @click="openCreate()"
      class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 focus:outline-none"
    >
      + Nuevo Ingrediente
    </button>
  </div>

  {{-- Tabla de Ingredientes --}}
  <div class="overflow-x-auto bg-white rounded shadow">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Costo</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creado</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        @foreach ($ingredientes as $ing)
        <tr class="hover:bg-gray-50">
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $ing->id }}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ing->nombre }}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($ing->tipo) }}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            @if ($ing->tipo === 'extra')
              ${{ number_format($ing->costo, 0, ',', '.') }}
            @else
              —
            @endif
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ $ing->created_at->format('d/m/Y') }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
            <button
              type="button"
              @click="openEdit({{ $ing->id }}, '{{ addslashes($ing->nombre) }}', '{{ $ing->tipo }}', {{ $ing->costo }})"
              class="text-blue-600 hover:underline focus:outline-none"
            >
              Editar
            </button>
            <button
              type="button"
              @click="openDelete({{ $ing->id }}, '{{ addslashes($ing->nombre) }}')"
              class="text-red-600 hover:underline focus:outline-none"
            >
              Eliminar
            </button>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Paginación --}}
  <div class="mt-4">
    {{ $ingredientes->links() }}
  </div>

  {{-- Modal: Crear Ingrediente --}}
  <div
    x-cloak
    x-show="showCreateModal"
    x-trap.noscroll="showCreateModal"
    x-transition.opacity.scale
    class="fixed inset-0 flex items-center justify-center z-50 overflow-y-auto"
    style="display: none;"
  >
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="closeCreate()" aria-hidden="true"></div>
    <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md mx-auto my-8">
      <div class="max-h-[80vh] overflow-y-auto p-6">
        <header class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Crear Nuevo Ingrediente</h3>
          <button
            type="button"
            @click="closeCreate()"
            class="text-gray-500 hover:text-gray-700 focus:outline-none"
          >&times;</button>
        </header>
        <form action="{{ route('admin.ingredientes.store') }}" method="POST" class="space-y-4">
          @csrf
          @include('admin.ingredientes._form')
          <div class="flex justify-end space-x-2">
            <button
              type="button"
              @click="closeCreate()"
              class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 focus:outline-none"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 focus:outline-none"
            >
              Guardar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Modal: Editar Ingrediente --}}
  <div
    x-cloak
    x-show="showEditModal"
    x-trap.noscroll="showEditModal"
    x-transition.opacity.scale
    class="fixed inset-0 flex items-center justify-center z-50 overflow-y-auto"
    style="display: none;"
  >
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="closeEdit()" aria-hidden="true"></div>
    <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md mx-auto my-8">
      <div class="max-h-[80vh] overflow-y-auto p-6">
        <header class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Editar Ingrediente</h3>
          <button
            type="button"
            @click="closeEdit()"
            class="text-gray-500 hover:text-gray-700 focus:outline-none"
          >&times;</button>
        </header>
        <form :action="editAction" method="POST" class="space-y-4">
          @csrf
          @method('PUT')
          @include('admin.ingredientes._form')
          <div class="flex justify-end space-x-2">
            <button
              type="button"
              @click="closeEdit()"
              class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 focus:outline-none"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none"
            >
              Actualizar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Modal: Eliminar Ingrediente --}}
  <div
    x-cloak
    x-show="showDeleteModal"
    x-trap.noscroll="showDeleteModal"
    x-transition.opacity.scale
    class="fixed inset-0 flex items-center justify-center z-50 overflow-y-auto"
    style="display: none;"
  >
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="closeDelete()" aria-hidden="true"></div>
    <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm mx-auto my-8">
      <div class="max-h-[80vh] overflow-y-auto p-6">
        <header class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Confirmar Eliminación</h3>
          <button
            type="button"
            @click="closeDelete()"
            class="text-gray-500 hover:text-gray-700 focus:outline-none"
          >&times;</button>
        </header>
        <div class="space-y-4">
          <p>¿Estás seguro de eliminar el ingrediente <strong x-text="deleteName"></strong>?</p>
          <div class="flex justify-end space-x-2">
            <button
              type="button"
              @click="closeDelete()"
              class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 focus:outline-none"
            >
              Cancelar
            </button>
            <form :action="deleteAction" method="POST" class="inline">
              @csrf
              @method('DELETE')
              <button
                type="submit"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 focus:outline-none"
              >
                Eliminar
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

@endsection
