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

    // /* Modal público de Producto */
    // Alpine.data('productModal', () => ({
    //     isOpen: false,
    //     title: '',
    //     bodyHtml: '',
    //     openModal(id) {
    //         this.isOpen = true;
    //         this.title = 'Cargando...';
    //         this.bodyHtml = '<div class="text-center p-4"><div class="spinner-border text-danger"></div></div>';
    //         const modalEl = document.getElementById('productoModal');
    //         this.modalInstance = new bootstrap.Modal(modalEl);
    //         this.modalInstance.show();

    //         fetch(`/producto/modal/${id}`)
    //             .then(res => res.ok ? res.text() : Promise.reject())
    //             .then(html => {
    //                 this.bodyHtml = html;
    //                 const tmp = document.createElement('div');
    //                 tmp.innerHTML = html;
    //                 const h = tmp.querySelector('#modal-producto-titulo');
    //                 this.title = h ? h.textContent : 'Detalle del producto';
    //                 setTimeout(() => window.inicializarModalProducto ?.(), 50);
    //             })
    //             .catch(() => {
    //                 this.bodyHtml = '<p class="text-danger p-4">Error al cargar el producto.</p>';
    //                 this.title = 'Error';
    //             });
    //     },
    //     closeModal() {
    //         this.isOpen && this.modalInstance.hide();
    //         this.isOpen = false;
    //         this.title = this.bodyHtml = '';
    //     }
    // }));

    Alpine.data('productModalDetails', (assigned, allIngredients, basePrice) => ({
        assigned,
        allIngredients,
        basePrice, // ⚠️ antes ponías `price`
        baseRolls: {},
        currentRolls: {},
        availableRolls: 0,
        recargoRolls: 0,
        swapping: null,

        init(prodId) {
            // 1) Inicializa baseRolls y currentRolls
            this.baseRolls = {};
            this.currentRolls = {};
            this.assigned.forEach(i => {
                this.baseRolls[i.id] = i.rolls;
                this.currentRolls[i.id] = i.rolls;
            });

            // 2) Relee el precio base del DOM (si quisieras)
            const precioFromAttr = parseInt(
                document.getElementById(`precio-${prodId}`) ?.dataset.basePrice,
                10
            );
            if (!isNaN(precioFromAttr)) {
                this.basePrice = precioFromAttr;
            }

            // 3) Calcula disponibles y recargo
            this.updateAvailable();
        },

        updateAvailable() {
            const sumBase = Object.values(this.baseRolls).reduce((a, b) => a + b, 0);
            const sumCurrent = Object.values(this.currentRolls).reduce((a, b) => a + b, 0);
            this.availableRolls = sumBase - sumCurrent;
            this.recargoRolls = Object.entries(this.currentRolls)
                .reduce((acc, [id, curr]) => {
                    const delta = curr - (this.baseRolls[id] || 0);
                    return acc + (delta > 0 ? delta : 0);
                }, 0);
        },

        get availableToSwap() {
            return this.allIngredients.filter(i => i.id !== this.swapping);
        },

        getName(id) {
            const found = this.assigned.find(i => i.id === id);
            return found ? found.nombre : '';
        },

        startSwap(id) {
            if (this.currentRolls[id] > 0) {
                this.swapping = id;
            }
        },

        doSwap(targetId) {
            this.currentRolls[this.swapping]--;
            this.currentRolls[targetId] = (this.currentRolls[targetId] || 0) + 1;
            this.swapping = null;
            this.updateAvailable();
        },

        cancelSwap() {
            this.swapping = null;
        },

        addToCart(id) {
            window.addToCart(id); // tu función de fetch
        },

        closeModal(id) {
            document.getElementById(`modal-${id}`) ?.classList.add('hidden');
        }
    }));


Alpine.data('productSwapper', (assigned, allIngredients) => ({
  // — Parámetros inyectados —
  assigned,         // [ {id,nombre,rolls}, … ]
  allIngredients,   // [ {id,nombre}, … ]

  // — Estado interno —
  baseRolls: {},      // { [id]: rollsOriginales }
  currentRolls: [],   // [ {id,nombre,rollsActuales}, … ]
  swapping: null,     // id del ingrediente a intercambiar

  // — Computeds —
  get availableRolls() {
    const sumBase = Object.values(this.baseRolls).reduce((a,b) => a + b, 0);
    const sumCurr = this.currentRolls.reduce((a,i) => a + i.rolls, 0);
    return sumBase - sumCurr;
  },

  get recargoRolls() {
    return this.currentRolls.reduce((acc, cur) => {
      const base = this.baseRolls[cur.id] || 0;
      const delta = cur.rolls - base;
      return acc + (delta > 0 ? delta : 0);
    }, 0);
  },

  get availableToSwap() {
    // Lista de targets: todos menos el que estoy swapping
    return this.allIngredients.filter(i => i.id !== this.swapping);
  },

  // — Inicialización —
  init() {
    // 1) Montar baseRolls
    this.assigned.forEach(i => {
      this.baseRolls[i.id] = i.rolls;
    });
    // 2) Montar currentRolls (clon de assigned)
    this.currentRolls = this.assigned.map(i => ({
      id:     i.id,
      nombre: i.nombre,
      rolls:  i.rolls,
    }));
  },

  // — Acciones de swap —
  startSwap(id) {
    if ((this.baseRolls[id] || 0) > 0) {
      this.swapping = id;
    }
  },

  doSwap(targetId) {
    // Busco y decremento en currentRolls
    const src = this.currentRolls.find(i => i.id === this.swapping);
    if (src && src.rolls > 0) src.rolls--;
    // Busco o creo el destino y aumento
    let dest = this.currentRolls.find(i => i.id === targetId);
    if (dest) {
      dest.rolls++;
    } else {
      dest = { 
        id:     targetId,
        nombre: this.allIngredients.find(i => i.id === targetId).nombre,
        rolls:  1
      };
      this.currentRolls.push(dest);
    }
    this.swapping = null;
  },

  cancelSwap() {
    this.swapping = null;
  },
}));


});




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
window.openModal = function(id) {
  // 1) Localiza el modal estático
  const modalEl = document.getElementById(`modal-${id}`);
  if (!modalEl) return;

  // 2) Pídele a Alpine que inicialice tu component productModalDetails
  //    pasando el mismo `id` al método init
  if (modalEl.__x && modalEl.__x.$data.init) {
    modalEl.__x.$data.init(id);
  }

  // 3) Y por último hazlo visible
  modalEl.classList.remove('hidden');
};



window.closeModal = function (id) {
    document.getElementById('modal-' + id).classList.add('hidden');
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
