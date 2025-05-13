@extends('admin.layout')

@section('title', 'Categorías')
@section('page-title', 'Gestión de Categorías')

@section('content')
    <div x-data="categoryModal()" x-cloak class="relative">

        {{-- Header: Búsqueda + Botón Nueva Categoría --}}
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            {{-- Búsqueda --}}
            <form action="{{ route('admin.categorias.index') }}" method="GET" class="flex items-center space-x-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar categoría..."
                    class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                <button type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 focus:outline-none">
                    Buscar
                </button>
            </form>

            {{-- Nueva categoría --}}
            <button @click.prevent="openCreate()"
                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 focus:outline-none">
                + Nueva Categoría
            </button>
        </div>

        {{-- Mensaje de éxito --}}
        @if (session('success'))
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $cat->created_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button @click.prevent="openEdit({{ $cat->id }}, '{{ addslashes($cat->nombre) }}')"
                                    class="text-blue-600 hover:underline focus:outline-none">
                                    Editar
                                </button>
                                <button @click.prevent="openDelete({{ $cat->id }}, '{{ addslashes($cat->nombre) }}')"
                                    class="text-red-600 hover:underline focus:outline-none">
                                    Eliminar
                                </button>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No se encontraron categorías.
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

        {{-- Modal Crear Categoría --}}
        <div x-show="showCreateModal" x-trap.noscroll="showCreateModal" x-transition.opacity.scale
            class="fixed inset-0 flex items-center justify-center z-50" style="display: none;">
            <div class="absolute inset-0 bg-black bg-opacity-50" @click="closeCreate()" aria-hidden="true"></div>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden w-full max-w-md mx-4 z-50">
                <header class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Crear Nueva Categoría</h3>
                    <button @click="closeCreate()"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none">&times;</button>
                </header>
                <form action="{{ route('admin.categorias.store') }}" method="POST" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label for="create_nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" id="create_nombre" name="nombre" x-model="createNombre"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-green-300"
                            required>
                        @error('nombre')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="closeCreate()"
                            class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 focus:outline-none">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 focus:outline-none">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Editar Categoría --}}
        <div x-show="showEditModal" x-trap.noscroll="showEditModal" x-transition.opacity.scale
            class="fixed inset-0 flex items-center justify-center z-50" style="display: none;">
            <div class="absolute inset-0 bg-black bg-opacity-50" @click="closeEdit()" aria-hidden="true"></div>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden w-full max-w-md mx-4 z-50">
                <header class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Editar Categoría</h3>
                    <button @click="closeEdit()"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none">&times;</button>
                </header>
                <form :action="editAction" method="POST" class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="edit_nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" id="edit_nombre" name="nombre" x-model="editNombre"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-green-300"
                            required>
                        @error('nombre')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="closeEdit()"
                            class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 focus:outline-none">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
        {{-- Modal Confirmar Eliminación --}}
        <div x-show="showDeleteModal" x-trap.noscroll="showDeleteModal" x-cloak x-transition.opacity.scale
            class="fixed inset-0 flex items-center justify-center z-50" style="display: none;">
            <div class="absolute inset-0 bg-black bg-opacity-50" @click="closeDelete()" aria-hidden="true"></div>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden w-full max-w-sm mx-4 z-50">
                <header class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Confirmar Eliminación</h3>
                    <button @click="closeDelete()"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none">&times;</button>
                </header>
                <div class="px-6 py-4">
                    <p class="mb-4">¿Estás seguro de eliminar la categoría <strong x-text="deleteName"></strong>?</p>
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="closeDelete()"
                            class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 focus:outline-none">Cancelar</button>
                        <form :action="deleteAction" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 focus:outline-none">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        {{-- /Modal Confirmar Eliminación --}}


    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('categoryModal', () => ({
                showCreateModal: false,
                showEditModal: false,
                showDeleteModal: false,
                deleteAction: '',
                deleteName: '',
                createNombre: '',
                editNombre: '',
                editAction: '',
                openCreate() {
                    this.showCreateModal = true;
                    this.$nextTick(() => this.$el.querySelector('#create_nombre').focus());
                },
                closeCreate() {
                    this.showCreateModal = false;
                    this.createNombre = '';
                },
                openEdit(id, nombre) {
                    this.editAction = '/admin/categorias/' + id;
                    this.editNombre = nombre;
                    this.showEditModal = true;
                    this.$nextTick(() => this.$el.querySelector('#edit_nombre').focus());
                },
                closeEdit() {
                    this.showEditModal = false;
                    this.editNombre = '';
                    this.editAction = '';
                },
                openDelete(id, nombre) {
                    this.deleteAction = '/admin/categorias/' + id;
                    this.deleteName = nombre;
                    this.showDeleteModal = true;
                },
                closeDelete() {
                    this.showDeleteModal = false;
                    this.deleteAction = '';
                    this.deleteName = '';
                },
            }));
        });
    </script>
@endpush
