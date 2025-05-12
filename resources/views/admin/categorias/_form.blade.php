@csrf

<div class="mb-4">
    <label class="block font-medium">Nombre</label>
    <input type="text" name="nombre" 
           value="{{ old('nombre', $categoria->nombre ?? '') }}"
           class="border p-2 w-full" required>
    @error('nombre') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
</div>
