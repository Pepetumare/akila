import 'alpinejs';

// resources/js/app.js
window.addEventListener('scroll', () => {
    const header = document.querySelector('header');
    if (window.scrollY > 50) {
      header.classList.replace('header-transparent', 'header-solid');
    } else {
      header.classList.replace('header-solid', 'header-transparent');
    }
  });
  

/// Toast utility for user feedback
function showToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.className = [
    'fixed', 'top-4', 'right-4', 'max-w-sm', 'w-full', 'p-4', 'mb-2', 'rounded',
    'shadow-lg', 'text-white', type === 'success' ? 'bg-green-500' : 'bg-red-500'
  ].join(' ');
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.classList.add('opacity-0', 'transition', 'duration-500');
    setTimeout(() => document.body.removeChild(toast), 500);
  }, 3000);
}

/// Modal & product customization logic
window.selectedExtras = {};
window.removedBases   = {};
window.basePrice      = {};

window.openModal = function(id) {
  const span = document.getElementById('precio-' + id);
  const base = parseInt(span.dataset.basePrice, 10);
  const qty  = parseInt(span.dataset.unidades, 10);
  window.basePrice[id] = base;
  window.selectedExtras[id] = {};
  window.removedBases[id]   = {};
  for (let u = 1; u <= qty; u++) {
    window.selectedExtras[id][u] = [];
    window.removedBases[id][u]   = [];
  }
  span.textContent = base.toLocaleString('es-CL');
  document.getElementById('modal-' + id).classList.remove('hidden');
};

window.closeModal = function(id) {
  document.getElementById('modal-' + id).classList.add('hidden');
};

window.toggleBase = function(id, unit, ingredient) {
  const cb = document.getElementById(`${ingredient}-${id}-unit-${unit}`);
  const arr = window.removedBases[id][unit] || [];
  if (!cb.checked) arr.push(ingredient);
  else window.removedBases[id][unit] = arr.filter(i => i !== ingredient);
};

window.toggleExtra = function(id, unit, ingredient, price) {
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

window.updatePrice = function(id) {
  const span = document.getElementById('precio-' + id);
  let total = window.basePrice[id] || 0;
  Object.values(window.selectedExtras[id] || {}).forEach(arr => {
    total += arr.reduce((sum, e) => sum + e.price, 0);
  });
  span.textContent = total.toLocaleString('es-CL');
};

window.addToCart = async function(id) {
  const span    = document.getElementById('precio-' + id);
  const qty     = parseInt(span.dataset.unidades, 10) || 1;
  const extras  = window.selectedExtras[id] || {};
  const removed = window.removedBases[id]   || {};
  const price   = parseInt(span.textContent.replace(/\./g, ''), 10);

  try {
    const res = await fetch('/cart/add', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ product_id: id, unidades: qty, removed_bases: removed, extras, price })
    });
    const json = await res.json();
    if (json.success) {
      // Update cart count
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
