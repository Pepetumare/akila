@extends('layouts.app')
@section('title', 'Nuestra carta')
@section('content')
    @php
        $activeCategory = $categorias->firstWhere('slug', request('filter'));
        $activeSort = in_array(request('sort'), ['name_asc', 'name_desc', 'price_asc', 'price_desc'], true) ? request('sort') : 'name_asc';
        $search = is_string(request('q')) ? request('q') : '';
        $filters = array_filter(['sort' => $activeSort, 'q' => $search]);
    @endphp
    <div class="akila-menu">
        <header class="akila-menu-hero">
            <div class="akila-hero-copy"><p class="akila-eyebrow">SUSHI AKILA · MARIQUINA</p><h1>Nuestra carta</h1><p>Encuentra tu próximo favorito. Sushi preparado al momento con el sello Akila.</p></div>
            <img class="akila-hero-photo" src="{{ asset('img/slide3.jpg') }}" alt="" width="1920" height="600" fetchpriority="high">
        </header>
        <section class="akila-category-panel" aria-labelledby="menu-categories-title">
        <h2 id="menu-categories-title">Explora por categoría</h2>
        <nav class="akila-categories" aria-label="Categorías de la carta">
            <a href="{{ route('menu', $filters) }}" @if(!request()->filled('filter')) aria-current="page" @endif>Toda la carta <span>({{ $categorias->sum('disponibles_count') }})</span></a>
            @foreach($categorias as $categoria)
                <a href="{{ route('menu', ['filter' => $categoria->slug] + $filters) }}" @if(request('filter') === $categoria->slug) aria-current="page" @endif>{{ $categoria->nombre }} <span>({{ $categoria->disponibles_count }})</span></a>
            @endforeach
        </nav>
        </section>
        <section aria-labelledby="menu-products-title">
            <div class="akila-menu-toolbar">
                <div><h2 id="menu-products-title">{{ $activeCategory?->nombre ?? 'Todos nuestros productos' }}</h2><p>{{ $productos->total() }} {{ $productos->total() === 1 ? 'producto disponible' : 'productos disponibles' }}</p></div>
                <form method="GET" action="{{ route('menu') }}" class="akila-menu-filters">
                    @if(request()->filled('filter'))<input type="hidden" name="filter" value="{{ request('filter') }}">@endif
                    <label><span>Buscar en la carta</span><input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Buscar producto..."></label>
                    <label><span>Ordenar por</span><select name="sort" id="menu-sort">
                        @foreach(['name_asc' => 'Nombre: A a Z', 'name_desc' => 'Nombre: Z a A', 'price_asc' => 'Menor precio', 'price_desc' => 'Mayor precio'] as $value => $label)
                            <option value="{{ $value }}" @selected($activeSort === $value)>{{ $label }}</option>
                        @endforeach
                    </select></label>
                    <button type="submit">Aplicar</button>
                </form>
            </div>
            <div class="akila-product-grid">
                @forelse($productos as $producto)
                    <x-store.product-card :product="$producto" :modal="true" />
                @empty
                    <div class="akila-menu-empty"><h3>No hay productos en esta selección</h3><p>Prueba con otra búsqueda o categoría.</p><a href="{{ route('menu') }}">Ver toda la carta</a></div>
                @endforelse
            </div>
            @if($productos->hasPages()){{ $productos->links('components.store.menu-pagination') }}@endif
        </section>
    </div>
@endsection
