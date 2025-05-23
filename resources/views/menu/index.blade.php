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
        <nav id="catBar" class="hidden md:block bg-white shadow rounded p-4 mb-4">
            <ul class="flex md:flex-row flex-col gap-2 overflow-auto">
                <li>
                    <a href="{{ route('menu') }}"
                        class="block px-3 py-2 rounded text-sm font-medium
                   {{ request('filter') ? 'bg-gray-100 text-gray-800 hover:bg-red-100' : 'bg-red-600 text-white' }}">
                        Todas las categorías
                    </a>
                </li>
                @foreach ($categorias as $categoria)
                    <li>
                        <a href="{{ route('menu', ['filter' => $categoria->slug]) }}"
                            class="block px-3 py-2 rounded text-sm font-medium
                       {{ request('filter') == $categoria->slug
                           ? 'bg-red-600 text-white'
                           : 'bg-gray-100 text-gray-800 hover:bg-red-100' }}">
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

                        <form action="{{ route('cart.add') }}" method="POST" class="mb-2">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $producto->id }}">
                            <input type="hidden" name="unidades" value="1">
                            <button class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700 transition">
                                Agregar al carrito
                            </button>
                        </form>

                        @if ($producto->personalizable)
                            <button
                                class="w-full border border-red-600 text-red-600 py-2 rounded hover:bg-red-50 transition"
                                data-bs-toggle="modal" data-bs-target="#productModal-{{ $producto->id }}">
                                Personalizar producto
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ===== Paginación ===== --}}
        <div class="mt-6">
            {{ $productos->withQueryString()->links() }}
        </div>
    </div>
@endsection


@section('modals')
    {{-- ========= MODAL ========= --}}
    @foreach ($productos->where('personalizable', true) as $producto)
        @php
            $assigned = $producto->ingredientes
                ->where('tipo', 'proteina')
                ->map(fn($i) => ['id' => $i->id, 'nombre' => $i->nombre])
                ->values();
        @endphp

        <div class="modal fade" id="productModal-{{ $producto->id }}" tabindex="-1"
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
                            alt="{{ $producto->nombre }}" class="w-full rounded-lg object-cover">

                        {{-- Controles --}}
                        <div class="flex flex-col gap-6">
                            {{-- Precio dinámico --}}
                            <div>
                                <span class="text-gray-500">Precio total</span>
                                <p id="price-{{ $producto->id }}" class="text-3xl font-extrabold text-red-600">
                                    ${{ number_format($producto->precio, 0, ',', '.') }} CLP
                                </p>
                            </div>

                            {{-- Proteínas --}}
                            <div>
                                <h6 class="font-semibold mb-2">Proteínas incluidas</h6>
                                <ul id="prot-list-{{ $producto->id }}" class="space-y-2">
                                    @foreach ($assigned as $p)
                                        <li class="chip" data-prot="{{ $p['id'] }}">
                                            {{ $p['nombre'] }}
                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                onclick="startSwap({{ $producto->id }}, {{ $p['id'] }})">
                                                Cambiar
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>

                                <div id="swap-box-{{ $producto->id }}" class="hidden mt-3 flex items-center gap-2">
                                    <select id="swap-select-{{ $producto->id }}"
                                        class="form-select flex-1 border-gray-300 rounded-lg"></select>
                                    <button class="btn btn-success btn-sm"
                                        onclick="confirmSwap({{ $producto->id }})">OK</button>
                                    <button class="btn btn-secondary btn-sm"
                                        onclick="cancelSwap({{ $producto->id }})">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer + FORM --}}
                    <form id="form-{{ $producto->id }}" action="{{ route('cart.add') }}" method="POST"
                        class="bg-gray-50 px-6 py-4 flex flex-col gap-6">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $producto->id }}">
                        <input type="hidden" name="unidades" value="1">
                        <input type="hidden" id="adjust-{{ $producto->id }}" name="price_adjustment" value="0">

                        {{-- hidden proteins (uno por id) --}}
                        @foreach ($assigned as $p)
                            <input type="hidden" name="Proteínas[]" value="{{ $p['id'] }}"
                                class="prot-input-{{ $producto->id }}">
                        @endforeach

                        {{-- Bases --}}
                        <div>
                            <h6 class="font-semibold mb-2">Elige una Base</h6>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($wrappers as $w)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="base_id" value="{{ $w->id }}"
                                            class="sr-only peer" required>
                                        <span
                                            class="px-3 py-1 rounded-full border border-red-600 text-red-600 text-sm transition
                         peer-checked:bg-red-600 peer-checked:text-white">
                                            {{ $w->nombre }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Sin queso / cebollín --}}
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

                        <button type="submit" class="btn btn-primary w-full">Agregar al carrito</button>
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
    </style>
