<div class="modal fade akila-product-modal" id="productModal-{{ $producto->id }}" tabindex="-1"
    aria-labelledby="productModal-title-{{ $producto->id }}" data-menu-config="{{ json_encode($menuConfig, JSON_UNESCAPED_UNICODE) }}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content overflow-hidden rounded-[1.25rem] border-0 bg-white">
            <div class="modal-header flex items-start justify-between gap-4 bg-[var(--akila-black)] p-5 text-white sm:px-7">
                <div class="min-w-0">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider">{{ $producto->personalizable ? 'A tu elección' : 'Composición establecida' }}</p>
                    <h2 id="productModal-title-{{ $producto->id }}" class="mb-0 break-words text-xl font-bold sm:text-2xl">{{ $producto->nombre }}</h2>
                </div>
                <button type="button" data-bs-dismiss="modal" aria-label="Cerrar" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-white/30 text-2xl">×</button>
            </div>
            <div class="modal-body min-w-0 p-5 sm:p-7">
                <div class="grid min-w-0 gap-6 md:grid-cols-2">
                    <div class="min-w-0">
                        <x-store.catalog-image :path="$producto->imagen" :alt="$producto->nombre" class="rounded-2xl" />
                        <p class="mt-4 text-sm leading-6 text-[var(--akila-gray-700)]">{{ strip_tags($producto->descripcion) }}</p>
                        <p class="text-sm font-bold">
                            {{ $menuConfig['quantity_label'] }}
                            @if($menuConfig['unit_type'] === 'pieza' && count($menuConfig['blocks']))
                                · {{ count($menuConfig['blocks']) }} rolls
                            @endif
                        </p>
                        @if($menuConfig['base'])<p class="text-sm">Base: {{ collect($menuConfig['base'])->pluck('nombre')->implode(', ') }}.</p>@endif
                        @foreach($menuConfig['preparations'] as $preparation)
                            <p class="mb-1 text-sm">{{ $preparation['nombre'] }}: {{ $preparation['piezas'] }} piezas en la promoción.</p>
                        @endforeach
                    </div>
                    <div class="min-w-0">
                        <form data-menu-form method="POST" action="{{ route('cart.add') }}" data-quote-url="{{ route('cart.quote') }}" data-cart-url="{{ route('cart.index') }}" id="form-{{ $producto->id }}" novalidate>
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $producto->id }}">
                            <input type="hidden" name="unidades" value="1">
                            <input type="hidden" name="version" value="2">
                            @foreach($menuConfig['blocks'] as $index => $block)
                                <section data-menu-block="{{ $block['id'] }}" data-block-label="{{ $block['label'] }}" class="mb-4 min-w-0 rounded-2xl border border-[var(--akila-gray-200)] p-4">
                                    <header class="mb-4 flex flex-wrap items-center justify-between gap-2"><h3 class="mb-0 text-base font-bold">{{ $block['label'] }} <span class="font-normal">· {{ $block['quantity_label'] }}</span></h3><span data-block-status class="text-xs font-semibold text-[var(--akila-gray-700)]">{{ $producto->personalizable ? 'Pendiente' : 'Incluido' }}</span></header>
                                    <input type="hidden" name="blocks[{{ $index }}][block_id]" value="{{ $block['id'] }}">
                                    @foreach($block['groups'] as $group)
                                        @include('components.menu-option-group', ['group' => $group, 'scope' => 'blocks['.$index.'][option_ids][]', 'blockKey' => $block['id'], 'editable' => $producto->personalizable])
                                    @endforeach
                                </section>
                            @endforeach
                            @foreach($menuConfig['groups'] as $group)
                                @include('components.menu-option-group', ['group' => $group, 'scope' => 'option_ids[]', 'blockKey' => 'product', 'editable' => $producto->personalizable])
                            @endforeach
                            @if(collect($menuConfig['base'])->contains('removable', true))
                                <fieldset class="mb-4"><legend class="text-sm font-bold">Si prefieres quitar la base</legend>
                                    @foreach($menuConfig['base'] as $base)
                                        @if($base['removable'])<label class="flex min-h-12 cursor-pointer items-center gap-3 text-sm"><input type="checkbox" name="removed_base_ids[]" value="{{ $base['id'] }}" class="h-5 w-5 rounded text-red-600">Sin {{ $base['nombre'] }}</label>@endif
                                    @endforeach
                                </fieldset>
                            @endif
                        </form>
                        <div data-menu-error role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" hidden></div>
                        <section class="rounded-2xl bg-[var(--akila-cream)] p-4" aria-label="Tu selección">
                            <h3 class="mb-3 text-base font-bold">Tu selección</h3>
                            <ul data-menu-summary class="mb-4 list-none space-y-2 p-0 text-sm"></ul>
                            <dl class="mb-0 space-y-2 text-sm" aria-live="polite"><div class="flex justify-between gap-3"><dt>Base</dt><dd data-price-base class="mb-0">—</dd></div><div class="flex justify-between gap-3"><dt>Recargos</dt><dd data-price-extras class="mb-0">—</dd></div><div class="flex justify-between gap-3 border-t border-[var(--akila-gray-200)] pt-3 text-lg font-bold"><dt>Total</dt><dd data-price-total class="mb-0">Completa tu selección</dd></div></dl>
                        </section>
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-nowrap gap-2 border-t border-[var(--akila-gray-200)] px-5 py-4 sm:px-7">
                <button type="button" data-bs-dismiss="modal" class="min-h-12 rounded-xl border border-[var(--akila-gray-200)] px-4 text-sm font-semibold">Volver</button>
                <button data-menu-add disabled type="submit" form="form-{{ $producto->id }}" class="min-h-12 flex-grow rounded-xl bg-[var(--akila-red)] px-4 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">Agregar al carrito</button>
            </div>
        </div>
    </div>
</div>
