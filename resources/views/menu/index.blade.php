@extends('layouts.app')

@section('content')
    <div class="container py-4 mx-auto">

        {{-- ===== Botón hamburguesa (móvil) ===== --}}
        <div class="flex justify-between items-center mb-3 md:hidden">
            <h2 class="text-lg font-bold">Categorías</h2>
            <button id="toggleMenu" class="p-2 rounded bg-red-600 text-white">
                <span id="openTxt">☰ Categorías</span>
                <span id="closeTxt" class="hidden">✕ Cerrar</span>
            </button>
        </div>

        {{-- ===== Navbar categorías ===== --}}
        <nav id="catBar" class="hidden md:block bg-white shadow rounded p-4 mb-4 transition-all duration-300">

            <ul class="flex md:flex-row flex-col gap-2 overflow-auto">
                <li>
                    <a href="{{ route('menu') }}"
                        class="block px-3 py-2 rounded text-sm font-medium {{ request('filter') ? 'bg-gray-100 text-gray-800 hover:bg-red-100' : 'bg-red-600 text-white' }}">
                        Todas las categorías
                    </a>
                </li>
                @foreach ($categorias as $categoria)
                    <li>
                        <a href="{{ route('menu', ['filter' => $categoria->slug]) }}"
                            class="block px-3 py-2 rounded text-sm font-medium {{ request('filter') == $categoria->slug ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-red-100' }}">
                            {{ $categoria->nombre }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- ===== Grid productos ===== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach ($productos as $producto)
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition flex flex-col">
                    <img src="{{ $producto->imagen ? asset('storage/' . $producto->imagen) : asset('img/no_disponible.png') }}"
                        class="h-48 w-full object-cover" alt="{{ $producto->nombre }}">

                    <div class="p-4 flex-grow flex flex-col">
                        <h3 class="text-lg font-semibold mb-1">{{ $producto->nombre }}</h3>
                        <p class="text-sm text-gray-600 mb-3 flex-grow">
                            {{ Str::limit($producto->descripcion, 60) }}
                        </p>
                        <p class="text-xl font-bold text-red-600 mb-4">
                            ${{ number_format($producto->precio, 0, ',', '.') }} CLP
                        </p>

                        {{-- Agregar rápido --}}
                        <form action="{{ route('cart.add') }}" method="POST" class="mb-2">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $producto->id }}">
                            <input type="hidden" name="unidades" value="1">

                            @if ($producto->personalizable)
                                {{-- proteínas por defecto --}}
                                @foreach ($producto->ingredientes->where('tipo', 'proteina') as $ing)
                                    <input type="hidden" name="Proteínas[]" value="{{ $ing->id }}">
                                @endforeach
                                {{-- vegetales por defecto --}}
                                @foreach ($producto->ingredientes->where('tipo', 'vegetal') as $ing)
                                    <input type="hidden" name="vegetales[]" value="{{ $ing->id }}">
                                @endforeach
                            @endif

                            <button class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700 transition">
                                Agregar al carrito
                            </button>
                        </form>

                        {{-- Personalizar --}}
                        @if ($producto->personalizable)
                            <button
                                class="w-full border border-red-600 text-red-600 py-2 rounded hover:bg-red-50 transition"
                                data-bs-toggle="modal" data-bs-target="#productModal-{{ $producto->id }}"
                                onclick="initModal({{ $producto->id }})">
                                Personalizar producto
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Paginación --}}
        <div class="mt-6">
            {{ $productos->withQueryString()->links() }}
        </div>
    </div>
@endsection


