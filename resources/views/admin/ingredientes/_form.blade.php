@csrf

<div class="mb-4">
    <label for="nombre" class="block font-medium">Nombre</label>
    <input 
        type="text" 
        name="nombre" 
        id="nombre"
        value="{{ old('nombre', $ingrediente->nombre ?? '') }}"
        class="border p-2 w-full" 
        required>
    @error('nombre')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label for="tipo" class="block font-medium">Tipo</label>
    <select 
        name="tipo" 
        id="tipo"
        class="border p-2 w-full" 
        required>
        <option value="base"  {{ old('tipo', $ingrediente->tipo ?? '') == 'base'  ? 'selected' : '' }}>Base</option>
        <option value="extra" {{ old('tipo', $ingrediente->tipo ?? '') == 'extra' ? 'selected' : '' }}>Extra</option>
    </select>
    @error('tipo')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label for="costo" class="block font-medium">Costo (solo para extras)</label>
    <input 
        type="number" 
        name="costo" 
        id="costo"
        step="0.01"
        value="{{ old('costo', $ingrediente->costo ?? '') }}"
        class="border p-2 w-full" 
        {{ (old('tipo', $ingrediente->tipo ?? '') == 'base') ? 'readonly' : '' }}>
    @error('costo')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
