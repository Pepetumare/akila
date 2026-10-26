// Admin-only entry: no storefront listeners, commercial state or external dependencies.
const sidebar = document.querySelector('.admin-sidebar');
const toggle = document.querySelector('[data-nav-toggle]');
const scrim = document.querySelector('[data-nav-close]');
const navigation = open => {
    document.body.classList.toggle('nav-open', open);
    toggle?.setAttribute('aria-expanded', String(open));
    if (scrim) scrim.hidden = !open;
    if (open) sidebar?.querySelector('a')?.focus();
    else toggle?.focus({ preventScroll: true });
};
toggle?.addEventListener('click', () => navigation(!document.body.classList.contains('nav-open')));
scrim?.addEventListener('click', () => navigation(false));
document.addEventListener('keydown', event => {
    if (document.body.classList.contains('nav-open')) {
        if (event.key === 'Escape') navigation(false);
        if (event.key === 'Tab') {
            const nodes = [toggle, ...sidebar.querySelectorAll('a,button')].filter(Boolean);
            if (event.shiftKey && document.activeElement === nodes[0]) { event.preventDefault(); nodes.at(-1).focus(); }
            else if (!event.shiftKey && document.activeElement === nodes.at(-1)) { event.preventDefault(); nodes[0].focus(); }
        }
    }
});

const confirmDialog = document.querySelector('#admin-confirm');
document.addEventListener('submit', event => {
    const form = event.target;
    if (!form.matches('[data-confirm]') || form.dataset.confirmed) return;
    event.preventDefault();
    confirmDialog.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
    confirmDialog.showModal();
    confirmDialog.addEventListener('close', () => {
        if (confirmDialog.returnValue === 'confirm') {
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        }
    }, { once: true });
});

document.querySelectorAll('[data-editor]').forEach(editor => {
    const tabs = [...editor.querySelectorAll('[data-editor-tab]')];
    const activate = tab => {
        tabs.forEach(item => { item.setAttribute('aria-selected', String(item === tab)); item.tabIndex = item === tab ? 0 : -1; });
        editor.querySelectorAll('[data-editor-panel]').forEach(panel => { panel.hidden = panel.dataset.editorPanel !== tab.dataset.editorTab; });
        const form = editor.querySelector('#product-general');
        if (form) form.hidden = !['general', 'image', 'availability'].includes(tab.dataset.editorTab);
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activate(tab));
        tab.addEventListener('keydown', event => {
            const offset = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
            if (!offset && !['Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const next = tabs[event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + offset + tabs.length) % tabs.length];
            activate(next); next.focus();
        });
    });
    // Required fields may be in another tab: reveal before the browser focuses them.
    editor.addEventListener('invalid', event => {
        const panel = event.target.closest('[data-editor-panel]');
        const tab = tabs.find(t => t.dataset.editorTab === panel?.dataset.editorPanel);
        if (tab) activate(tab);
    }, true);
});

const kds = document.querySelector('[data-kds]');
if (kds) {
    const board = kds.querySelector('[data-kds-board]');
    const status = kds.querySelector('[data-poll-status]');
    let running = false, busy = false, sound = false, audio, previous = new Set([...board.querySelectorAll('.kds-column.red [data-order-id]')].map(n => Number(n.dataset.orderId)));
    const elapsed = () => board.querySelectorAll('[data-elapsed]').forEach(node => {
        node.textContent = Math.max(0, Math.floor((Date.now() - Date.parse(node.dataset.elapsed)) / 60000)) + ' min';
    });
    const poll = async () => {
        if (running || busy || document.hidden || document.querySelector('dialog[open]')) return;
        running = true;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(kds.dataset.pollUrl, { headers: { Accept: 'application/json' }, signal: controller.signal });
            if (!response.ok) throw Error('poll');
            const data = await response.json();
            const next = new Set(data.new_ids);
            if (sound && [...next].some(id => !previous.has(id))) {
                const oscillator = audio.createOscillator(), gain = audio.createGain();
                oscillator.connect(gain); gain.connect(audio.destination); gain.gain.value = 0.06; oscillator.frequency.value = 740;
                oscillator.start(); oscillator.stop(audio.currentTime + 0.15);
            }
            previous = next;
            // Preserve user-opened snapshot details and avoid interrupting keyboard focus.
            if (!board.contains(document.activeElement)) {
                const opened = [...board.querySelectorAll('details[open]')].map(n => [n.closest('[data-order-id]').dataset.orderId, [...n.closest('[data-order-id]').querySelectorAll('details')].indexOf(n)]);
                const scroll = board.scrollLeft;
                board.innerHTML = data.html;
                opened.forEach(([id, index]) => { const detail = board.querySelector('[data-order-id="' + id + '"]')?.querySelectorAll('details')[index]; if (detail) detail.open = true; });
                board.scrollLeft = scroll;
            }
            elapsed(); status.textContent = 'Actualizado ' + new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
        } catch { status.textContent = 'Sin conexión. Se conserva la vista; reintentando…'; }
        finally { clearTimeout(timeout); running = false; }
    };
    board.addEventListener('submit', async event => {
        const form = event.target;
        if (!form.matches('.transition-form')) return;
        event.preventDefault(); if (busy) return; busy = true;
        const button = form.querySelector('button'); button.disabled = true;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
            if (!response.ok) { const data = await response.json(); throw Error(Object.values(data.errors || {}).flat()[0] || 'No se pudo actualizar el pedido.'); }
            // Allow the refreshed board to replace the submitted button only after success.
            button.blur();
            status.textContent = 'Estado actualizado';
        } catch (error) { status.textContent = error.message; }
        finally { button.disabled = false; busy = false; await poll(); }
    });
    kds.querySelector('[data-kds-sound]')?.addEventListener('click', async event => {
        sound = !sound;
        if (sound) { audio ||= new (window.AudioContext || window.webkitAudioContext)(); await audio.resume(); }
        event.currentTarget.textContent = 'Sonido ' + (sound ? 'ON' : 'OFF'); event.currentTarget.setAttribute('aria-pressed', String(sound));
    });
    kds.querySelector('[data-fullscreen]')?.addEventListener('click', async () => {
        try { if (!document.fullscreenElement) await document.documentElement.requestFullscreen(); else await document.exitFullscreen(); }
        catch { status.textContent = 'El navegador no permitió pantalla completa.'; }
    });
    document.addEventListener('fullscreenchange', () => document.body.classList.toggle('kds-fullscreen', Boolean(document.fullscreenElement)));
    const timer = setInterval(poll, 15000), clock = setInterval(elapsed, 10000);
    window.addEventListener('pagehide', () => { clearInterval(timer); clearInterval(clock); audio?.close(); }, { once: true });
    elapsed();
}
