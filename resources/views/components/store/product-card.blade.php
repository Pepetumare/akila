@props(['product', 'modal' => false])

@php
    $category = $product->categoria;
    $customizationUrl = $category?->slug ? route('menu', ['filter' => $category->slug]) : route('menu');
@endphp

<article {{ $attributes->class(['akila-product-card flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-[var(--akila-gray-200)] bg-white transition-colors md:hover:border-[var(--akila-gray-500)] motion-reduce:transition-none']) }}>
    <div class="relative">
        <x-store.catalog-image :path="$product->imagen" :alt="$product->nombre" />
        <span class="akila-card-badge">{{ $product->personalizable ? 'A tu gusto' : 'Fijo' }}</span>
    </div>
    <div class="akila-card-body">
        @if($category)
            <p class="mb-2 break-words text-xs font-semibold uppercase tracking-wider text-[var(--akila-gray-700)]">{{ $category->nombre }}</p>
        @endif
        <h3 class="mb-2 break-words text-xl font-bold tracking-tight text-[var(--akila-gray-900)]">{{ $product->nombre }}</h3>
        <ul class="akila-card-metadata" aria-label="Resumen del producto">
            @foreach(\App\View\MenuCardSummary::lines($product) as $line)<li><x-store.icon :name="$loop->first ? 'portion' : 'ingredient'" /><span>{{ $line }}</span></li>@endforeach
        </ul>
        <x-store.price :amount="$product->precio" class="akila-card-price" />

        @if($modal)
            <button type="button" x-data @click="window.openProductModal({{ (int) $product->id }}, @js(route('producto.modal', $product->id)), $el)"
                data-product-modal-id="productModal-{{ $product->id }}"
                aria-haspopup="dialog" aria-controls="productModal-{{ $product->id }}" aria-label="{{ $product->personalizable ? 'Personalizar' : 'Ver detalle de' }} {{ $product->nombre }}"
                @class(['akila-card-cta', 'is-customizable' => $product->personalizable])>
                {{ $product->personalizable ? 'Personalizar' : 'Ver y agregar' }} <span aria-hidden="true">→</span>
            </button>
        @elseif($product->personalizable)
            {{-- Home links to the existing menu customization flow. --}}
            <a href="{{ $customizationUrl }}" aria-label="Personalizar {{ $product->nombre }} en la carta"
                class="inline-flex min-h-12 w-full items-center justify-center gap-3 rounded-xl bg-[var(--akila-black)] px-4 py-3 text-center text-sm font-bold text-white no-underline transition-colors hover:bg-[var(--akila-red)] hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[var(--akila-orange)] motion-reduce:transition-none">
                Personalizar en la carta <span aria-hidden="true">→</span>
            </a>
        @else
            {{-- cart.add validates the product and calls ProductPricingService. No client prices. --}}
            <form method="POST" action="{{ route('cart.add') }}" class="mt-auto">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="unidades" value="1">
                <button type="submit" aria-label="Agregar {{ $product->nombre }} al carrito"
                    class="inline-flex min-h-12 w-full items-center justify-center gap-3 rounded-xl bg-[var(--akila-red)] px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-[var(--akila-black)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[var(--akila-orange)] motion-reduce:transition-none">
                    Agregar al carrito <x-store.icon name="cart" class="shrink-0" />
                </button>
            </form>
        @endif
    </div>
</article>
