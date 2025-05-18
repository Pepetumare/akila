// resources/js/app.js

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

window.Alpine = Alpine;
Alpine.plugin(focus);

/* ════════════════════════════════════════════════
   ▸ 1. COMPONENTE productModalAdmin
╚════════════════════════════════════════════════ */
window.productModalAdmin = () => ({
    categorias: window.akila ?.categorias ?? {},
    ingredientes: window.akila ?.ingredientes ?? [],
    activeModal: null,
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

    /* Computed actions for form URLs */
    get editAction() {
        return `/admin/productos/${this.form.id}`
    },
    get deleteAction() {
        return `/admin/productos/${this.form.id}`
    },

    /* Open create modal */
    openCreate() {
        this.activeModal = 'create';
        Object.assign(this.form, {
            id: null,
            nombre: '',
            descripcion: '',
            precio: '',
            categoria_id: Object.keys(this.categorias)[0] ?? null,
            personalizable: true,
            unidades: 1,
            imagen: null,
            imagenPreview: null,
            ingredientes_seleccionados: {},
        });
        this.$nextTick(() => this.$el.querySelector('#create_nombre') ?.focus());
    },

    /* Open edit modal with data */
    openEdit(id, nombre, categoriaId, precio, personalizable, unidades, ingredientesPivot) {
        this.activeModal = 'edit';
        this.form.id = id;
        this.form.nombre = nombre;
        this.form.descripcion = '';
        this.form.precio = precio;
        this.form.categoria_id = categoriaId;
        this.form.personalizable = personalizable;
        this.form.unidades = unidades;
        this.form.imagen = null;
        this.form.imagenPreview = null;
        this.form.ingredientes_seleccionados = {};
        ingredientesPivot.forEach(({
            id,
            pivot
        }) => {
            this.form.ingredientes_seleccionados[id] = pivot.cantidad_permitida ?? 1;
        });
    },

    openDelete(id, nombre) {
        this.activeModal = 'delete';
        this.form.id = id;
        this.form.nombre = nombre;
    },

    closeModal() {
        this.activeModal = null;
    },

    onFileChange(e) {
        const file = e.target.files[0];
        if (!file) return;
        this.form.imagen = file;
        const reader = new FileReader();
        reader.onload = ev => {
            this.form.imagenPreview = ev.target.result
        };
        reader.readAsDataURL(file);
    },
});

/* ════════════════════════════════════════════════
   ▸ 2. OTROS COMPONENTES Alpine
╚════════════════════════════════════════════════ */
document.addEventListener('alpine:init', () => {
    /* Ingredientes Admin */
    Alpine.data('ingredienteModal', () => ({
        showCreateModal: false,
        showEditModal: false,
        showDeleteModal: false,
        editAction: '',
        editNombre: '',
        editTipo: '',
        editCosto: '',
        deleteAction: '',
        deleteName: '',
        openCreate() {
            this.showCreateModal = true
        },
        closeCreate() {
            this.showCreateModal = false
        },
        openEdit(id, nombre, tipo, costo) {
            this.editAction = `/admin/ingredientes/${id}`;
            this.editNombre = nombre;
            this.editTipo = tipo;
            this.editCosto = costo;
            this.showEditModal = true;
        },
        closeEdit() {
            this.showEditModal = false;
            this.editAction = this.editNombre = this.editTipo = this.editCosto = '';
        },
        openDelete(id, nombre) {
            this.deleteAction = `/admin/ingredientes/${id}`;
            this.deleteName = nombre;
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
        showEditModal: false,
        showDeleteModal: false,
        createNombre: '',
        editAction: '',
        editNombre: '',
        deleteAction: '',
        deleteName: '',
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
        isOpen: false,
        title: '',
        bodyHtml: '',
        openModal(id) {
            this.isOpen = true;
            this.title = 'Cargando...';
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
                    setTimeout(() => window.inicializarModalProducto ?.(), 50);
                })
                .catch(() => {
                    this.bodyHtml = '<p class="text-danger p-4">Error al cargar el producto.</p>';
                    this.title = 'Error';
                });
        },
        closeModal() {
            this.isOpen && this.modalInstance.hide();
            this.isOpen = false;
            this.title = this.bodyHtml = '';
        }
    }));

    Alpine.data('productModalDetails', (assigned, allIngredients) => ({
        assigned,
        allIngredients,
        basePrice: 0,
        baseRolls: {},
        currentRolls: {},
        availableRolls: 0,
        recargoRolls: 0,
        swapping: null,

        init(prodId) {
          // 1) Montar baseRolls/currentRolls
          this.baseRolls = {};
          this.currentRolls = {};
          assigned.forEach(i => {
            this.baseRolls[i.id]    = i.rolls;
            this.currentRolls[i.id] = i.rolls;
          });

          // 2) Leer precio
          this.basePrice = parseInt(
            document.getElementById(`precio-${prodId}`).dataset.basePrice,
            10
          ) || 0;

          // 3) Calcular availableRolls & recargoRolls
          this.updateAvailable();
        },

        updateAvailable() {
          const sumBase    = Object.values(this.baseRolls).reduce((a,b)=>a+b,0);
          const sumCurrent = Object.values(this.currentRolls).reduce((a,b)=>a+b,0);
          this.availableRolls = sumBase - sumCurrent;
          this.recargoRolls   = Object.entries(this.currentRolls)
            .reduce((acc, [id, curr]) => {
              const delta = curr - (this.baseRolls[id]||0);
              return acc + (delta > 0 ? delta : 0);
            }, 0);
        },

        get availableToSwap() {
          return allIngredients.filter(i => i.id != this.swapping);
        },

        getName(id) {
          const f = assigned.find(i => i.id == id);
          return f ? f.nombre : '';
        },

        startSwap(id) {
          if (this.currentRolls[id] > 0) this.swapping = id;
        },

        doSwap(targetId) {
          this.currentRolls[this.swapping]--;
          this.currentRolls[targetId] = (this.currentRolls[targetId]||0) + 1;
          this.swapping = null;
          this.updateAvailable();
        },

        cancelSwap() {
          this.swapping = null;
        },

        addToCart(id) {
          window.addToCart(id); // tu fetch ya está definido en window.addToCart
        },

        closeModal(id) {
          document.getElementById(`modal-${id}`).classList.add('hidden');
        }
      }));

});


