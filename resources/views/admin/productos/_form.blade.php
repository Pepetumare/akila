@csrf

{{-- Nombre --}}
<div class="mb-4">
    <label for="nombre" class="block font-medium">Nombre</label>
    <input x-model="form.nombre" type="text" name="nombre" id="nombre" class="border p-2 w-full" required>
    @error('nombre')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Descripción --}}
<div class="mb-4">
    <label for="descripcion" class="block font-medium">Descripción</label>
    <textarea x-model="form.descripcion" name="descripcion" id="descripcion" class="border p-2 w-full" rows="3"></textarea>
    @error('descripcion')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Precio Base --}}
<div class="mb-4">
    <label for="precio" class="block font-medium">Precio Base</label>
    <input x-model.number="form.precio" type="number" name="precio" id="precio" class="border p-2 w-full"
        step="0.01" required>
    @error('precio')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Categoría --}}
<div class="mb-4">
    <label for="categoria_id" class="block font-medium">Categoría</label>
    <select x-model="form.categoria_id" name="categoria_id" id="categoria_id" class="border p-2 w-full" required>
        <option value="">-- Seleccione --</option>
        <template x-for="(label, id) in categorias" :key="id">
            <option :value="id" x-text="label"></option>
        </template>
    </select>
    @error('categoria_id')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Personalizable --}}
<div class="mb-4">
    <input type="hidden" name="personalizable" value="0">
    <label class="inline-flex items-center">
        <input x-model="form.personalizable" type="checkbox" name="personalizable" value="1" class="form-checkbox">
        <span class="ml-2">Personalizable</span>
    </label>
    @error('personalizable')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Imagen --}}
<div class="mb-4">
    <label for="imagen" class="block font-medium">Imagen (opcional)</label>
    <input @change="onFileChange" type="file" name="imagen" id="imagen" class="border p-2 w-full">
    <template x-if="form.imagenPreview">
        <p class="mt-2">
            Imagen actual:<br>
            <img :src="form.imagenPreview" alt="" class="w-32 h-auto rounded">
        </p>
    </template>
    @error('imagen')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Unidades incluidas --}}
<div class="mb-4">
    <label for="unidades" class="block font-medium">Unidades incluidas</label>
    <input x-model.number="form.unidades" type="number" name="unidades" id="unidades" class="border p-2 w-full"
        min="1" required>
    @error('unidades')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Rolls Disponibles --}}
<div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
    {{-- Total piezas (rolls) --}}
    <div>
        <label for="rolls_total" class="block font-medium">Total piezas (rolls)</label>
        <input x-model.number="form.rolls_total" type="number" name="rolls_total" id="rolls_total"
            class="border p-2 w-full" min="0" required>
        @error('rolls_total')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Piezas envueltas --}}
    <div>
        <label for="rolls_envueltos" class="block font-medium">Piezas envueltas</label>
        <input x-model.number="form.rolls_envueltos" type="number" name="rolls_envueltos" id="rolls_envueltos"
            class="border p-2 w-full" min="0" :max="form.rolls_total" required>
        @error('rolls_envueltos')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Piezas fritas --}}
    <div>
        <label for="rolls_fritos" class="block font-medium">Piezas fritas</label>
        <input x-model.number="form.rolls_fritos" type="number" name="rolls_fritos" id="rolls_fritos"
            class="border p-2 w-full" min="0" :max="form.rolls_total" required>
        @error('rolls_fritos')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>


{{-- Ingredientes --}}
<div class="mb-4">
    <label class="block font-medium mb-2">Ingredientes</label>

    <template x-for="ing in ingredientes" :key="ing.id">
        <div class="flex items-center space-x-4 mb-2">
            <label class="inline-flex items-center space-x-2">
                <input type="checkbox" :value="ing.id"
                    @change="
              if ($event.target.checked) {
                form.ingredientes_seleccionados[ing.id] = form.ingredientes_seleccionados[ing.id] 
                  ?? (ing.pivot?.cantidad_permitida || 1);
              } else {
                delete form.ingredientes_seleccionados[ing.id];
              }
            "
                    :checked="form.ingredientes_seleccionados[ing.id] !== undefined" class="form-checkbox">
                <span x-text="ing.nombre"></span>
            </label>

            <input type="number" min="1" x-model.number="form.ingredientes_seleccionados[ing.id]"
                :disabled="form.ingredientes_seleccionados[ing.id] === undefined" class="w-20 border rounded px-2 py-1"
                placeholder="Límite" />
        </div>
    </template>

    @error('ingredientes')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    @error('cantidad_permitida.*')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
