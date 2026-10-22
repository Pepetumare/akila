import { Modal } from 'bootstrap';
import { initLegacyModal } from './storePersonalization';

const loading = new Map();
const currency = value => `$${Number(value).toLocaleString('es-CL', { maximumFractionDigits: 2 })}`;

window.openProductModal = async function (id, url, trigger) {
    let modal = document.getElementById(`productModal-${id}`);
    try {
        if (!modal) {
            trigger.disabled = true;
            if (!loading.has(id)) loading.set(id, fetch(url, { headers: { Accept: 'text/html' }, cache: 'no-store' })
                .then(async response => {
                    if (!response.ok) throw new Error('No pudimos abrir este producto. Intenta nuevamente.');
                    const template = document.createElement('template');
                    template.innerHTML = await response.text();
                    const element = template.content.firstElementChild;
                    document.body.append(element);
                    return element;
                }).finally(() => loading.delete(id)));
            modal = await loading.get(id);
        }
        if (modal.dataset.menuConfig) {
            if (!modal.dataset.initialized) initialize(modal);
        } else {
            initLegacyModal(id);
        }
        Modal.getOrCreateInstance(modal).show();
    } catch (error) {
        let message = trigger.parentElement.querySelector('[data-modal-error]');
        if (!message) {
            message = document.createElement('p');
            message.dataset.modalError = '';
            message.setAttribute('role', 'alert');
            trigger.after(message);
        }
        message.textContent = error.message;
    } finally {
        trigger.disabled = false;
    }
};

function initialize(modal) {
    modal.dataset.initialized = 'true';
    const schema = JSON.parse(modal.dataset.menuConfig);
    const form = modal.querySelector('[data-menu-form]');
    const button = modal.querySelector('[data-menu-add]');
    const errorBox = modal.querySelector('[data-menu-error]');
    let revision = 0;
    let controller;
    let timer;
    let valid = false;
    let submitting = false;

    function errors(message = '') {
        errorBox.hidden = !message;
        errorBox.textContent = message;
    }

    function groupsPayload(container) {
        return [...container.querySelectorAll('input[data-menu-option]:checked')].map(input => Number(input.value));
    }

    function payload() {
        return {
            product_id: schema.product_id, unidades: Number(form.elements.unidades.value), version: 2,
            ...(schema.customizable ? {
                blocks: [...form.querySelectorAll('[data-menu-block]')].map(block => ({
                    block_id: Number(block.dataset.menuBlock), option_ids: groupsPayload(block),
                })),
                option_ids: [...form.querySelectorAll(':scope > [data-menu-group] input[data-menu-option]:checked')].map(input => Number(input.value)),
            } : {}),
            removed_base_ids: [...form.querySelectorAll('input[name="removed_base_ids[]"]:checked')].map(input => Number(input.value)),
        };
    }

    function completeness() {
        let message = '';
        form.querySelectorAll('[data-menu-block]').forEach(block => {
            let complete = true;
            if (schema.customizable) block.querySelectorAll('[data-menu-group]').forEach(group => {
                const count = group.querySelectorAll('input:checked').length;
                if (count < Number(group.dataset.min) || count > Number(group.dataset.max)) complete = false;
            });
            block.querySelector('[data-block-status]').textContent = complete ? (schema.customizable ? 'Completo' : 'Incluido') : 'Pendiente';
            if (!complete && !message) message = `Completa la elección del ${block.dataset.blockLabel} antes de agregar al carrito.`;
        });
        if (schema.customizable) form.querySelectorAll(':scope > [data-menu-group]').forEach(group => {
            const count = group.querySelectorAll('input:checked').length;
            if ((count < Number(group.dataset.min) || count > Number(group.dataset.max)) && !message) message = 'Completa las opciones del producto antes de agregar al carrito.';
        });
        return message;
    }

    function summary(quote) {
        const list = modal.querySelector('[data-menu-summary]');
        list.replaceChildren();
        schema.blocks.forEach((block, index) => {
            let names;
            if (quote) {
                const selected = quote.detalle.Bloques.find(b => b.block_id === block.id);
                names = selected.grupos.flatMap(group => group.opciones.map(option => option.nombre));
            } else {
                const ids = payload().blocks?.find(b => b.block_id === block.id)?.option_ids ?? [];
                names = block.groups.flatMap(group => group.options.filter(option => ids.includes(option.id)).map(option => option.nombre));
            }
            const item = document.createElement('li');
            item.textContent = `${block.label}: ${names.length ? names.join(' · ') : 'Pendiente'}`;
            list.append(item);
        });
        schema.groups.forEach(group => {
            const ids = payload().option_ids ?? [];
            const names = quote
                ? (quote.detalle.Grupos.find(g => g.group_id === group.id)?.opciones ?? []).map(o => o.nombre)
                : group.options.filter(o => ids.includes(o.id)).map(o => o.nombre);
            const item = document.createElement('li');
            item.textContent = `${group.nombre}: ${names.length ? names.join(' · ') : 'Pendiente'}`;
            list.append(item);
        });
        for (const [selector, value] of [['base', quote?.precio_base], ['extras', quote?.recargo], ['total', quote?.total]]) {
            modal.querySelector(`[data-price-${selector}]`).textContent = value === undefined ? '—' : currency(value);
        }
    }

    async function post(url, body, signal) {
        const response = await fetch(url, {
            method: 'POST', credentials: 'same-origin', signal,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form.elements._token.value },
            body: JSON.stringify(body),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat().join(' ') || 'No pudimos validar la selección. Intenta nuevamente.');
        return data;
    }

    async function quote() {
        const current = revision;
        valid = false;
        button.disabled = true;
        controller?.abort();
        summary();
        const missing = completeness();
        errors(missing);
        if (missing) return false;
        controller = new AbortController();
        try {
            const result = await post(form.dataset.quoteUrl, payload(), controller.signal);
            if (current !== revision) return false;
            summary(result);
            errors();
            valid = true;
            button.disabled = submitting;
            return true;
        } catch (error) {
            if (current === revision && error.name !== 'AbortError') errors(error.message);
            return false;
        }
    }

    form.addEventListener('change', () => {
        revision++;
        valid = false;
        button.disabled = true;
        controller?.abort();
        summary();
        errors(completeness());
        clearTimeout(timer);
        timer = setTimeout(quote, 200);
    });
    form.querySelectorAll('[data-clear-group]').forEach(clear => clear.addEventListener('click', () => {
        clear.closest('[data-menu-group]').querySelectorAll('input').forEach(input => { input.checked = false; });
        form.dispatchEvent(new Event('change', { bubbles: true }));
    }));
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submitting) return;
        const current = revision;
        submitting = true;
        button.disabled = true;
        try {
            if (!await quote() || current !== revision) return;
            // Adding revalidates independently in Laravel, even after a valid quote.
            await post(form.action, payload());
            window.location.assign(form.dataset.cartUrl);
        } catch (error) {
            errors(error.message);
        } finally {
            submitting = false;
            button.disabled = !valid;
        }
    });
    quote();
}