@section('modals')
    @foreach ($productos->where('personalizable', true) as $producto)
        @php
            $howMany = fn($p) => isset($p->cantidad)
                ? (int) $p->cantidad
                : (isset($p->cantidad_permitida)
                    ? (int) $p->cantidad_permitida
                    : 1);

            $assignedProteins = [];
            foreach ($producto->ingredientes->where('tipo', 'proteina') as $ing) {
                $rolls = intdiv($howMany($ing->pivot), 10);
                for ($i = 0; $i < $rolls; $i++) {
                    $assignedProteins[] = ['id' => $ing->id, 'nombre' => $ing->nombre];
                }
            }
            $assignedVeggies = [];
            foreach ($producto->ingredientes->where('tipo', 'vegetal') as $ing) {
                $rolls = intdiv($howMany($ing->pivot), 10);
                for ($i = 0; $i < $rolls; $i++) {
                    $assignedVeggies[] = ['id' => $ing->id, 'nombre' => $ing->nombre];
                }
            }
        @endphp

        <div class="modal fade" id="productModal-{{ $producto->id }}" tabindex="-1"
            data-proteins='@json($assignedProteins)' data-vegetables='@json($assignedVeggies)'
            data-all='@json($wrappers->merge($proteins)->merge($vegetables))' data-base-price="{{ $producto->precio }}">
            <div class="modal-dialog modal-xl">
                <div class="modal-content rounded-xl shadow-lg">

                    {{-- Header --}}
                    <div class="flex justify-between items-start bg-red-600 text-white px-6 py-4">
                        <h5 class="text-xl font-semibold">{{ $producto->nombre }}</h5>
                        <button type="button" class="btn-close opacity-90 invert" data-bs-dismiss="modal"></button>
                    </div>

                    {{-- Body --}}
                    <div class="grid md:grid-cols-2 gap-6 p-6">
                        <img src="{{ $producto->imagen ? asset('storage/' . $producto->imagen) : asset('img/no_disponible.png') }}"
                            class="w-full rounded-lg object-cover" alt="{{ $producto->nombre }}">

                        <div class="flex flex-col gap-6">
                            {{-- Precio --}}
                            <div>
                                <span class="text-gray-500">Precio total</span>
                                <p id="price-{{ $producto->id }}" class="text-3xl font-extrabold text-red-600">
                                    ${{ number_format($producto->precio, 0, ',', '.') }} CLP
                                </p>
                            </div>

                            {{-- Proteínas --}}
                            <div>
                                <h6 class="font-semibold mb-2">Proteínas (rolls ×10 cortes)</h6>
                                <ul id="prot-list-{{ $producto->id }}" class="space-y-2"></ul>
                            </div>
                            {{-- Vegetales --}}
                            <div>
                                <h6 class="font-semibold mb-2">Vegetales (rolls ×10 cortes)</h6>
                                <ul id="veg-list-{{ $producto->id }}" class="space-y-2"></ul>
                            </div>

                            {{-- Selector swap --}}
                            <div id="swap-box-{{ $producto->id }}" class="hidden mt-2 flex items-center gap-2">
                                <select id="swap-select-{{ $producto->id }}"
                                    class="form-select flex-1 border-gray-300 rounded-lg">
                                    <option value="">-- elige --</option>
                                </select>
                                <button class="btn btn-success btn-sm"
                                    onclick="confirmSwap({{ $producto->id }})">OK</button>
                                <button class="btn btn-secondary btn-sm"
                                    onclick="cancelSwap({{ $producto->id }})">Cancelar</button>
                            </div>
                        </div>
                    </div>

                    {{-- Footer + Form --}}
                    <form id="form-{{ $producto->id }}" action="{{ route('cart.add') }}" method="POST"
                        class="bg-gray-50 px-6 py-4 flex flex-col gap-6">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $producto->id }}">
                        <input type="hidden" name="unidades" value="1">
                        <input type="hidden" id="adjust-{{ $producto->id }}" name="price_adjustment" value="0">

                        {{-- hidden inputs inyectados por JS --}}

                        {{-- ==== Selector de Bases ==== --}}
                        <div>
                            <h6 class="font-semibold mb-2">Elige una Base</h6>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($wrappers as $w)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="base_id" value="{{ $w->id }}" required
                                            class="sr-only peer">
                                        <span
                                            class="px-3 py-1 rounded-full border border-red-600 text-red-600 text-sm transition peer-checked:bg-red-600 peer-checked:text-white">
                                            {{ $w->nombre }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- ==== Sin queso / Sin cebollín ==== --}}
                        <div class="flex flex-col gap-2">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="cream_cheese" value="1" class="h-4 w-4 text-red-600">
                                Sin queso crema
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="scallions" value="1" class="h-4 w-4 text-red-600">
                                Sin cebollín
                            </label>
                        </div>

                        {{-- ==== Botón Agregar ==== --}}
                        <button type="submit" class="btn btn-primary w-full">
                            Agregar al carrito
                        </button>
                    </form>

                </div>
            </div>
        </div>
    @endforeach
@endsection


@push('styles')
    <style>
        .chip {
            @apply inline-flex items-center gap-2 bg-gray-100 px-3 py-1 rounded-full text-sm;
        }

        .old-swap {
            @apply text-gray-500 underline;
        }
    </style>
@endpush


