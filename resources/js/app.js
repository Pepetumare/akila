window.selectedExtras = {};
window.removedBases = {};
window.basePrice = {};

// Abre el modal e inicializa estados por unidad
window.openModal = function(id) {
    const span   = document.getElementById('precio-'+id);
    const base   = parseInt(span.dataset.basePrice, 10);
    const qty    = parseInt(span.dataset.unidades, 10);
    window.basePrice[id] = base;
    window.selectedExtras[id] = {};
    window.removedBases[id] = {};
    for(let u = 1; u <= qty; u++){
        window.selectedExtras[id][u] = [];
        window.removedBases[id][u]   = [];
    }
    // Establecer precio inicial del modal
    span.textContent = base.toLocaleString('es-CL');
    document.getElementById('modal-'+id).classList.remove('hidden');
};

// Cierra el modal
window.closeModal = function(id) {
    document.getElementById('modal-'+id).classList.add('hidden');
};

// Gestiona ingredientes base (solo registro, no afecta precio)
window.toggleBase = function(id, unit, ingredient) {
    const cb  = document.getElementById(`${ingredient}-${id}-unit-${unit}`);
    const arr = window.removedBases[id][unit];
    if (!cb.checked) {
        arr.push(ingredient);
    } else {
        window.removedBases[id][unit] = arr.filter(i => i !== ingredient);
    }
};

// Gestiona ingredientes extra y actualiza precio
window.toggleExtra = function(id, unit, ingredient, price) {
    const cb  = document.getElementById(`extra-${id}-unit-${unit}-${ingredient}`);
    const arr = window.selectedExtras[id][unit];
    if (cb.checked) {
        if (arr.length < 3) {
            arr.push({ ingredient, price });
        } else {
            cb.checked = false;
            alert('Solo puedes elegir hasta 3 ingredientes adicionales por unidad.');
            return;
        }
    } else {
        window.selectedExtras[id][unit] = arr.filter(item => item.ingredient !== ingredient);
    }
    updatePrice(id);
};

// Recalcula y muestra el precio total: base + extras
window.updatePrice = function(id) {
    const span = document.getElementById('precio-'+id);
    let total = window.basePrice[id];  // precio base para todas unidades
    // Sumar precio de extras de cada unidad
    Object.values(window.selectedExtras[id]).forEach(arr => {
        total += arr.reduce((sum, e) => sum + e.price, 0);
    });
    span.textContent = total.toLocaleString('es-CL');
};

// Añade el producto personalizado al carrito via AJAX
window.addToCart = function(id) {
    const span   = document.getElementById('precio-'+id);
    const qty    = parseInt(span.dataset.unidades, 10);
    const extras  = window.selectedExtras[id];
    const removed = window.removedBases[id];
    const price   = parseInt(span.textContent.replace(/\./g, ''), 10);

    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            product_id:    id,
            unidades:      qty,
            removed_bases: removed,
            extras:        extras,
            price:         price,
        })
    })
    .then(res => res.json())
    .then(json => {
        if (json.success) {
            alert('Producto agregado al carrito');
            closeModal(id);
            // Opcional: actualizar contador de carrito en el UI
        }
    });
};