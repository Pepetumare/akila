@props(['disabled' => false])
<input @disabled($disabled) {{ $attributes->class(['block min-h-12 w-full rounded-xl border border-[var(--akila-gray-200)] bg-white px-3 py-3 text-base text-[var(--akila-gray-900)] focus:border-[var(--akila-red)] focus:ring-[var(--akila-red)]']) }}>