@push('scripts')
    <script>
        const swapState = {};

        function initModal(prodId) {
            const modal = document.getElementById(`productModal-${prodId}`);
            if (!swapState[prodId]) {
                const protInit = JSON.parse(modal.dataset.proteins).map(o => o.id);
                const vegInit = JSON.parse(modal.dataset.vegetables).map(o => o.id);
                swapState[prodId] = {
                    proteina: protInit,
                    vegetal: vegInit,
                    basePrice: +modal.dataset.basePrice,
                    adjust: 0,
                    changes: {
                        proteina: {},
                        vegetal: {}
                    }
                };
            }
            refreshUI(prodId);
        }

        function startSwap(prodId, idx, group) {
            const modal = document.getElementById(`productModal-${prodId}`);
            const all = JSON.parse(modal.dataset.all);
            const sel = document.getElementById(`swap-select-${prodId}`);
            const st = swapState[prodId];

            // cuenta actual
            const counts = st[group].reduce((a, id) => (a[id] = (a[id] || 0) + 1, a), {});
            st.swapping = {
                idx,
                group
            };
            sel.innerHTML = '<option value="">-- elige --</option>';
            all.filter(i => i.tipo === group && i.id !== st[group][idx])
                .filter(i => {
                    const cnt = counts[i.id] || 0;
                    const isSalmon = i.nombre.toLowerCase().includes('salm');
                    return isSalmon ? cnt < 1 : cnt < 2;
                })
                .forEach(i => sel.insertAdjacentHTML(
                    'beforeend',
                    `<option value="${i.id}">${i.nombre}</option>`
                ));
            document.getElementById(`swap-box-${prodId}`).classList.remove('hidden');
        }

        function confirmSwap(prodId) {
            const sel = document.getElementById(`swap-select-${prodId}`);
            const newId = +sel.value;
            if (!newId) return;
            const st = swapState[prodId];
            const {
                idx,
                group
            } = st.swapping;
            st[group][idx] = newId;
            st.changes[group][idx] = st.changes[group][idx] === undefined ?
                sel.options[sel.selectedIndex].text :
                st.changes[group][idx];
            st.adjust++;
            refreshUI(prodId);
            cancelSwap(prodId);
        }

        function cancelSwap(prodId) {
            document.getElementById(`swap-box-${prodId}`).classList.add('hidden');
        }

        function refreshUI(prodId) {
            const modal = document.getElementById(`productModal-${prodId}`);
            const all = JSON.parse(modal.dataset.all);
            const st = swapState[prodId];
            const form = document.getElementById(`form-${prodId}`);

            ['proteina', 'vegetal'].forEach(group => {
                const list = document.getElementById(
                    `${group==='proteina'?'prot':'veg'}-list-${prodId}`
                );
                list.innerHTML = '';
                st[group].forEach((id, idx) => {
                    const ing = all.find(i => i.id === id) || {
                        nombre: id
                    };
                    const changed = st.changes[group][idx] !== undefined;
                    const oldName = changed ? st.changes[group][idx] : '';
                    list.insertAdjacentHTML('beforeend', `
                    <li class="chip">
                        ${changed ? `<span class="old-swap">${oldName}</span> ` : ''}
                        ${ing.nombre} <span class="text-xs">×10</span>
                        <button type="button"
                                class="btn btn-sm btn-outline-warning"
                                onclick="startSwap(${prodId},${idx},'${group}')">
                            Cambiar
                        </button>
                    </li>`);
                });
            });

            // inputs ocultos
            form.querySelectorAll('.ing-input').forEach(el => el.remove());
            st.proteina.forEach(id =>
                form.insertAdjacentHTML('beforeend',
                    `<input type="hidden" name="Proteínas[]" value="${id}" class="ing-input">`
                )
            );
            st.vegetal.forEach(id =>
                form.insertAdjacentHTML('beforeend',
                    `<input type="hidden" name="vegetales[]" value="${id}" class="ing-input">`
                )
            );

            // precio
            document.getElementById(`adjust-${prodId}`).value = st.adjust * 1000;
            document.getElementById(`price-${prodId}`).textContent =
                `$${(st.basePrice + st.adjust*1000).toLocaleString('de-DE')} CLP`;
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggleMenu');
            const catBar = document.getElementById('catBar');
            const openTxt = document.getElementById('openTxt');
            const closeTxt = document.getElementById('closeTxt');

            if (toggleButton && catBar) {
                toggleButton.addEventListener('click', function() {
                    catBar.classList.toggle('hidden');
                    openTxt.classList.toggle('hidden');
                    closeTxt.classList.toggle('hidden');
                });
            }
        });
    </script>
@endpush
