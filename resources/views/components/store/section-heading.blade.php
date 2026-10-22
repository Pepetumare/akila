@props(['title', 'eyebrow'=>null])
<div {{ $attributes->class(['mb-8']) }}>
@if($eyebrow)<p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-[var(--akila-red)]">{{ $eyebrow }}</p>@endif
<h2 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ $title }}</h2>
@if($slot->isNotEmpty())<div class="mt-3 max-w-2xl text-base leading-7 text-[var(--akila-gray-700)]">{{ $slot }}</div>@endif
</div>