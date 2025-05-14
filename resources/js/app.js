/**
 *  resources/js/app.js
 *  ——————————————————————————————————————————————————————————
 *  Configuración Alpine global + lógicas de modales
 */

import Alpine from 'alpinejs';
import focus  from '@alpinejs/focus';

window.Alpine = Alpine;
Alpine.plugin(focus);

/* ════════════════════════════════════════════════
   ▸ 1. COMPONENTE productModalAdmin
   ------------------------------------------------
   Se expone como función global  para que Blade
   le inyecte categorías e ingredientes en tiempo
   de renderizado:
      x-data="productModalAdmin(@json($categorias), @json($ingredientes))"
   ════════════════════════════════════════════════ */
// window.productModalAdmin = (categorias = {}, ingredientes = []) => ({
//   categorias,
//   ingredientes,
  // Ahora lee desde window.akila y no recibe parámetros
window.productModalAdmin = () => ({
  categorias: window.akila?.categorias ?? {},
  ingredientes: window.akila?.ingredientes ?? [],
  activeModal: null,                    // 'create' | 'edit' | 'delete'
  form: {
    id: null,
    nombre: '',
    descripcion: '',
    precio: '',
    categoria_id: null,
    personalizable: true,
    unidades: 1,
    imagen: null,
    imagenPreview: null,
    ingredientes_seleccionados: {},
  },

  /* ——— Computeds ——— */
  get editAction()   { return `/admin/productos/${this.form.id}` },
  get deleteAction() { return `/admin/productos/${this.form.id}` },

  /* ——— Acciones ——— */
  openCreate() {
    this.activeModal = 'create';
    Object.assign(this.form, {
      id: null, nombre: '', descripcion: '',
      precio: '', categoria_id: Object.keys(this.categorias)[0] ?? null,
      personalizable: true, unidades: 1,
      imagen: null, imagenPreview: null,
      ingredientes_seleccionados: {},
    });
    // optional chaining bien escrito
    this.$nextTick(() => this.$el.querySelector('#create_nombre')?.focus());
  },

  openEdit(id, nombre, categoriaId, precio, personalizable, unidades, ingredientesPivot) {
    this.activeModal         = 'edit';
    this.form.id             = id;
    this.form.nombre         = nombre;
    this.form.descripcion    = '';
    this.form.precio         = precio;
    this.form.categoria_id   = categoriaId;
    this.form.personalizable = personalizable;
    this.form.unidades       = unidades;
    this.form.imagen         = null;
    this.form.imagenPreview  = null;
    this.form.ingredientes_seleccionados = {};
    ingredientesPivot.forEach(({ id, pivot }) => {
      this.form.ingredientes_seleccionados[id] = pivot.cantidad_permitida ?? 1;
    });
  },

  openDelete(id, nombre) {
    this.activeModal = 'delete';
    this.form.id     = id;
    this.form.nombre = nombre;
  },

  closeModal() {
    this.activeModal = null;
  },

  onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    this.form.imagen = file;

    /* Pre-visualización de la imagen */
    const reader = new FileReader();
    reader.onload = ev => { this.form.imagenPreview = ev.target.result };
    reader.readAsDataURL(file);
  },
});

/* ════════════════════════════════════════════════
   ▸ 2. Resto de componentes Alpine que NO dependen
     de datos Blade (se dejan dentro de alpine:init)
   ════════════════════════════════════════════════ */
document.addEventListener('alpine:init', () => {
  /* Ingredientes Admin */
  Alpine.data('ingredienteModal', () => ({
    showCreateModal: false,
    showEditModal:   false,
    showDeleteModal: false,
    editAction: '', editNombre: '', editTipo: '', editCosto: '',
    deleteAction: '', deleteName: '',
    openCreate()  { this.showCreateModal = true },
    closeCreate() { this.showCreateModal = false },
    openEdit(id,nombre,tipo,costo) {
      this.editAction = `/admin/ingredientes/${id}`;
      this.editNombre = nombre;
      this.editTipo   = tipo;
      this.editCosto  = costo;
      this.showEditModal = true;
    },
    closeEdit() {
      this.showEditModal = false;
      this.editAction = this.editNombre = this.editTipo = this.editCosto = '';
    },
    openDelete(id,nombre) {
      this.deleteAction = `/admin/ingredientes/${id}`;
      this.deleteName   = nombre;
      this.showDeleteModal = true;
    },
    closeDelete() {
      this.showDeleteModal = false;
      this.deleteAction = this.deleteName = '';
    },
  }));

  /* Categorías Admin */
  Alpine.data('categoryModal', () => ({
    showCreateModal: false,
    showEditModal:   false,
    showDeleteModal: false,
    createNombre:   '',
    editAction:     '',
    editNombre:     '',
    deleteAction:   '',
    deleteName:     '',
    openCreate() {
      this.showCreateModal = true;
      this.createNombre = '';
    },
    closeCreate() {
      this.showCreateModal = false;
      this.createNombre = '';
    },
    openEdit(id, nombre) {
      this.editAction = `/admin/categorias/${id}`;
      this.editNombre = nombre;
      this.showEditModal = true;
    },
    closeEdit() {
      this.showEditModal = false;
      this.editAction = this.editNombre = '';
    },
    openDelete(id, nombre) {
      this.deleteAction = `/admin/categorias/${id}`;
      this.deleteName = nombre;
      this.showDeleteModal = true;
    },
    closeDelete() {
      this.showDeleteModal = false;
      this.deleteAction = this.deleteName = '';
    },
  }));

  /* Modal público de Producto */
  Alpine.data('productModal', () => ({
    isOpen:   false,
    title:    '',
    bodyHtml: '',
    openModal(id) {
      this.isOpen   = true;
      this.title    = 'Cargando...';
      this.bodyHtml = '<div class="text-center p-4"><div class="spinner-border text-danger"></div></div>';
      const modalEl = document.getElementById('productoModal');
      this.modalInstance = new bootstrap.Modal(modalEl);
      this.modalInstance.show();

      fetch(`/producto/modal/${id}`)
        .then(res => res.ok ? res.text() : Promise.reject())
        .then(html => {
          this.bodyHtml = html;
          const tmp = document.createElement('div');
          tmp.innerHTML = html;
          const h = tmp.querySelector('#modal-producto-titulo');
          this.title = h ? h.textContent : 'Detalle del producto';
          setTimeout(() => window.inicializarModalProducto?.(), 50);
        })
        .catch(() => {
          this.bodyHtml = '<p class="text-danger p-4">Error al cargar el producto.</p>';
          this.title    = 'Error';
        });
    },
    closeModal() {
      this.isOpen && this.modalInstance.hide();
      this.isOpen = false;
      this.title = this.bodyHtml = '';
    }
  }));
});

