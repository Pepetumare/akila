<fieldset data-menu-group="{{ $group['id'] }}" data-min="{{ $group['min'] }}" data-max="{{ $group['max'] }}" class="mb-4 last:mb-0">
    <legend class="mb-2 text-sm font-bold">{{ $group['nombre'] }}</legend>
    @if($editable)
        <p class="mb-3 text-xs text-[var(--akila-gray-700)]">{{ $group['min'] === 1 && $group['max'] === 1 ? 'Elige una sola opción entre las categorías de este grupo.' : 'Elige entre '.$group['min'].' y '.$group['max'].' opciones.' }}</p>
        @foreach(collect($group['options'])->groupBy(fn ($option) => $group['categorize'] ? $option['tipo'] : '') as $type => $options)
            @if($type)<p class="mb-2 mt-3 text-xs font-semibold uppercase tracking-wide text-[var(--akila-gray-700)]">{{ ['proteina' => 'Proteínas', 'vegetal' => 'Vegetales', 'base' => 'Base', 'topping' => 'Toppings', 'envoltura' => 'Envolturas', 'salsa' => 'Salsas'][$type] ?? ucfirst($type) }}</p>@endif
            <div class="grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach($options as $option)
                    <label class="min-w-0 cursor-pointer">
                        <input data-menu-option type="{{ $group['selection'] === 'single' ? 'radio' : 'checkbox' }}" name="{{ $group['selection'] === 'single' ? 'selection-'.$blockKey.'-'.$group['id'] : $scope }}" data-option-id="{{ $option['id'] }}" value="{{ $option['id'] }}" class="peer sr-only">
                        <span class="flex min-h-12 min-w-0 items-center justify-between gap-2 rounded-xl border border-[var(--akila-gray-200)] px-3 py-3 text-sm font-semibold peer-checked:!border-[var(--akila-red)] peer-checked:bg-[var(--akila-red)] peer-checked:text-white peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[var(--akila-orange)]"><span class="break-words">{{ $option['nombre'] }}</span>@if((float) $option['surcharge'] > 0)<span class="shrink-0 text-xs">+${{ number_format((float) $option['surcharge'], fmod((float) $option['surcharge'], 1) ? 2 : 0, ',', '.') }}</span>@endif</span>
                    </label>
                @endforeach
            </div>
        @endforeach
        @if($group['min'] === 0 && $group['selection'] === 'single')<button type="button" data-clear-group class="mt-2 min-h-11 text-xs font-semibold underline">Sin elección adicional</button>@endif
    @else
        <p class="mb-0 text-sm">{{ collect($group['options'])->pluck('nombre')->implode(', ') }}</p>
    @endif
</fieldset>
