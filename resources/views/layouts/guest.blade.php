<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b0f12">
    <title>Tu cuenta · Sushi Akila</title>
    <link rel="icon" href="{{ asset('img/logo/logo-icon.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/custom.css'])
</head>
<body class="akila-store min-h-screen bg-[var(--akila-cream)] font-sans text-[var(--akila-gray-900)] antialiased">
    <header class="bg-[var(--akila-black)] text-white">
        <div class="akila-container flex min-h-20 items-center justify-between gap-4 py-4">
            <a href="{{ route('home') }}" class="rounded" aria-label="Sushi Akila, inicio"><img src="{{ asset('img/logo/logo-light-transparent.png') }}" alt="Sushi Akila" width="160" height="64" class="h-10 w-auto max-w-40 object-contain"></a>
            <a href="{{ route('menu') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-white no-underline hover:text-[var(--akila-orange)]">Volver a la carta →</a>
        </div>
    </header>
    <main id="main-content" class="akila-container grid min-w-0 gap-10 py-10 sm:py-16 lg:grid-cols-2 lg:items-center lg:gap-16">
        <div class="hidden lg:block">
            <p class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-[var(--akila-red)]">Sushi Akila · Mariquina</p>
            <p class="mb-5 text-5xl font-black leading-tight tracking-tight">Tu próximo favorito<br>te espera aquí.</p>
            <p class="max-w-md text-base leading-7 text-[var(--akila-gray-700)]">Sushi artesanal, preparado al momento. Tu cuenta, tus datos y tus pedidos en un solo lugar.</p>
            <div class="mt-8 h-1 w-12 rounded-full bg-[var(--akila-orange)]" aria-hidden="true"></div>
        </div>
        <div class="mx-auto w-full min-w-0 max-w-lg rounded-[1.25rem] border border-[var(--akila-gray-200)] bg-white p-6 sm:p-8">{{ $slot }}</div>
    </main>
    <footer class="akila-container pb-8 text-center text-xs text-[var(--akila-gray-700)]">&copy; {{ date('Y') }} Sushi Akila</footer>
</body>
</html>
