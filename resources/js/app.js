import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import './modules/menuPersonalization';

window.Alpine = Alpine;
Alpine.plugin(focus);

// Inicializamos componentes Alpine cuando se dispara el evento alpine:init
document.addEventListener('alpine:init', () => {
    /* ════════════════════════════════════════
       COMPONENTE productModalAdmin
    ════════════════════════════════════════ */
    Alpine.data('productModalAdmin', () => ({
        // Datos inyectados desde Blade
        categorias: window.akila ?.categorias ?? {},
        wrappers: window.akila ?.wrappers ?? [],
        proteins: window.akila ?.proteins ?? [],
        vegetables: window.akila ?.vegetables ?? [],

        // Estado de modales
        activeModal: null,

        // Rutas dinámicas
        get editAction() {
            return `/admin/productos/${this.form.id}`;
        },
        get deleteAction() {
            return `/admin/productos/${this.form.id}`;
        },

        // Form data
        form: {
            id: null,
            nombre: '',
            descripcion: '',
            precio: 0,
            categoria_id: null,
            personalizable: false,
            unidades: 1,
            imagen: null,
            imagenPreview: null,
            envolturas: [],
            Proteínas: [],
            vegetales: [],
            cantidad_proteina: {},
            cantidad_vegetal: {}
        },

        // Abrir modal de creación
        openCreate() {
            this.form.envolturas = []
            this.resetForm();
            this.form.categoria_id = Object.keys(this.categorias)[0] ?? null;
            this.activeModal = 'create';
            this.$nextTick(() => {
                const input = this.$el.querySelector('input[name="nombre"]');
                input ?.focus();
            });
        },

        // Abrir modal de edición
        openEdit(id, nombre, descripcion, categoriaId, precio, personalizable, unidades, wrapsPivot, protPivot, vegPivot) {
            this.resetForm();
            this.form.id = id;
            this.form.nombre = nombre;
            this.form.descripcion = descripcion;
            this.form.categoria_id = categoriaId;
            this.form.precio = precio;
            this.form.personalizable = personalizable;
            this.form.unidades = unidades;

            // Envoltura única
            this.form.envolturas = wrapsPivot.map(i => i.id);

            // **Categoría como string** para que coincida con los option.value
            this.form.categoria_id = String(categoriaId);

            // Proteínas y cantidades
            this.form.Proteínas = protPivot.map(i => i.id);
            protPivot.forEach(i => {
                this.form.cantidad_proteina[i.id] = i.pivot.cantidad_permitida;
            });

            // Vegetales y cantidades
            this.form.vegetales = vegPivot.map(i => i.id);
            vegPivot.forEach(i => {
                this.form.cantidad_vegetal[i.id] = i.pivot.cantidad_permitida;
            });

            this.activeModal = 'edit';
        },

        // Abrir modal de eliminación
        openDelete(id, nombre) {
            this.form.id = id;
            this.form.nombre = nombre;
            this.activeModal = 'delete';
        },

        // Cerrar modal
        closeModal() {
            this.activeModal = null;
        },

        // Resetear formulario
        resetForm() {
            this.form = {
                id: null,
                nombre: '',
                descripcion: '',
                precio: 0,
                categoria_id: '',
                personalizable: false,
                unidades: 1,
                imagen: null,
                imagenPreview: null,
                envolturas: [],
                Proteínas: [],
                vegetales: [],
                cantidad_proteina: {},
                cantidad_vegetal: {}
            };
        },

        // Previsualizar imagen
        onFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.form.imagen = file;
            const reader = new FileReader();
            reader.onload = e => this.form.imagenPreview = e.target.result;
            reader.readAsDataURL(file);
        }
    }));

    /* ════════════════════════════════════════
       COMPONENTE ingredienteModal
    ════════════════════════════════════════ */
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
            this.showCreateModal = true;
        },
        closeCreate() {
            this.showCreateModal = false;
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
        }
    }));

    /* ════════════════════════════════════════
       COMPONENTE categoryModal
    ════════════════════════════════════════ */
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
        }
    }));

});

// Iniciar Alpine
Alpine.start();

/* ════════════════════════════════════════════════
   UI GLOBAL & Carrito Público
   (Funciones auxiliares fuera de Alpine)
╚════════════════════════════════════════════════ */

// Header opacidad al hacer scroll
document.addEventListener('scroll', () => {
    const header = document.querySelector('header');
    header ?.classList.toggle('header-solid', window.scrollY > 50);
});
