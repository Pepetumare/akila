@php
    $configuration = app(\App\Services\ProductPricingService::class)->defaults($producto);
    $assignedProteins = array_map(fn ($id) => ['id' => $id], $configuration['Proteínas']);
    $assignedVeggies = array_map(fn ($id) => ['id' => $id], $configuration['vegetales']);
    $envolturasDisponibles = $producto->ingredientes->where('tipo', 'envoltura');
@endphp

<div class="modal fade akila-product-modal" id="productModal-{{ $producto->id }}" tabindex="-1" x-data
    aria-labelledby="productModal-title-{{ $producto->id }}" aria-describedby="productModal-description-{{ $producto->id }}"
    data-proteins='@json($assignedProteins)' data-vegetables='@json($assignedVeggies)'
    data-all='@json($allIngredients)' data-base-price="{{ $producto->precio }}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content overflow-hidden rounded-[1.25rem] border-0 bg-white">
            <div class="modal-header flex items-start justify-between gap-4 border-b border-[var(--akila-gray-200)] bg-[var(--akila-black)] px-[1.25rem] py-[1rem] text-white sm:px-7">
                <div class="min-w-0">
                    <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[var(--akila-gray-200)]">Prepáralo a tu gusto</p>
                    <h2 id="productModal-title-{{ $producto->id }}" class="mb-0 break-words text-xl font-bold sm:text-2xl">{{ $producto->nombre }}</h2>
                </div>
                <button type="button" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-white/30 text-2xl text-white hover:bg-white/10" data-bs-dismiss="modal" aria-label="Cerrar personalización de {{ $producto->nombre }}"><span aria-hidden="true">×</span></button>
            </div>

            <div class="modal-body min-w-0 p-[1.25rem] sm:p-7">
                <p id="productModal-description-{{ $producto->id }}" class="mb-6 text-sm leading-6 text-[var(--akila-gray-700)]">Personaliza tus ingredientes y revisa el precio antes de agregar al carrito.</p>
                <div class="grid min-w-0 gap-6 md:grid-cols-2">
                    <div class="min-w-0">
                        <x-store.catalog-image :path="$producto->imagen" :alt="$producto->nombre" class="rounded-2xl" />
                        @if(filled($producto->descripcion))<p class="mb-0 mt-4 break-words text-sm leading-6 text-[var(--akila-gray-700)]">{{ strip_tags($producto->descripcion) }}</p>@endif
                    </div>
                    <div class="min-w-0 space-y-5">
                        <div class="rounded-2xl bg-[var(--akila-cream)] p-[1.25rem]">
                            <p class="mb-1 text-xs font-semibold text-[var(--akila-gray-700)]">Precio por unidad</p>
                            <p id="price-{{ $producto->id }}" role="status" aria-live="polite" aria-atomic="true" class="mb-0 break-words text-2xl font-extrabold text-[var(--akila-gray-900)]"><x-store.price :amount="$producto->precio" /></p>
                        </div>
                        <section aria-labelledby="prot-title-{{ $producto->id }}">
                            <h3 id="prot-title-{{ $producto->id }}" class="mb-2 text-base font-bold">Proteínas</h3>
                            <p class="mb-3 text-xs text-[var(--akila-gray-700)]">Rolls de 10 cortes. Cambia una proteína para ver las opciones.</p>
                            <ul id="prot-list-{{ $producto->id }}" class="m-0 list-none space-y-2 p-0"></ul>
                        </section>
                        <section aria-labelledby="veg-title-{{ $producto->id }}">
                            <h3 id="veg-title-{{ $producto->id }}" class="mb-2 text-base font-bold">Vegetales</h3>
                            <p class="mb-3 text-xs text-[var(--akila-gray-700)]">Rolls de 10 cortes. Elige la combinación que prefieras.</p>
                            <ul id="veg-list-{{ $producto->id }}" class="m-0 list-none space-y-2 p-0"></ul>
                        </section>
                        <div id="swap-box-{{ $producto->id }}" class="hidden flex flex-wrap items-end gap-2 rounded-xl border border-[var(--akila-gray-200)] bg-[var(--akila-gray-100)] p-3">
                            <div class="min-w-0 flex-grow basis-full">
                                <label for="swap-select-{{ $producto->id }}" class="mb-2 block text-xs font-bold">Elige el ingrediente</label>
                                <select id="swap-select-{{ $producto->id }}" class="min-h-11 w-full rounded-lg border-[var(--akila-gray-200)] bg-white text-sm"><option value="">-- elige --</option></select>
                            </div>
                            <button type="button" class="min-h-11 rounded-lg bg-[var(--akila-black)] px-4 py-2 text-sm font-bold text-white hover:bg-[var(--akila-red)]" @click="window.confirmSwap({{ (int) $producto->id }})">Confirmar cambio</button>
                            <button type="button" class="min-h-11 rounded-lg border border-[var(--akila-gray-200)] bg-white px-4 py-2 text-sm font-semibold" @click="window.cancelSwap({{ (int) $producto->id }})">Cancelar</button>
                        </div>
                    </div>
                </div>

                <form id="form-{{ $producto->id }}" action="{{ route('cart.add') }}" method="POST" class="mt-6 space-y-5 border-t border-[var(--akila-gray-200)] pt-6">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $producto->id }}">
                    <input type="hidden" name="unidades" value="1">
                    {{-- The existing personalization module inserts the normalized ingredient inputs here. --}}
                    @if($envolturasDisponibles->isNotEmpty())
                        <fieldset>
                            <legend class="mb-3 text-base font-bold">Elige una envoltura</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach($envolturasDisponibles as $env)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="envoltura_id" value="{{ $env->id }}" @checked($configuration['envoltura_id'] === $env->id) required class="peer sr-only">
                                        <span class="inline-flex min-h-12 items-center rounded-full border border-[var(--akila-gray-200)] px-4 py-3 text-sm font-semibold text-[var(--akila-gray-700)] peer-checked:border-[var(--akila-black)] peer-checked:bg-[var(--akila-black)] peer-checked:text-white peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[var(--akila-orange)]">{{ $env->nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                    <fieldset>
                        <legend class="mb-2 text-base font-bold">Últimos detalles</legend>
                        <div class="flex flex-col gap-1 sm:flex-row sm:gap-6">
                            <label class="inline-flex min-h-12 cursor-pointer items-center gap-3 text-sm"><input type="checkbox" name="cream_cheese" value="1" class="h-5 w-5 rounded border-gray-300 text-red-600">Sin queso crema</label>
                            <label class="inline-flex min-h-12 cursor-pointer items-center gap-3 text-sm"><input type="checkbox" name="scallions" value="1" class="h-5 w-5 rounded border-gray-300 text-red-600">Sin cebollín</label>
                        </div>
                    </fieldset>
                </form>
            </div>

            <div class="modal-footer flex-nowrap gap-2 border-t border-[var(--akila-gray-200)] bg-white px-[1.25rem] py-[1rem] sm:px-7">
                <button type="button" data-bs-dismiss="modal" class="min-h-12 rounded-xl border border-[var(--akila-gray-200)] px-3 py-3 text-sm font-semibold">Volver</button>
                <button type="submit" form="form-{{ $producto->id }}" class="min-h-12 flex-grow rounded-xl bg-[var(--akila-red)] px-4 py-3 text-sm font-bold text-white hover:bg-[var(--akila-black)] sm:flex-grow-0">Agregar al carrito</button>
            </div>
        </div>
    </div>
</div>
