@csrf

<div class="mb-4">
    <label for="nombre" class="block font-medium">Nombre</label>
    <input x-model="editNombre" type="text" name="nombre" id="nombre" class="border p-2 w-full" required>
    @error('nombre')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label for="tipo" class="block font-medium">Tipo</label>
    <select x-model="editTipo" name="tipo" id="tipo" class="border p-2 w-full" required>
        @foreach ($tipos as $tipo)
            <option value="{{ $tipo }}" {{ old('tipo', $ingrediente->tipo ?? '') === $tipo ? 'selected' : '' }}>
                {{ ucfirst($tipo) }}
            </option>
        @endforeach
    </select>
    @error('tipo')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>


<div class="mb-4">
    <label for="costo" class="block font-medium">Costo (solo para extras)</label>
    <input x-model="editCosto" type="number" name="costo" id="costo" class="border p-2 w-full"
        :disabled="editTipo === 'base'" >
    @error('costo')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
