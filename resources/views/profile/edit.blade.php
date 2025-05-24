@extends('profile.layout')

@section('panel-content')
    <h2 class="text-xl font-bold mb-6">🧾 Editar Perfil</h2>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">

        @csrf
        <div>
            <label class="block text-sm font-semibold">Foto de perfil</label>

            <div class="flex items-center gap-4 my-2">
                <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : asset('images/default-avatar.png') }}"
                    alt="Avatar" class="w-16 h-16 rounded-full object-cover border">

                <input type="file" name="avatar" accept="image/*" class="text-sm text-gray-600">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold">Nombre</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                class="w-full border px-3 py-2 rounded" required>
        </div>

        <div>
            <label class="block text-sm font-semibold">Correo</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                class="w-full border px-3 py-2 rounded" required>
        </div>

        <div>
            <label class="block text-sm font-semibold">Teléfono</label>
            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                class="w-full border px-3 py-2 rounded">
        </div>

        <div class="pt-6 border-t">
            <label class="block text-sm font-semibold">Nueva contraseña</label>
            <input type="password" name="password" class="w-full border px-3 py-2 rounded">
        </div>

        <div>
            <label class="block text-sm font-semibold">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" class="w-full border px-3 py-2 rounded">
        </div>

        <div class="text-right">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                Guardar cambios
            </button>
        </div>
    </form>
@endsection
