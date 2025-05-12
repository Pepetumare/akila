@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4">Ingredientes</h2>
    @if(session('success'))
      <p class="text-green-600 mb-4">{{ session('success') }}</p>
    @endif

    <a href="{{ route('admin.ingredientes.create') }}"
       class="bg-green-500 text-white px-4 py-2 rounded mb-4 inline-block">
      Nuevo Ingrediente
    </a>

    <table class="w-full border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 text-left">#</th>
                <th class="p-2 text-left">Nombre</th>
                <th class="p-2 text-left">Tipo</th>
                <th class="p-2 text-left">Costo</th>
                <th class="p-2 text-left">Acciones</th>
            </tr>
        </thead>
        <tbody>
        @foreach($ingredientes as $ing)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-2">{{ $ing->id }}</td>
                <td class="p-2">{{ $ing->nombre }}</td>
                <td class="p-2 capitalize">{{ $ing->tipo }}</td>
                <td class="p-2">
                    @if($ing->tipo === 'extra')
                        ${{ number_format($ing->costo, 0, ',', '.') }}
                    @else
                        —
                    @endif
                </td>
                <td class="p-2 space-x-2">
                    <a href="{{ route('admin.ingredientes.edit', $ing) }}"
                       class="text-blue-600 hover:underline">Editar</a>
                    <form action="{{ route('admin.ingredientes.destroy', $ing) }}"
                          method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="text-red-600 hover:underline"
                                onclick="return confirm('¿Eliminar este ingrediente?')">
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
