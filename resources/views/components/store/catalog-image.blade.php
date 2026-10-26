@props(['path' => null, 'alt'])

@php
    $hasImage = filled($path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
@endphp

<div {{ $attributes->class(['relative aspect-[4/3] w-full overflow-hidden bg-[var(--akila-charcoal)]']) }}>
    @if($hasImage)
        <img src="{{ asset('storage/' . $path) }}" alt="{{ $alt }}" width="640" height="480"
            loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover">
    @else
        <div role="img" aria-label="{{ $alt }}; fotografía no disponible" class="akila-image-fallback absolute inset-0 flex items-center justify-center">
            <img src="{{ asset('img/logo/logo-dark-transparent.png') }}" alt="" width="160" height="80"
                loading="lazy" decoding="async" class="h-auto w-36 max-w-full object-contain">
            <span class="akila-fallback-caption" aria-hidden="true">MARIQUINA</span>
        </div>
    @endif
</div>
