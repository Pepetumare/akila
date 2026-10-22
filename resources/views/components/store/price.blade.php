@props(['amount'])

{{-- Presentation only: amounts come from the catalog or a server quote. --}}
<span {{ $attributes->class(['inline-flex flex-wrap items-baseline gap-x-1.5 gap-y-1']) }}>
    <span class="font-extrabold tracking-tight">${{ number_format((float) $amount, 0, ',', '.') }}</span>
    <span class="text-xs font-medium tracking-normal text-[var(--akila-gray-700)]">CLP</span>
</span>
