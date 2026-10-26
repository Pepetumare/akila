@php
    $fixedCompositions = collect($menuConfig['blocks'])->groupBy(fn ($block) => json_encode(array_column($block['groups'], 'options')));
    $hasBlocks = $producto->personalizable && count($menuConfig['blocks']);
    $repetitionRules = $producto->reglas->where('activo', true)->where('tipo', \App\Enums\TipoReglaPromocion::MaxRepeticiones);
    $ruleOptions = $producto->bloques->flatMap(fn ($b) => $b->gruposOpciones->flatMap(fn ($g) => $g->opciones))->merge($producto->gruposOpciones->flatMap(fn ($g) => $g->opciones));
    $fillingOptionIds = collect($menuConfig['blocks'])->flatMap(fn ($b) => $b['groups'])->merge($menuConfig['groups'])->where('categorize', true)->flatMap(fn ($g) => array_column($g['options'], 'id'));
    $displayLimits = $producto->personalizable ? $repetitionRules->map(fn ($r) => [
        'max' => (int) $r->valor,
        'option_ids' => $ruleOptions->filter(fn ($o) => $r->opcion_id ? $o->id === $r->opcion_id : ($o->ingrediente_id === $r->ingrediente_id && $fillingOptionIds->contains($o->id)))->pluck('id')->unique()->values()->all(),
    ])->values()->all() : [];