/* Inicia Alpine una vez registrados los listeners anteriores */
Alpine.start();

/* ════════════════════════════════════════════════
   ▸ 3. Funciones globales (header, toast, carrito…)
   ════════════════════════════════════════════════ */

// Header scroll (cambia opacidad / color al hacer scroll)
window.addEventListener('scroll', () => {
  const header = document.querySelector('header');
  header && header.classList.toggle('header-solid', window.scrollY > 50);
});

// Toast utility
function showToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.className = [
    'fixed','top-4','right-4','max-w-sm','w-full','p-4','mb-2','rounded',
    'shadow-lg','text-white',
    type === 'success' ? 'bg-green-500' : 'bg-red-500'
  ].join(' ');
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.classList.add('opacity-0','transition','duration-500');
    setTimeout(() => document.body.removeChild(toast), 500);
  }, 3000);
}

/* ——— Lógica de personalización y carrito para la tienda pública ——— */
window.selectedExtras = {};
window.removedBases  = {};
window.basePrice     = {};

window.openModal = function (id) {
  const span  = document.getElementById('precio-' + id);
  const base  = parseInt(span.dataset.basePrice, 10);
  const qty   = parseInt(span.dataset.unidades,   10);
  window.basePrice[id]   = base;
  window.selectedExtras[id] = {};
  window.removedBases[id]   = {};
  for (let u = 1; u <= qty; u++) {
    window.selectedExtras[id][u] = [];
    window.removedBases[id][u]   = [];
  }
  span.textContent = base.toLocaleString('es-CL');
  document.getElementById('modal-' + id).classList.remove('hidden');
};

window.closeModal = function (id) {
  document.getElementById('modal-' + id).classList.add('hidden');
};

window.toggleBase = function (id, unit, ingredient) {
  const cb  = document.getElementById(`${ingredient}-${id}-unit-${unit}`);
  const arr = window.removedBases[id][unit] || [];
  if (!cb.checked) arr.push(ingredient);
  else window.removedBases[id][unit] = arr.filter(i => i !== ingredient);
};

window.toggleExtra = function (id, unit, ingredient, price) {
  const cb  = document.getElementById(`extra-${id}-unit-${unit}-${ingredient}`);
  const arr = window.selectedExtras[id][unit] || [];
  if (cb.checked) {
    if (arr.length < 3) arr.push({ ingredient, price });
    else {
      cb.checked = false;
      showToast('Solo puedes elegir hasta 3 ingredientes adicionales por unidad.', 'error');
      return;
    }
  } else {
    window.selectedExtras[id][unit] = arr.filter(item => item.ingredient !== ingredient);
  }
  window.updatePrice(id);
};

window.updatePrice = function (id) {
  const span  = document.getElementById('precio-' + id);
  let total   = window.basePrice[id] || 0;
  Object.values(window.selectedExtras[id] || {}).forEach(arr => {
    total += arr.reduce((sum, e) => sum + e.price, 0);
  });
  span.textContent = total.toLocaleString('es-CL');
};

window.addToCart = async function (id) {
  const span   = document.getElementById('precio-' + id);
  const qty    = parseInt(span.dataset.unidades, 10) || 1;
  const extras = window.selectedExtras[id] || {};
  const removed = window.removedBases[id] || {};
  const price   = parseInt(span.textContent.replace(/\./g, ''), 10);

  try {
    const res = await fetch('/cart/add', {
      method:  'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        product_id: id,
        unidades:   qty,
        removed_bases: removed,
        extras,
        price
      })
    });
    const json = await res.json();
    if (json.success) {
      const countEl = document.querySelector('.cart-count');
      if (countEl) countEl.textContent = json.cart_count;
      showToast('Producto agregado al carrito.', 'success');
      window.closeModal(id);
    } else {
      showToast('No se pudo agregar al carrito.', 'error');
    }
  } catch (err) {
    console.error('Error adding to cart:', err);
    showToast('Error de red. Intenta más tarde.', 'error');
  }
};
