@extends('layouts.app')

@section('title', 'Sushi artesanal en Mariquina')

@section('content')
    @php
        // Inicio uses Route::view; these catalog reads stay local to the Home.
        $homeCategories = \App\Models\Categoria::query()
            ->addSelect(['cover_image' => \App\Models\Producto::query()
                ->select('imagen')->whereColumn('categoria_id', 'categorias.id')
                ->whereNotNull('imagen')->where('imagen', '!=', '')->orderBy('id')->limit(1)])
            ->orderBy('nombre')->get();
        // There is no featured/popularity field: use a stable catalog selection.
        $homeProducts = \App\Models\Producto::with('categoria')->orderBy('nombre')->orderBy('id')->limit(4)->get();
        $customizableProduct = \App\Models\Producto::with('categoria')->where('personalizable', true)->orderBy('nombre')->first();
        $customizationUrl = $customizableProduct?->categoria?->slug
            ? route('menu', ['filter' => $customizableProduct->categoria->slug]) : route('menu');
        $benefits = [
            ['Ingredientes seleccionados', 'El sabor comienza en cada ingrediente.', 'M12 21v-9M12 16C4 16 3 10 3 5c6 0 9 3 9 8M12 12c0-6 4-9 9-9 0 6-3 10-9 10'],
            ['Preparado al momento', 'Cada roll, hecho para tu pedido.', 'M12 8v4l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0'],
            ['Delivery y retiro', 'Elige cómo disfrutar tu sushi.', 'M3 6h11v11H3zM14 10h4l3 4v3h-7M8 19a2 2 0 1 1-4 0 2 2 0 0 1 4 0M20 19a2 2 0 1 1-4 0 2 2 0 0 1 4 0'],
            ['Compra segura', 'Paga a través de Mercado Pago.', 'M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6zM8 12l3 3 5-6'],
        ];
    @endphp

    <div class="space-y-16 pb-4 sm:space-y-20 lg:space-y-24">
        <section aria-labelledby="home-hero-title" class="grid min-w-0 items-center gap-8 lg:grid-cols-[minmax(0,9fr)_minmax(0,11fr)] lg:gap-10">
            <div class="min-w-0 py-2 lg:py-10">
                <p class="mb-5 flex items-center gap-3 text-xs font-bold uppercase tracking-[0.18em] text-[var(--akila-gray-700)]"><span class="h-0.5 w-7 shrink-0 bg-[var(--akila-red)]" aria-hidden="true"></span>Sushi Akila · Mariquina</p>
                <h1 id="home-hero-title" class="mb-5 text-[clamp(2.5rem,6vw,5rem)] font-black leading-[1.04] tracking-[-0.05em]">Sushi hecho<br>con <span class="text-[var(--akila-red)]">actitud.</span></h1>
                <p class="mb-3 text-xl font-semibold tracking-tight sm:text-2xl">Sabor japonés en Mariquina.</p>
                <p class="mb-7 max-w-md text-base leading-7 text-[var(--akila-gray-700)]">Rolls preparados al momento, combinaciones para todos los gustos y el sello de Sushi Akila.</p>
                <a href="{{ route('menu') }}" class="inline-flex min-h-14 w-full items-center justify-center gap-5 rounded-xl bg-[var(--akila-red)] px-7 py-4 font-bold text-white no-underline transition-colors hover:bg-[var(--akila-black)] hover:text-white sm:w-auto">Ver la carta <span aria-hidden="true">→</span></a>
            </div>
            <figure class="relative m-0 min-w-0 overflow-hidden rounded-[2rem] bg-[var(--akila-charcoal)]">
                <img src="{{ asset('img/slide1sushi.png') }}" alt="Selección de nigiris y makis sobre platos de cerámica" width="1123" height="734" fetchpriority="high" loading="eager" class="aspect-[4/3] w-full object-cover lg:aspect-[5/6]">
                <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 to-transparent px-6 pb-6 pt-16 text-white sm:px-8 sm:pb-8"><span class="mb-1 block text-[10px] font-bold uppercase tracking-[0.2em] text-white/80">El sello Akila</span><span class="text-xl font-bold tracking-tight">Hecho al momento.<br>Disfrutado a tu manera.</span></figcaption>
            </figure>
        </section>

        <section aria-label="La experiencia Sushi Akila" class="grid grid-cols-2 gap-x-5 gap-y-7 rounded-2xl border border-[var(--akila-gray-200)] bg-white p-5 sm:p-8 lg:grid-cols-4 lg:gap-8">
            @foreach($benefits as [$title, $description, $path])
                <div class="min-w-0">
                    <svg class="mb-4 h-7 w-7 text-[var(--akila-red)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $path }}" /></svg>
                    <h2 class="mb-2 text-sm font-bold sm:text-base">{{ $title }}</h2>
                    <p class="mb-0 text-sm leading-6 text-[var(--akila-gray-700)]">{{ $description }}</p>
                </div>
            @endforeach
        </section>

        <section aria-labelledby="home-categories-title">
            <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
                <div><p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-[var(--akila-red)]">Encuentra tu combinación</p><h2 id="home-categories-title" class="mb-0 text-3xl font-bold tracking-tight sm:text-4xl">Explora nuestra carta</h2></div>
                <a href="{{ route('menu') }}" class="inline-flex min-h-11 items-center gap-3 font-semibold text-[var(--akila-gray-900)] underline decoration-[var(--akila-red)] underline-offset-4 hover:text-[var(--akila-red)]">Ver toda la carta <span aria-hidden="true">→</span></a>
            </div>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
                @forelse($homeCategories as $category)
                    <x-store.category-card :category="$category" />
                @empty
                    <p class="col-span-full mb-0 rounded-2xl border border-[var(--akila-gray-200)] bg-white p-6 text-[var(--akila-gray-700)]">Pronto encontrarás aquí las categorías de nuestra carta.</p>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="home-products-title">
            <div class="mb-7"><p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-[var(--akila-red)]">Una selección de nuestra carta</p><h2 id="home-products-title" class="mb-0 text-3xl font-bold tracking-tight sm:text-4xl">Los favoritos</h2></div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @forelse($homeProducts as $product)
                    <x-store.product-card :product="$product" />
                @empty
                    <p class="col-span-full mb-0 rounded-2xl border border-[var(--akila-gray-200)] bg-white p-6 text-[var(--akila-gray-700)]">Estamos preparando nuestra selección. Explora la carta para conocer los productos disponibles.</p>
                @endforelse
            </div>
        </section>

        @if($customizableProduct)
            <section aria-labelledby="home-customize-title" class="grid min-w-0 overflow-hidden rounded-[2rem] border border-[var(--akila-gray-200)] bg-white md:grid-cols-2">
                <div class="relative min-w-0 bg-[var(--akila-charcoal)]"><img src="{{ asset('img/slide3.jpg') }}" alt="Rolls envueltos en salmón, servidos con jengibre y wasabi" width="1920" height="600" loading="lazy" decoding="async" class="h-full min-h-64 w-full object-cover object-left md:min-h-96"></div>
                <div class="min-w-0 p-6 sm:p-10 lg:p-12">
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-[var(--akila-red)]">Tu combinación, tu sello</p>
                    <h2 id="home-customize-title" class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl">Prepáralo a tu gusto</h2>
                    <p class="mb-6 text-base leading-7 text-[var(--akila-gray-700)]">Elige tus ingredientes y crea la combinación que más te guste.</p>
                    <ol aria-label="Opciones según el producto elegido" class="mb-6 grid list-none grid-cols-2 gap-3 p-0">
                        @foreach(['Envoltura', 'Proteína', 'Vegetales', 'Extras'] as $option)
                            <li class="flex min-w-0 items-center gap-2 rounded-xl bg-[var(--akila-cream)] px-3 py-3 text-sm font-semibold"><span class="text-xs text-[var(--akila-red)]" aria-hidden="true">0{{ $loop->iteration }}</span>{{ $option }}</li>
                        @endforeach
                    </ol>
                    <p class="mb-6 text-xs leading-5 text-[var(--akila-gray-700)]">Las opciones disponibles dependen del producto que elijas en la carta.</p>
                    <a href="{{ $customizationUrl }}" class="inline-flex min-h-14 w-full items-center justify-center gap-4 rounded-xl bg-[var(--akila-black)] px-6 py-4 font-bold text-white no-underline hover:bg-[var(--akila-red)] hover:text-white sm:w-auto">Personalizar <span aria-hidden="true">→</span></a>
                </div>
            </section>
        @endif

        {{-- No promotions: their public purchase flow is not connected. --}}

        <section aria-labelledby="home-brand-title" class="relative isolate overflow-hidden rounded-[2rem] bg-[var(--akila-black)] px-6 py-14 text-white sm:p-12 lg:p-16">
            <img src="{{ asset('img/logo/logo-icon-light-transparent.png') }}" alt="" aria-hidden="true" width="320" height="320" loading="lazy" class="pointer-events-none absolute -right-8 bottom-0 -z-10 h-64 w-64 object-contain opacity-[0.06] sm:right-8 sm:h-80 sm:w-80">
            <div class="mb-6 h-1 w-12 bg-[var(--akila-red)]" aria-hidden="true"></div>
            <p class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-[var(--akila-gray-200)]">El espíritu Sushi Akila</p>
            <h2 id="home-brand-title" class="mb-5 max-w-3xl text-3xl font-bold leading-tight tracking-tight sm:text-4xl lg:text-5xl">Precisión en cada corte.<br>Sabor en cada <span class="text-[var(--akila-orange)]">roll.</span></h2>
            <p class="mb-0 max-w-lg text-base leading-7 text-[var(--akila-gray-200)]">Preparación artesanal, atención al detalle y pasión por el sabor japonés.</p>
        </section>

        <section aria-labelledby="home-local-title" class="grid items-start gap-8 border-t border-[var(--akila-gray-200)] pt-10 md:grid-cols-2">
            <div><p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-[var(--akila-red)]">Estamos cerca</p><h2 id="home-local-title" class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl">Sabor japonés.<br>Corazón de Mariquina.</h2><p class="mb-0 text-base leading-7 text-[var(--akila-gray-700)]">Encuéntranos en San José de la Mariquina. Disfruta Sushi Akila con delivery o retiro.</p></div>
            <div class="rounded-2xl border border-[var(--akila-gray-200)] bg-white p-6 sm:p-8"><h3 class="mb-5 text-lg font-bold">Planifica tu próximo pedido</h3><dl class="mb-0 space-y-5 text-sm"><div><dt class="mb-1 text-xs font-semibold uppercase tracking-wider text-[var(--akila-gray-700)]">Dirección</dt><dd class="mb-0">Fernando Luis Manss 542</dd></div><div><dt class="mb-1 text-xs font-semibold uppercase tracking-wider text-[var(--akila-gray-700)]">Teléfono</dt><dd class="mb-0"><a href="tel:+56945053594" class="font-semibold text-[var(--akila-gray-900)] underline decoration-[var(--akila-red)] underline-offset-4 hover:text-[var(--akila-red)]">+56 9 4505 3594</a></dd></div><div><dt class="mb-1 text-xs font-semibold uppercase tracking-wider text-[var(--akila-gray-700)]">Entrega</dt><dd class="mb-0">Delivery y retiro en tienda</dd></div></dl></div>
        </section>
    </div>
@endsection
