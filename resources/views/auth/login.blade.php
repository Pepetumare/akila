<x-guest-layout>
    <p class="mb-2 text-xs font-bold uppercase tracking-widest text-[var(--akila-red)]">Bienvenido de vuelta</p>
    <h1 class="mb-3 text-3xl font-bold tracking-tight">Ingresa a tu cuenta</h1>
    <p class="mb-7 text-sm leading-6 text-[var(--akila-gray-700)]">Revisa tus pedidos y administra tus datos.</p>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="mb-2 block text-sm font-semibold">Correo electrónico</label>
            <x-store.form-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" :aria-invalid="$errors->has('email') ? 'true' : 'false'" aria-describedby="email-error" />
            <div id="email-error"><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
        </div>
        <div>
            <label for="password" class="mb-2 block text-sm font-semibold">Contraseña</label>
            <x-store.form-input id="password" type="password" name="password" required autocomplete="current-password" :aria-invalid="$errors->has('password') ? 'true' : 'false'" aria-describedby="password-error" />
            <div id="password-error"><x-input-error :messages="$errors->get('password')" class="mt-2" /></div>
        </div>
        <label for="remember_me" class="inline-flex min-h-11 cursor-pointer items-center gap-3 text-sm text-[var(--akila-gray-700)]"><input id="remember_me" type="checkbox" name="remember" class="h-5 w-5 rounded border-gray-300 text-red-600 focus:ring-red-600">Recordarme</label>
        <x-store.form-button class="w-full">Ingresar</x-store.form-button>
        @if(Route::has('password.request'))<a href="{{ route('password.request') }}" class="flex min-h-11 items-center justify-center text-sm text-[var(--akila-gray-700)] underline underline-offset-4 hover:text-[var(--akila-red)]">¿Olvidaste tu contraseña?</a>@endif
    </form>
    <p class="mb-0 mt-6 border-t border-[var(--akila-gray-200)] pt-6 text-center text-sm text-[var(--akila-gray-700)]">¿Primera vez aquí? <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center font-bold text-[var(--akila-gray-900)] underline underline-offset-4">Crea tu cuenta</a></p>
</x-guest-layout>
