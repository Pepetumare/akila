@csrf

<div class="mb-4">
    <label for="nombre" class="block font-medium">Nombre</label>
    <input
        x-model="form.nombre"
        type="text"
        name="nombre"
        id="nombre"
        class="border p-2 w-full"
        required
    >
    @error('nombre')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label for="descripcion" class="block font-medium">Descripción</label>
    <textarea
        x-model="form.descripcion"
        name="descripcion"
        id="descripcion"
        class="border p-2 w-full"
        rows="3"
    ></textarea>
    @error('descripcion')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label for="precio" class="block font-medium">Precio Base</label>
    <input
        x-model="form.precio"
        type="number"
        name="precio"
        id="precio"
        class="border p-2 w-full"
        step="0.01"
        required
    >
    @error('precio')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label for="categoria_id" class="block font-medium">Categoría</label>
    <select
        x-model="form.categoria_id"
        name="categoria_id"
        id="categoria_id"
        class="border p-2 w-full"
        required
    >
        <option value="">-- Seleccione --</option>
        @foreach ($categorias as $id => $nombre)
            <option value="{{ $id }}">{{ $nombre }}</option>
        @endforeach
    </select>
    @error('categoria_id')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <input type="hidden" name="personalizable" value="0">
    <label class="inline-flex items-center">
        <input
            x-model="form.personalizable"
            type="checkbox"
            name="personalizable"
            value="1"
            class="form-checkbox"
        >
        <span class="ml-2">Personalizable</span>
    </label>
    @error('personalizable')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label for="imagen" class="block font-medium">Imagen (opcional)</label>
    <input
        @change="onFileChange"
        type="file"
        name="imagen"
        id="imagen"
        class="border p-2 w-full"
    >
    @if (!empty($producto->imagen))
        <p class="mt-2">
            Imagen actual:<br>
            <img src="{{ asset('storage/' . $producto->imagen) }}" alt="" class="w-32 h-auto rounded">
        </p>
    @endif
    @error('imagen')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label for="unidades" class="block font-medium">Unidades incluidas</label>
    <input
        x-model="form.unidades"
        type="number"
        name="unidades"
        id="unidades"
        class="border p-2 w-full"
        min="1"
        required
    >
    @error('unidades')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label for="swappables" class="block font-medium">Ingredientes intercambiables (“A tu pinta”)</label>
    <select
        x-model="form.ingredientes_seleccionados"
        name="swappables[]"
        id="swappables"
        multiple
        class="border p-2 w-full h-32"
    >
        @foreach ($ingredientes as $ing)
            <option :value="{{ $ing['id'] }}">{{ $ing['nombre'] }}</option>
        @endforeach
    </select>
    <p class="text-sm text-gray-500">
        Estos ingredientes solo se tendrán en cuenta si el producto es de categoría “A tu pinta”.
    </p>
    @error('swappables')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
