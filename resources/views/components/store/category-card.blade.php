@props(['category', 'image' => null])

<a href="{{ route('menu', ['filter' => $category->slug]) }}"
    aria-label="Explorar {{ $category->nombre }} en la carta"
    {{ $attributes->class(['group relative block min-h-36 min-w-0 overflow-hidden rounded-2xl border border-[var(--akila-gray-200)] bg-[var(--akila-charcoal)] text-white no-underline transition-colors md:hover:border-[var(--akila-orange)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[var(--akila-orange)] motion-reduce:transition-none']) }}>
    <x-store.catalog-image :path="$image ?? $category->cover_image ?? null" :alt="'Selección de ' . $category->nombre" />
    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent" aria-hidden="true"></div>
    <div class="absolute inset-x-0 bottom-0 flex min-h-16 items-end justify-between gap-3 p-4 text-white sm:p-5">
        <h3 class="mb-0 min-w-0 break-words text-base font-bold leading-snug sm:text-lg">{{ $category->nombre }}</h3>
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/30 text-lg" aria-hidden="true">↗</span>
    </div>
</a>
