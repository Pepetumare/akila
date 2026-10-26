@php
    $automatic = count($group['options']) === 1 && $group['min'] === 1 && $group['max'] >= 1;
    $types = collect($group['options'])->groupBy(fn ($option) => $group['categorize'] ? $option['tipo'] : '');
    $tabbed = $group['categorize'] && $types->count() > 1 && !$automatic && $editable;
    $tabKey = 'option-types-'.$producto->id.'-'.$blockKey.'-'.$group['id'];
    $collapsible = $blockKey !== 'product' && $group['min'] === 0 && $editable;
    $typeNames = ['proteina' => 'Proteínas', 'vegetal' => 'Vegetales', 'base' => 'Base', 'topping' => 'Toppings', 'envoltura' => 'Envolturas', 'salsa' => 'Salsas'];
@endphp
@if(count($group['options']))
@if($collapsible)<details class="akila-optional-group"><summary>{{ $group['nombre'] }} <span>Opcional</span></summary>@endif
<fieldset data-menu-group="{{ $group['id'] }}" data-min="{{ $group['min'] }}" data-max="{{ $group['max'] }}" class="akila-option-group">
    <legend>{{ $group['categorize'] ? ($group['min'] === 1 && $group['max'] === 1 ? 'Elige 1 ingrediente' : 'Elige tus ingredientes') : $group['nombre'] }}</legend>
    @if($automatic || !$editable)
        <p class="akila-automatic">{{ collect($group['options'])->pluck('nombre')->implode(' · ') }} <span class="akila-included">Incluido</span></p>
        @if($editable && $automatic)<input data-menu-option type="checkbox" name="{{ $scope }}" value="{{ $group['options'][0]['id'] }}" checked hidden>@endif
    @else
        <p class="akila-group-hint">{{ $group['min'] === 1 && $group['max'] === 1 ? '1 opción · Obligatorio' : ($group['min'] === 0 ? 'Opcional · Hasta '.$group['max'].' '.($group['max'] === 1 ? 'opción' : 'opciones') : 'Elige entre '.$group['min'].' y '.$group['max'].' opciones') }}</p>
        @if($tabbed)
            <div class="akila-option-tabs" role="tablist" aria-label="Tipos de ingrediente">
                @foreach($types as $type => $options)
                    <button type="button" role="tab" id="{{ $tabKey }}-{{ $type }}" data-option-tab="{{ $type }}" aria-controls="{{ $tabKey }}-panel-{{ $type }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}">{{ $typeNames[$type] ?? ucfirst($type) }}</button>
                @endforeach
            </div>
        @endif
        @foreach($types as $type => $options)
            <div @if($tabbed) role="tabpanel" id="{{ $tabKey }}-panel-{{ $type }}" aria-labelledby="{{ $tabKey }}-{{ $type }}" data-option-panel="{{ $type }}" @if(!$loop->first) hidden @endif @endif>
                @if(!$tabbed && $type)<p class="akila-option-type">{{ $typeNames[$type] ?? ucfirst($type) }}</p>@endif
                <div class="akila-options-grid">
                    @foreach($options as $option)
                        <label class="akila-option-label">
                            <input data-menu-option type="{{ $group['selection'] === 'single' ? 'radio' : 'checkbox' }}" name="{{ $group['selection'] === 'single' ? 'selection-'.$blockKey.'-'.$group['id'] : $scope }}" data-option-id="{{ $option['id'] }}" value="{{ $option['id'] }}" class="sr-only">
                            <span class="akila-option-card"><x-store.icon :name="$type === 'vegetal' ? 'leaf' : 'ingredient'" class="akila-option-icon" /><span class="akila-option-copy">{{ $option['nombre'] }}<small data-option-limit class="akila-option-limit"></small></span><span class="akila-option-price">{{ (float) $option['surcharge'] > 0 ? '+$'.number_format((float) $option['surcharge'], fmod((float) $option['surcharge'], 1) ? 2 : 0, ',', '.') : 'Incluido' }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
        @if($group['min'] === 0 && $group['selection'] === 'single')<button type="button" data-clear-group class="akila-clear-option">Sin elección adicional</button>@endif
    @endif
</fieldset>
@if($collapsible)</details>@endif
@endif
