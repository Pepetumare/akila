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