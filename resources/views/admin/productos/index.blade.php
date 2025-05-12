@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4">Productos</h2>

    @if(session('success'))
      <p class="text-green-600 mb-4">{{ session('success') }}</p>
    @endif

    <a href="{{ route('admin.productos.create') }}"
       class="bg-green-500 text-white px-4 py-2 rounded mb-4 inline-block">
      Nuevo Producto
    </a>

    <table class="w-full border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 text-left">#</th>
                <th class="p-2 text-left">Nombre</th>
                <th class="p-2 text-left">Categoría</th>
                <th class="p-2 text-left">Precio</th>
                <th class="p-2 text-left">Personalizable</th>
                <th class="p-2 text-left">Acciones</th>
            </tr>
        </thead>
        <tbody>
        @foreach($productos as $prod)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-2">{{ $prod->id }}</td>
                <td class="p-2">{{ $prod->nombre }}</td>
                <td class="p-2">{{ $prod->categoria->nombre ?? '—' }}</td>
                <td class="p-2">${{ number_format($prod->precio,0,',','.') }}</td>
                <td class="p-2">{{ $prod->personalizable ? 'Sí' : 'No' }}</td>
                <td class="p-2 space-x-2">
                    <a href="{{ route('admin.productos.edit', $prod) }}"
                       class="text-blue-600 hover:underline">Editar</a>
                    <form action="{{ route('admin.productos.destroy', $prod) }}"
                          method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="text-red-600 hover:underline"
                                onclick="return confirm('¿Eliminar este producto?')">
                          Eliminar
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
