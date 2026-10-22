<x-guest-layout>
    <p class="mb-2 text-xs font-bold uppercase tracking-widest text-[var(--akila-red)]">Tu espacio Akila</p>
    <h1 class="mb-3 text-3xl font-bold tracking-tight">Crea tu cuenta</h1>
    <p class="mb-7 text-sm leading-6 text-[var(--akila-gray-700)]">Ten tus datos y el historial de tus pedidos a mano.</p>
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <div><label for="name" class="mb-2 block text-sm font-semibold">Nombre</label><x-store.form-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" :aria-invalid="$errors->has('name') ? 'true' : 'false'" aria-describedby="name-error" /><div id="name-error"><x-input-error :messages="$errors->get('name')" class="mt-2" /></div></div>
        <div><label for="email" class="mb-2 block text-sm font-semibold">Correo electrónico</label><x-store.form-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" :aria-invalid="$errors->has('email') ? 'true' : 'false'" aria-describedby="email-error" /><div id="email-error"><x-input-error :messages="$errors->get('email')" class="mt-2" /></div></div>
        <div><label for="password" class="mb-2 block text-sm font-semibold">Contraseña</label><x-store.form-input id="password" type="password" name="password" required autocomplete="new-password" :aria-invalid="$errors->has('password') ? 'true' : 'false'" aria-describedby="password-error" /><div id="password-error"><x-input-error :messages="$errors->get('password')" class="mt-2" /></div></div>
        <div><label for="password_confirmation" class="mb-2 block text-sm font-semibold">Confirmar contraseña</label><x-store.form-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" :aria-invalid="$errors->has('password_confirmation') ? 'true' : 'false'" aria-describedby="password-confirmation-error" /><div id="password-confirmation-error"><x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" /></div></div>
        <x-store.form-button class="w-full">Crear cuenta</x-store.form-button>
    </form>
    <p class="mb-0 mt-6 border-t border-[var(--akila-gray-200)] pt-6 text-center text-sm text-[var(--akila-gray-700)]">¿Ya tienes cuenta? <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-bold text-[var(--akila-gray-900)] underline underline-offset-4">Ingresar</a></p>
</x-guest-layout>