Alpine.data('productSwapper', (assigned, allIngredients) => ({
    baseRolls: assigned, // [ {id,nombre,rolls}, … ]
    currentRolls: assigned.map(obj => ({
        ...obj
    })),
    swapping: null, // id del ingrediente que estamos quitando
    availableToSwap: [], // lista de ingredientes “targets”

    startSwap(ingId) {
        this.swapping = ingId;
        // genera lista de posibles destinos: todos – el propio
        this.availableToSwap = this.allIngredients
            .filter(i => i.id !== ingId);
    },

    getName(id) {
        const found = this.baseRolls.find(i => i.id === id);
        return found ? found.nombre : '';
    },

    doSwap(targetId) {
        // decrementa 1 roll de swapping
        const src = this.currentRolls.find(i => i.id === this.swapping);
        src.rolls--;
        // incrementa 1 roll en target
        let dest = this.currentRolls.find(i => i.id === targetId);
        if (dest) {
            dest.rolls++;
        } else {
            // si no estaba originalmente, lo añadimos (pero en combos fijos siempre estará?)
            this.currentRolls.push({
                id: targetId,
                nombre: this.allIngredients.find(i => i.id === targetId).nombre,
                rolls: 1
            });
        }
        // cerramos el panel
        this.swapping = null;
    },

    cancelSwap() {
        this.swapping = null;
    }
}));


/* Inicia Alpine */
Alpine.start();

/* ════════════════════════════════════════════════
   ▸ 3. UI & Carrito Público
╚════════════════════════════════════════════════ */
// Header scroll (opacidad al hacer scroll)
window.addEventListener('scroll', () => {
    const header = document.querySelector('header');
    header && header.classList.toggle('header-solid', window.scrollY > 50);
});

// Toast utility
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = [
        'fixed', 'top-4', 'right-4', 'max-w-sm', 'w-full', 'p-4', 'mb-2', 'rounded',
        'shadow-lg', 'text-white',
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    ].join(' ');
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('opacity-0', 'transition', 'duration-500');
        setTimeout(() => document.body.removeChild(toast), 500);
    }, 3000);
}

// Lógica antigua de extras y bases
window.selectedExtras = {};
window.removedBases = {};
window.basePrice = {};

// Nuevas variables globales para rolls intercambiables
window.baseRolls = {}; // rolls base por producto-ingrediente
window.currentRolls = {}; // rolls actuales tras cambios
window.availableRolls = {}; // rolls libres para reasignar
window.recargoRolls = {}; // número de rolls agregados (para cálculo de recargo)

/**
 * Al abrir el modal de producto:
 * - Inicializa extras/base (antigua lógica)
 * - Inicializa rolls y recargos (nueva lógica)
 */