@endphp
<div class="modal fade akila-product-modal akila-configurator" id="productModal-{{ $producto->id }}" tabindex="-1"
    role="dialog" aria-modal="true" aria-labelledby="productModal-title-{{ $producto->id }}" data-menu-limits="{{ json_encode($displayLimits) }}" data-menu-config="{{ json_encode($menuConfig, JSON_UNESCAPED_UNICODE) }}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header akila-config-header">
                <p class="akila-product-type">{{ $producto->categoria?->nombre }}<span>{{ $producto->personalizable ? 'A tu elección' : 'Composición establecida' }}</span></p>
                <h2 id="productModal-title-{{ $producto->id }}">{{ $producto->nombre }}</h2>
                <button type="button" data-bs-dismiss="modal" aria-label="Cerrar detalle del producto" class="akila-close">×</button>
            </div>
            <div class="modal-body akila-config-body" tabindex="0" aria-label="Detalle y configuración del producto">
                <div class="akila-config-layout">
                <aside class="akila-product-overview" aria-label="Información del producto">
                    <x-store.catalog-image :path="$producto->imagen" :alt="$producto->nombre" class="akila-config-image rounded-2xl" />
                    <h3 class="akila-overview-title">{{ $producto->nombre }}</h3>
                    <p class="akila-quantity"><x-store.icon name="portion" />{{ $menuConfig['quantity_label'] }}@if($menuConfig['unit_type'] === 'pieza' && count($menuConfig['blocks'])) · {{ count($menuConfig['blocks']) }} rolls @endif</p>
                    @if($menuConfig['base'])
                        <div class="akila-base"><x-store.icon name="ingredient" /><p><strong>{{ $producto->personalizable ? 'Base incluida' : 'Ingredientes incluidos' }}</strong>{{ collect($menuConfig['base'])->pluck('nombre')->implode(' + ') }}</p></div>
                    @endif
                    <section class="akila-selection" aria-label="Resumen de selección">
                        <h3>{{ $producto->personalizable ? 'Tu selección' : 'Tu producto' }}</h3>
                        <ul data-menu-summary></ul>
                        <p data-menu-sauces hidden></p>
                    </section>
                    <section data-included-sauces class="akila-included-sauces" hidden><h4>Incluye salsas</h4><ul></ul><p>Incluidas. No intercambiables.</p></section>
                    @if($producto->personalizable && $repetitionRules->isNotEmpty())
                        <details class="akila-rules"><summary>Reglas de esta elección</summary><ul>
                            @foreach($repetitionRules as $rule)
                                @php($ruleOption = $ruleOptions->first(fn ($o) => $rule->opcion_id ? $o->id === $rule->opcion_id : $o->ingrediente_id === $rule->ingrediente_id))
                                @if($ruleOption)<li>{{ $ruleOption->ingrediente?->nombre ?? $ruleOption->nombre }}: máximo {{ (int) $rule->valor }} {{ (int) $rule->valor === 1 ? 'vez' : 'veces' }}.</li>@endif
                            @endforeach
                        </ul></details>
                    @endif
                    <dl class="akila-price-breakdown"><div><dt>Precio base</dt><dd data-price-base>${{ number_format((float) $producto->precio, 0, ',', '.') }}</dd></div><div><dt>Adicionales</dt><dd data-price-extras>$0</dd></div><div><dt>Total</dt><dd data-price-total>${{ number_format((float) $producto->precio, 0, ',', '.') }}</dd></div></dl>
                    @if($producto->descripcion)<details class="akila-product-description"><summary>Descripción del producto</summary><p>{{ strip_tags($producto->descripcion) }}</p></details>@endif
                </aside>
                <div class="akila-config-panel" tabindex="0" role="region" aria-label="{{ $producto->personalizable ? 'Configuración del producto' : 'Composición y adicionales' }}">
                    <div class="akila-config-heading"><div><h3>{{ $producto->personalizable ? 'Configura tu elección' : 'Tu producto, al detalle' }}</h3><p class="akila-config-quantity">{{ $menuConfig['quantity_label'] }}</p></div><span data-menu-progress role="status"></span></div>
                    <progress data-menu-progress-bar max="1" value="0" aria-label="Elecciones completadas" hidden></progress>
                    @if($hasBlocks)
                        <nav class="akila-unit-nav" aria-label="Ir a una unidad">@foreach($menuConfig['blocks'] as $block)<button type="button" data-go-block="{{ $block['id'] }}" aria-controls="menu-block-{{ $producto->id }}-{{ $block['id'] }}"><span data-nav-status aria-hidden="true">{{ $loop->iteration }}</span><span>{{ $block['label'] }}</span></button>@endforeach</nav>
                    @elseif(count($menuConfig['blocks']))
                        <section class="akila-fixed-composition"><h4>Composición incluida</h4><p>Preparada con los ingredientes establecidos.</p>
                            @foreach($fixedCompositions as $blocks)
                                <div class="akila-composition-row"><span>{{ collect($blocks->first()['groups'])->flatMap(fn ($g) => array_column($g['options'], 'nombre'))->implode(' · ') }}</span><small>{{ $blocks->count() }} {{ $menuConfig['unit_type'] === 'pieza' ? ($blocks->count() === 1 ? 'roll' : 'rolls') : 'unidades' }}</small><strong>{{ $blocks->sum(fn ($b) => $b['pieces'] ?? 1) }} {{ $menuConfig['unit_type'] === 'pieza' ? 'piezas' : 'unidades' }}</strong></div>
                            @endforeach
                        </section>
                    @elseif(!$producto->personalizable)
                        <section class="akila-fixed-composition"><h4>Listo para disfrutar</h4><p>{{ collect($menuConfig['base'])->pluck('nombre')->implode(' · ') }}</p><span class="akila-included">Composición incluida</span></section>
                    @endif
                    @if(!$producto->personalizable && $menuConfig['preparations'])
                        <section class="akila-fixed-preparations"><h4>Preparación</h4>@foreach($menuConfig['preparations'] as $preparation)<p>{{ $preparation['piezas'] }} piezas · {{ $preparation['nombre'] }}</p>@endforeach</section>
                    @endif
                    @if($producto->personalizable && $menuConfig['base'])
                        <div class="akila-inline-base"><strong>Base incluida</strong><p>{{ collect($menuConfig['base'])->pluck('nombre')->implode(' + ') }}</p></div>
                    @endif
                    <form data-menu-form method="POST" action="{{ route('cart.add') }}" data-quote-url="{{ route('cart.quote') }}" data-cart-url="{{ route('cart.index') }}" id="form-{{ $producto->id }}" novalidate>
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $producto->id }}">
                        <input type="hidden" name="unidades" value="1">
                        <input type="hidden" name="version" value="2">
                        @if($hasBlocks)
                            @foreach($menuConfig['blocks'] as $index => $block)
                                <details data-menu-block="{{ $block['id'] }}" data-block-label="{{ $block['label'] }}" id="menu-block-{{ $producto->id }}-{{ $block['id'] }}" class="akila-unit" @if($index === 0) open @endif>
                                    <summary><span><strong>{{ $block['label'] }}</strong><span class="akila-unit-quantity">— {{ $block['quantity_label'] }}</span><span data-block-preview class="akila-unit-preview">Elige tus ingredientes</span></span><span data-block-status class="akila-unit-status">Pendiente</span></summary>
                                    <div class="akila-unit-content">
                                        <input type="hidden" name="blocks[{{ $index }}][block_id]" value="{{ $block['id'] }}">
                                        @foreach($block['groups'] as $group)
                                            @include('components.menu-option-group', ['group' => $group, 'scope' => 'blocks['.$index.'][option_ids][]', 'blockKey' => $block['id'], 'editable' => true])
                                        @endforeach
                                        <div class="akila-unit-actions">
                                            @if($index > 0)<button type="button" data-go-block="{{ $menuConfig['blocks'][$index - 1]['id'] }}">← Anterior</button>@endif
                                            @if($index < count($menuConfig['blocks']) - 1)<button type="button" data-go-block="{{ $menuConfig['blocks'][$index + 1]['id'] }}">Siguiente →</button>@endif
                                        </div>
                                    </div>
                                </details>
                            @endforeach
                        @endif
                        @foreach($menuConfig['groups'] as $group)
                            @include('components.menu-option-group', ['group' => $group, 'scope' => 'option_ids[]', 'blockKey' => 'product', 'editable' => $producto->personalizable || $group['min'] === 0])
                        @endforeach
                        @if(collect($menuConfig['base'])->contains('removable', true))
                            <details class="akila-base-removal"><summary>¿Prefieres quitar algo de la base?</summary><fieldset><legend class="sr-only">Ingredientes de base que deseas quitar</legend>
                                @foreach($menuConfig['base'] as $base)
                                    @if($base['removable'])<label><input type="checkbox" name="removed_base_ids[]" value="{{ $base['id'] }}">Sin {{ $base['nombre'] }}</label>@endif
                                @endforeach
                            </fieldset></details>
                        @endif
                    </form>
                    <section data-mobile-summary class="akila-mobile-summary" aria-label="Tu selección actual"><h4>Tu selección</h4><ul></ul></section>

                </div>
                </div>
            </div>
            <div class="modal-footer akila-config-footer">
                <div data-menu-error role="alert" hidden></div>
                <button type="button" data-bs-dismiss="modal" class="akila-back">Volver</button>
                <div class="akila-footer-total"><span>Total</span><strong data-price-total aria-live="polite">${{ number_format((float) $producto->precio, 0, ',', '.') }}</strong><small class="akila-currency">CLP</small><small data-quote-status role="status">Completa tu selección</small></div>
                <button data-menu-add disabled type="submit" form="form-{{ $producto->id }}" class="akila-add">Agregar al carrito <span aria-hidden="true">→</span></button>
            </div>
        </div>
    </div>
</div>