@endpush



@push('scripts')
    <script>
        /* ===== Botón hamburguesa ===== */
        const toggleBtn = document.getElementById('toggleMenu');
        if (toggleBtn) {
            const catBar = document.getElementById('catBar');
            const openTxt = document.getElementById('openTxt');
            const closeTxt = document.getElementById('closeTxt');

            toggleBtn.addEventListener('click', () => {
                catBar.classList.toggle('hidden');
                openTxt.classList.toggle('hidden');
                closeTxt.classList.toggle('hidden');
            });
        }

        /* ===== Swap de proteínas ===== */
        const swapState = {}; // {prodId:{current:[ids], basePrice, adjust, swappingId}}

        function startSwap(prodId, pid) {
            const modal = document.getElementById(`productModal-${prodId}`);
            const all = JSON.parse(modal.dataset.all);
            const box = document.getElementById(`swap-box-${prodId}`);
            const select = document.getElementById(`swap-select-${prodId}`);

            if (!swapState[prodId]) {
                const ids = [...modal.querySelectorAll(`.prot-input-${prodId}`)]
                    .map(el => Number(el.value));
                swapState[prodId] = {
                    current: ids,
                    basePrice: Number(modal.dataset.basePrice),
                    adjust: 0
                };
            }

            swapState[prodId].swappingId = pid;

            /* Rellenar opciones */
            select.innerHTML = '<option value="">-- otra proteína --</option>';
            all.filter(i => i.tipo === 'proteina' && i.id !== pid)
                .forEach(i => {
                    select.insertAdjacentHTML('beforeend',
                        `<option value="${i.id}">${i.nombre}</option>`);
                });

            box.classList.remove('hidden');
        }

        function confirmSwap(prodId) {
            const select = document.getElementById(`swap-select-${prodId}`);
            const target = Number(select.value);
            if (!target) return;
            const st = swapState[prodId];
            st.current = st.current.map(p => p === st.swappingId ? target : p);
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
            const listEl = document.getElementById(`prot-list-${prodId}`);
            const priceEl = document.getElementById(`price-${prodId}`);
            const form = document.getElementById(`form-${prodId}`);
            const adjustH = document.getElementById(`adjust-${prodId}`);
            const st = swapState[prodId];

            /* Chips visibles */
            listEl.innerHTML = '';
            st.current.forEach(pid => {
                const name = all.find(i => i.id === pid)?.nombre || pid;
                listEl.insertAdjacentHTML('beforeend', `
           <li class="chip" data-prot="${pid}">
               ${name}
               <button type="button" class="btn btn-sm btn-outline-warning"
                       onclick="startSwap(${prodId}, ${pid})">Cambiar</button>
           </li>`);
            });

            /* Reemplazar inputs ocultos */
            form.querySelectorAll(`.prot-input-${prodId}`).forEach(el => el.remove());
            st.current.forEach(pid => {
                const h = document.createElement('input');
                h.type = 'hidden';
                h.name = 'Proteínas[]';
                h.value = pid;
                h.className = `prot-input-${prodId}`;
                form.appendChild(h);
            });

            /* Precio y ajuste */
            adjustH.value = st.adjust * 1000;
            priceEl.textContent =
                `$${(st.basePrice + st.adjust * 1000).toLocaleString('de-DE')} CLP`;
        }

        /* (Opcional) Cierra caja swap al ocultar modal  */
        document.querySelectorAll('.modal').forEach(m => {
            m.addEventListener('hidden.bs.modal', () => {
                const id = Number(m.id.replace('productModal-', ''));
                if (swapState[id]) delete swapState[id];
            });
        });
    </script>
@endpush
