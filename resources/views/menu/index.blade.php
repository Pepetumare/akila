@extends('layouts.app')

@section('title', 'Nuestra carta')

@section('content')
    @php
        $activeCategory = $categorias->firstWhere('slug', request('filter'));
        $activeSort = in_array(request('sort'), ['name_asc', 'name_desc', 'price_asc', 'price_desc'], true)
            ? request('sort') : 'name_asc';
        // Reuse the categories already supplied to the page without per-card queries.
        $productos->each(fn ($product) => $product->setRelation('categoria', $categorias->firstWhere('id', $product->categoria_id)));
    @endphp

    <div class="min-w-0 space-y-8 sm:space-y-10">
        <header class="max-w-2xl">
            <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-[var(--akila-red)]">Sushi Akila · Mariquina</p>
            <h1 class="mb-4 text-4xl font-black tracking-tight sm:text-5xl">Nuestra carta</h1>
            <p class="mb-0 text-base leading-7 text-[var(--akila-gray-700)]">Encuentra tu próximo favorito. Elige una categoría y disfruta sushi preparado al momento, con el sello Akila.</p>
        </header>

        <section aria-labelledby="menu-categories-title" class="min-w-0 rounded-2xl border border-[var(--akila-gray-200)] bg-white p-4 sm:p-6">
            <h2 id="menu-categories-title" class="mb-4 text-sm font-bold">Explora por categoría</h2>
            <nav aria-label="Categorías de la carta" class="overflow-x-auto pb-2 lg:overflow-visible">
                <ul class="m-0 flex w-max min-w-full list-none gap-2 p-0 lg:w-auto lg:flex-wrap">
                    <li>
                        <a href="{{ route('menu', $activeSort !== 'name_asc' ? ['sort' => $activeSort] : []) }}"
                            @if(!request()->filled('filter')) aria-current="page" @endif
                            @class(['inline-flex min-h-12 items-center rounded-full border px-5 py-3 text-sm font-semibold no-underline transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--akila-orange)]',
                                'border-[var(--akila-black)] bg-[var(--akila-black)] text-white hover:text-white' => !request()->filled('filter'),
                                'border-[var(--akila-gray-200)] text-[var(--akila-gray-700)] hover:border-[var(--akila-red)] hover:text-[var(--akila-red)]' => request()->filled('filter')])>Toda la carta</a>
                    </li>
                    @foreach($categorias as $categoria)
                        <li>
                            <a href="{{ route('menu', ['filter' => $categoria->slug, 'sort' => $activeSort]) }}"
                                @if(request('filter') === $categoria->slug) aria-current="page" @endif
                                @class(['inline-flex min-h-12 items-center rounded-full border px-5 py-3 text-sm font-semibold no-underline transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--akila-orange)]',
                                    'border-[var(--akila-black)] bg-[var(--akila-black)] text-white hover:text-white' => request('filter') === $categoria->slug,
                                    'border-[var(--akila-gray-200)] text-[var(--akila-gray-700)] hover:border-[var(--akila-red)] hover:text-[var(--akila-red)]' => request('filter') !== $categoria->slug])>{{ $categoria->nombre }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </section>

        <section aria-labelledby="menu-products-title">
            <div class="mb-6 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                <div class="min-w-0">
                    <h2 id="menu-products-title" class="mb-2 break-words text-2xl font-bold tracking-tight">{{ $activeCategory?->nombre ?? 'Todos nuestros productos' }}</h2>
                    <p class="mb-0 text-sm text-[var(--akila-gray-700)]">{{ $productos->total() }} {{ $productos->total() === 1 ? 'producto disponible' : 'productos disponibles' }}</p>
                </div>
                <form method="GET" action="{{ route('menu') }}" class="flex flex-wrap items-end gap-2">
                    @if(request()->filled('filter'))<input type="hidden" name="filter" value="{{ request('filter') }}">@endif
                    <div class="min-w-0 flex-grow">
                        <label for="menu-sort" class="mb-2 block text-xs font-semibold text-[var(--akila-gray-700)]">Ordenar por</label>
                        <select id="menu-sort" name="sort" class="min-h-12 w-full rounded-xl border-[var(--akila-gray-200)] bg-white py-3 pl-3 pr-9 text-sm">
                            @foreach(['name_asc' => 'Nombre: A a Z', 'name_desc' => 'Nombre: Z a A', 'price_asc' => 'Menor precio', 'price_desc' => 'Mayor precio'] as $value => $label)
                                <option value="{{ $value }}" @selected($activeSort === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="min-h-12 rounded-xl bg-[var(--akila-black)] px-4 py-3 text-sm font-bold text-white hover:bg-[var(--akila-red)]">Aplicar</button>
                </form>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($productos as $producto)
                    <x-store.product-card :product="$producto" :modal="true" />
                @empty
                    <div class="col-span-full rounded-2xl border border-[var(--akila-gray-200)] bg-white px-6 py-14 text-center sm:py-20">
                        <x-store.icon name="menu" class="mx-auto mb-5 h-9 w-9 text-[var(--akila-red)]" />
                        <h3 class="mb-3 text-xl font-bold">{{ request()->filled('filter') ? 'No hay productos en esta selección' : 'Estamos preparando nuestra carta' }}</h3>
                        <p class="mx-auto mb-6 max-w-md text-sm leading-6 text-[var(--akila-gray-700)]">{{ request()->filled('filter') ? 'Prueba con otra categoría o vuelve a explorar toda la carta.' : 'Pronto encontrarás aquí los productos disponibles de Sushi Akila.' }}</p>
                        @if(request()->filled('filter'))<a href="{{ route('menu') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[var(--akila-red)] px-6 py-3 text-sm font-bold text-white no-underline hover:bg-[var(--akila-black)] hover:text-white">Ver toda la carta</a>@endif
                    </div>
                @endforelse
            </div>
            @if($productos->hasPages())<div class="mt-10">{{ $productos->withQueryString()->links() }}</div>@endif
        </section>
    </div>
@endsection
