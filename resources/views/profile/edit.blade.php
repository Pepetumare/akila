@extends('profile.layout')
@section('panel-content')
    @if(session('success'))<div role="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><p class="mb-2 font-bold">Revisa los datos indicados.</p><ul class="mb-0 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PATCH')
        <section aria-labelledby="personal-data-title" class="rounded-2xl border border-[var(--akila-gray-200)] bg-white p-6 sm:p-8">
            <h2 id="personal-data-title" class="mb-2 text-2xl font-bold tracking-tight">Datos personales</h2><p class="mb-6 text-sm leading-6 text-[var(--akila-gray-700)]">Mantén actualizada tu información de contacto.</p>
            <div class="mb-6 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
                @if($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar))
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="Foto de perfil de {{ $user->name }}" width="80" height="80" loading="lazy" class="h-20 w-20 shrink-0 rounded-full border border-[var(--akila-gray-200)] object-cover">
                @else
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-[var(--akila-cream)]" aria-hidden="true"><x-store.icon name="account" class="h-8 w-8 text-[var(--akila-gray-700)]" /></div>
                @endif
                <div class="min-w-0"><label for="avatar" class="mb-2 block text-sm font-semibold">Foto de perfil</label><input id="avatar" type="file" name="avatar" accept="image/*" class="block min-h-11 w-full max-w-full text-sm text-[var(--akila-gray-700)]" aria-describedby="avatar-help"><p id="avatar-help" class="mb-0 mt-1 text-xs text-[var(--akila-gray-700)]">Imagen de hasta 2 MB.</p><x-input-error :messages="$errors->get('avatar')" class="mt-2" /></div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="name" class="mb-2 block text-sm font-semibold">Nombre</label><x-store.form-input id="name" name="name" type="text" :value="old('name', $user->name)" required autocomplete="name" /><x-input-error :messages="$errors->get('name')" class="mt-2" /></div>
                <div><label for="email" class="mb-2 block text-sm font-semibold">Correo electrónico</label><x-store.form-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="email" /><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
                <div><label for="phone" class="mb-2 block text-sm font-semibold">Teléfono</label><x-store.form-input id="phone" name="phone" type="text" :value="old('phone', $user->phone)" autocomplete="tel" /><x-input-error :messages="$errors->get('phone')" class="mt-2" /></div>
            </div>
        </section>
        <section id="account-password" aria-labelledby="password-title" class="scroll-mt-28 rounded-2xl border border-[var(--akila-gray-200)] bg-white p-6 sm:p-8">
            <h2 id="password-title" class="mb-2 text-2xl font-bold tracking-tight">Contraseña</h2><p class="mb-6 text-sm leading-6 text-[var(--akila-gray-700)]">Deja estos campos vacíos para conservar tu contraseña actual.</p>
            <div class="grid gap-5 sm:grid-cols-2"><div><label for="password" class="mb-2 block text-sm font-semibold">Nueva contraseña</label><x-store.form-input id="password" name="password" type="password" autocomplete="new-password" /><x-input-error :messages="$errors->get('password')" class="mt-2" /></div><div><label for="password_confirmation" class="mb-2 block text-sm font-semibold">Confirmar contraseña</label><x-store.form-input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" /><x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" /></div></div>
        </section>
        <div class="flex justify-end"><x-store.form-button class="w-full sm:w-auto">Guardar cambios</x-store.form-button></div>
    </form>
@endsection
