@csrf

{{-- Nombre --}}
<div class="mb-4">
    <label for="nombre" class="block font-medium">Nombre</label>
    <input x-model="form.nombre" type="text" name="nombre" id="nombre" class="border p-2 w-full" required>
    @error('nombre')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
</div>

{{-- Descripción --}}
<div class="mb-4">
    <label for="descripcion" class="block font-medium">Descripción</label>
    <textarea x-model="form.descripcion" name="descripcion" id="descripcion" class="border p-2 w-full" rows="3"></textarea>
    @error('descripcion')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
</div>

{{-- Precio Base --}}
<div class="mb-4">
    <label for="precio" class="block font-medium">Precio Base</label>
    <input x-model.number="form.precio" type="number" name="precio" id="precio" class="border p-2 w-full"
        step="0.01" required>
    @error('precio')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
</div>

{{-- Categoría --}}
<div class="mb-4">
    <label for="categoria_id" class="block font-medium">Categoría</label>
    <select x-model="form.categoria_id" name="categoria_id" id="categoria_id" class="border p-2 w-full" required>
        <option value="">-- Seleccione --</option>
        @foreach ($categorias as $id => $label)
            <option value="{{ $id }}">{{ $label }}</option>
        @endforeach
    </select>
    @error('categoria_id')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
</div>

{{-- Personalizable --}}
<div class="mb-4">
    {{-- hidden para que siempre viaje el campo --}}
    <input type="hidden" name="personalizable" value="0">

    <label class="inline-flex items-center">
        <input type="checkbox" name="personalizable" value="1" class="form-checkbox"
            {{ old('personalizable', 0) ? 'checked' : '' }}>
        <span class="ml-2">Personalizable</span>
    </label>

    @error('personalizable')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
</div>


{{-- Imagen --}}
<div class="mb-4">
    <label for="imagen" class="block font-medium">Imagen (opcional)</label>
    <input @change="onFileChange" type="file" name="imagen" id="imagen" class="border p-2 w-full">
    <template x-if="form.imagenPreview">
        <p class="mt-2">
            Imagen actual:<br>
            <img :src="form.imagenPreview" class="w-32 rounded">
        </p>
    </template>
    @error('imagen')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
</div>

{{-- Unidades incluidas --}}
<div class="mb-4">
    <label for="unidades" class="block font-medium">Unidades incluidas</label>
    <input x-model.number="form.unidades" type="number" name="unidades" id="unidades" class="border p-2 w-full"
        min="1" required>
    @error('unidades')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
</div>

{{-- Envolturas (múltiples bases) --}}
<div class="mb-4">
    <label class="block font-medium mb-2">Bases disponibles</label>
    <div class="flex flex-wrap gap-4">
        @foreach ($wrappers as $w)
            <label class="inline-flex items-center space-x-2">
                <input type="checkbox" name="envolturas[]" value="{{ $w->id }}" x-model="form.envolturas"
                    class="form-checkbox">
                <span>{{ $w->nombre }}</span>
            </label>
        @endforeach
    </div>
    @error('envolturas')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
</div>

{{-- Proteínas --}}
<div class="mb-4">
    <label class="block font-medium mb-2">Proteínas</label>
    <div class="space-y-2">
        @foreach ($proteins as $p)
            <div class="flex items-center space-x-2">
                <input type="checkbox" name="Proteínas[]" value="{{ $p->id }}" x-model.number="form.Proteínas"
                    class="form-checkbox" id="prot-{{ $p->id }}">
                <label for="prot-{{ $p->id }}">{{ $p->nombre }}</label>
                <input type="number" name="cantidad_proteina[{{ $p->id }}]"
                    x-model.number="form.cantidad_proteina['{{ $p->id }}']" min="1"
                    :disabled="!form.Proteínas.includes({{ $p->id }})"
                    class="w-16 border rounded px-2 py-1 ml-auto">
            </div>
        @endforeach
    </div>
    @error('Proteínas')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
    @error('cantidad_proteina.*')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
</div>

{{-- Vegetales --}}
<div class="mb-4">
    <label class="block font-medium mb-2">Vegetales</label>
    <div class="space-y-2">
        @foreach ($vegetables as $v)
            <div class="flex items-center space-x-2">
                <input type="checkbox" name="vegetales[]" value="{{ $v->id }}"
                    x-model.number="form.vegetales" class="form-checkbox" id="veg-{{ $v->id }}">
                <label for="veg-{{ $v->id }}">{{ $v->nombre }}</label>
                <input type="number" name="cantidad_vegetal[{{ $v->id }}]"
                    x-model.number="form.cantidad_vegetal['{{ $v->id }}']" min="1"
                    :disabled="!form.vegetales.includes({{ $v->id }})"
                    class="w-16 border rounded px-2 py-1 ml-auto">
            </div>
        @endforeach
    </div>
    @error('vegetales')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
    @error('cantidad_vegetal.*')
        <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
</div>