window.openModal = function (id) {
    const span = document.getElementById('precio-' + id);
    const base = parseInt(span.dataset.basePrice, 10);
    const qty = parseInt(span.dataset.unidades, 10);

    // --- Extras/Base antigua ---
    window.basePrice[id] = base;
    window.selectedExtras[id] = {};
    window.removedBases[id] = {};
    for (let u = 1; u <= qty; u++) {
        window.selectedExtras[id][u] = [];
        window.removedBases[id][u] = [];
    }

    // --- Rolls intercambiables (nuevo) ---
    window.baseRolls[id] = {};
    window.currentRolls[id] = {};
    window.availableRolls[id] = 0;
    window.recargoRolls[id] = 0;

    document.querySelectorAll(`#modal-${id} .chip-ingrediente`).forEach(el => {
        const ingId = el.dataset.id;
        const rolls = parseInt(el.dataset.rolls, 10) || 0;
        window.baseRolls[id][ingId] = rolls;
        window.currentRolls[id][ingId] = rolls;
    });

    // Render precio base
    span.textContent = base.toLocaleString('es-CL');
    document.getElementById('modal-' + id).classList.remove('hidden');
};

window.closeModal = function (id) {
    document.getElementById('modal-' + id).classList.add('hidden');
};

window.toggleBase = function (id, unit, ingredient) {
    const cb = document.getElementById(`${ingredient}-${id}-unit-${unit}`);
    const arr = window.removedBases[id][unit] || [];
    if (!cb.checked) arr.push(ingredient);
    else window.removedBases[id][unit] = arr.filter(i => i !== ingredient);
};

window.toggleExtra = function (id, unit, ingredient, price) {
    const cb = document.getElementById(`extra-${id}-unit-${unit}-${ingredient}`);
    const arr = window.selectedExtras[id][unit] || [];
    if (cb.checked) {
        if (arr.length < 3) arr.push({
            ingredient,
            price
        });
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
    const span = document.getElementById('precio-' + id);
    let total = window.basePrice[id] || 0;
    Object.values(window.selectedExtras[id] || {}).forEach(arr => {
        total += arr.reduce((sum, e) => sum + e.price, 0);
    });
    span.textContent = total.toLocaleString('es-CL');
};

/**
 * Actualiza recargo y precio total incluyendo recargo de rolls
 */
window.updateRecargo = function (id) {
    let added = 0;
    for (const ingId in window.currentRolls[id]) {
        const delta = window.currentRolls[id][ingId] - window.baseRolls[id][ingId];
        if (delta > 0) added += delta;
    }
    window.recargoRolls[id] = added;
    const recargoCLP = added * 1000;
    const span = document.getElementById('precio-' + id);
    const baseV = window.basePrice[id] || 0;
    span.textContent = (baseV + recargoCLP).toLocaleString('es-CL');

    const recEl = document.getElementById('recargo-' + id);
    if (recEl) recEl.textContent = `+${recargoCLP.toLocaleString('es-CL')} CLP`;
};

/**
 * Decrementa un roll (bloque de 10 piezas) de un ingrediente
 */
window.decrementRoll = function (prodId, ingId) {
    if (window.currentRolls[prodId][ingId] > 0) {
        window.currentRolls[prodId][ingId]--;
        window.availableRolls[prodId]++;
        window.updateRecargo(prodId);
    }
};

/**
 * Incrementa un roll en otro ingrediente
 */
window.incrementRoll = function (prodId, ingId) {
    if (window.availableRolls[prodId] > 0) {
        window.currentRolls[prodId][ingId]++;
        window.availableRolls[prodId]--;
        window.updateRecargo(prodId);
    }
};

window.addToCart = async function (id) {
  console.log('🛒 addToCart llamado con id =', id);
    const span = document.getElementById('precio-' + id);
    const qty = parseInt(span.dataset.unidades, 10) || 1;
    const extras = window.selectedExtras[id] || {};
    const removed = window.removedBases[id] || {};
    const price = parseInt(span.textContent.replace(/\./g, ''), 10);

    try {
        const res = await fetch('/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                product_id: id,
                unidades: qty,
                // Personalización de rolls
                personalization: {
                    rolls: window.currentRolls[id], // { ingredienteId: #rolls, … }
                    recargo: window.recargoRolls[id] * 1000 // CLP
                },
                price

            })
        });

        const json = await res.json();

        if (json.success) {
            // ————— Aquí evitamos el '?.' en el LHS —————
            const cartCountEl = document.querySelector('.cart-count');
            if (cartCountEl) {
                cartCountEl.textContent = json.cart_count;
            }

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
