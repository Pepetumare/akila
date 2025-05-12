<div id="modal-{{ $producto->id }}"
    class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-start overflow-auto py-10 hidden" role="dialog"
    aria-modal="true">
    <div class="bg-white w-11/12 max-w-2xl p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold mb-4">
            Personaliza tus {{ $producto->nombre }}
        </h2>

        {{-- Iteramos por cada unidad --}}
        @for ($i = 1; $i <= $producto->unidades; $i++)
            <div class="unit-section border-b pb-4 mb-4">
                <h3 class="font-semibold mb-2">Unidad {{ $i }}</h3>

                <!-- Ingredientes Básicos (gratuitos) -->
                <div class="mb-3">
                    <h4 class="font-medium">Ingredientes Básicos</h4>
                    <label class="inline-flex items-center mr-4">
                        <input type="checkbox" id="queso-{{ $producto->id }}-unit-{{ $i }}" checked
                            onchange="toggleBase('{{ $producto->id }}', {{ $i }}, 'queso')"
                            class="form-checkbox mr-2">
                        Queso Crema
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" id="cebollin-{{ $producto->id }}-unit-{{ $i }}" checked
                            onchange="toggleBase('{{ $producto->id }}', {{ $i }}, 'cebollin')"
                            class="form-checkbox mr-2">
                        Cebollín
                    </label>
                </div>


                @if ($producto->categoria->slug === 'a-tu-pinta')
                    <h3 class="font-bold mb-2">Personaliza tu A tu pinta</h3>
                    @foreach ($producto->swappables as $ing)
                        <div class="flex items-center mb-2">
                            <span class="flex-1">{{ $ing->nombre }}</span>
                            <select name="swap-{{ $producto->id }}-{{ $ing->id }}" class="border p-1">
                                <option value="{{ $ing->id }}">{{ $ing->nombre }}</option>
                                @foreach ($producto->swappables as $swap)
                                    @if ($swap->id !== $ing->id)
                                        <option value="{{ $swap->id }}">{{ $swap->nombre }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                @endif

                <!-- Ingredientes Extras -->
                <div class="mb-3">
                    <h4 class="font-medium">Extras (Máx. 3)</h4>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach (['Camarón', 'Pollo', 'Kanikama', 'Choclo', 'Palmitos', 'Champiñones', 'Salmón'] as $ingrediente)
                            <label class="inline-flex items-center">
                                <input type="checkbox"
                                    id="extra-{{ $producto->id }}-unit-{{ $i }}-{{ $ingrediente }}"
                                    onchange="toggleExtra('{{ $producto->id }}', {{ $i }}, '{{ $ingrediente }}', {{ $ingrediente == 'Salmón' ? 2000 : 1000 }})"
                                    class="form-checkbox mr-2">
                                {{ $ingrediente }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        @endfor

        <!-- Precio total -->
        <div class="mb-4">
            <p class="font-bold">
                Precio Total: $
                <span id="precio-{{ $producto->id }}" data-base-price="{{ (int) $producto->precio }}"
                    data-unidades="{{ $producto->unidades }}">
                    {{ number_format($producto->precio * $producto->unidades, 0, ',', '.') }}
                </span>
            </p>
        </div>

        <!-- Acciones -->
        <div class="flex justify-end space-x-2">
            <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
                onclick="addToCart('{{ $producto->id }}')">
                Agregar al Carrito
            </button>
            <button class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400"
                onclick="closeModal('{{ $producto->id }}')">
                Cancelar
            </button>
        </div>
    </div>
</div>
