@extends('admin.layout')

@section('title', 'Productos')
@section('page-title', 'Gestión de Productos')

@section('content')
    <div x-data="productModal(@json($categorias), @json($ingredientes))" x-cloak
        class="container mx-auto p-6">

        {{-- Mensaje de éxito --}}
        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        {{-- Header: Nuevo Producto --}}
        <div class="mb-6 flex justify-center">
            <button @click="openCreate()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                + Nuevo Producto
            </button>
        </div>

        {{-- Tabla de Productos --}}
        <div class="overflow-x-auto bg-white rounded shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">#</th>
                        <th class="px-6 py-3 text-left">Nombre</th>
                        <th class="px-6 py-3 text-left">Categoría</th>
                        <th class="px-6 py-3 text-left">Precio</th>
                        <th class="px-6 py-3 text-left">Personalizable</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($productos as $prod)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">{{ $prod->id }}</td>
                            <td class="px-6 py-4">{{ $prod->nombre }}</td>
                            <td class="px-6 py-4">{{ $prod->categoria->nombre ?? '—' }}</td>
                            <td class="px-6 py-4">${{ number_format($prod->precio, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">{{ $prod->personalizable ? 'Sí' : 'No' }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button
                                    @click='openEdit(
    {{ $prod->id }},
    @json($prod->nombre),
    {{ $prod->categoria_id ?? 'null' }},
    {{ $prod->precio }},
    {{ $prod->personalizable ? 'true' : 'false' }},
    {{ $prod->unidades }},
    @json($prod->ingredientes->map(fn($i) => ['id' => $i->id, 'pivot' => $i->pivot]))
  )'
                                    class="text-blue-600 hover:underline">
                                    Editar
                                </button>
                                <button @click='openDelete({{ $prod->id }}, @json($prod->nombre))'
                                    class="text-red-600 hover:underline">
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
            {{ $productos->links() }}
        </div>

        {{-- MODALES --}}
        <template x-if="activeModal==='create'">
            <div class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
                <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4 my-8 overflow-hidden">
                    <div class="p-6 max-h-[80vh] overflow-y-auto">
                        <header class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Crear Nuevo Producto</h3>
                            <button @click="closeModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
                        </header>
                        <form :action="`/admin/productos`" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @include('admin.productos._form')
                            <div class="flex justify-end space-x-2">
                                <button type="button" @click="closeModal()"
                                    class="px-4 py-2 border rounded hover:bg-gray-100">Cancelar</button>
                                <button type="submit"
                                    class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="activeModal==='edit'">
            <div class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
                <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4 my-8 overflow-hidden">
                    <div class="p-6 max-h-[80vh] overflow-y-auto">
                        <header class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Editar Producto</h3>
                            <button @click="closeModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
                        </header>
                        <form :action="editAction" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @method('PUT')
                            @include('admin.productos._form')
                            <div class="flex justify-end space-x-2">
                                <button type="button" @click="closeModal()"
                                    class="px-4 py-2 border rounded hover:bg-gray-100">Cancelar</button>
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Actualizar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="activeModal==='delete'">
            <div class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
                <div class="bg-white rounded-lg shadow-lg w-full max-w-sm mx-4 my-8 p-6">
                    <header class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Confirmar Eliminación</h3>
                        <button @click="closeModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
                    </header>
                    <p class="mb-4">¿Eliminar el producto <strong x-text="form.nombre"></strong>?</p>
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="closeModal()"
                            class="px-4 py-2 border rounded hover:bg-gray-100">Cancelar</button>
                        <form :action="deleteAction" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </template>

    </div>
@endsection

@push('scripts')
@endpush
