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
        if (!modal.dataset.lifecycleBound) {
            modal.dataset.lifecycleBound = 'true';
            let position;
            let previousScrollBehavior;
            modal.addEventListener('show.bs.modal', () => {
                position = { x: window.scrollX, y: window.scrollY };
                previousScrollBehavior = document.documentElement.style.scrollBehavior;
                document.documentElement.style.scrollBehavior = 'auto';
                // Bootstrap locks overflow and compensates for the scrollbar. Fixed body
                // also prevents touch scrolling from moving the document behind the dialog.
                Object.assign(document.body.style, { position: 'fixed', top: `-${position.y}px`, left: '0', width: '100%' });
            });
            modal.addEventListener('hidden.bs.modal', () => {
                Object.assign(document.body.style, { position: '', top: '', left: '', width: '' });
                window.scrollTo(position.x, position.y);
                document.documentElement.style.scrollBehavior = previousScrollBehavior;
                modal.menuOpener?.focus({ preventScroll: true });
            });
        }
        modal.menuOpener = trigger;
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
    const status = modal.querySelector('[data-quote-status]');
    const blocks = [...form.querySelectorAll('[data-menu-block]')];
    const limits = JSON.parse(modal.dataset.menuLimits || '[]');

    // Tabs change presentation only; hidden choices remain part of the same fieldset.
    form.querySelectorAll('[data-menu-group]').forEach(group => {
        const tabs = [...group.querySelectorAll('[data-option-tab]')];
        function activate(tab, focus = false) {
            tabs.forEach(item => {
                const selected = item === tab;
                item.setAttribute('aria-selected', String(selected));
                item.tabIndex = selected ? 0 : -1;
                group.querySelector(`[data-option-panel="${item.dataset.optionTab}"]`).hidden = !selected;
            });
            if (focus) tab.focus({ preventScroll: true });
        }
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activate(tab));
            tab.addEventListener('keydown', event => {
                const target = { ArrowRight: (index + 1) % tabs.length, ArrowLeft: (index - 1 + tabs.length) % tabs.length, Home: 0, End: tabs.length - 1 }[event.key];
                if (target === undefined) return;
                event.preventDefault();
                activate(tabs[target], true);
            });
        });
    });

    function restrictions() {
        const inputs = [...form.querySelectorAll('input[data-menu-option]')];
        inputs.forEach(input => {
            const group = input.closest('[data-menu-group]');
            const hints = [];
            let blocked = false;
            limits.filter(rule => rule.option_ids.includes(Number(input.value))).forEach(rule => {
                const count = inputs.filter(i => i.checked && rule.option_ids.includes(Number(i.value))).length;
                hints.push(`${count}/${rule.max}${count >= rule.max ? ' · Máximo alcanzado' : ''}`);
                if (!input.checked && count >= rule.max) blocked = true;
            });
            if (!input.checked && input.type === 'checkbox' && group.querySelectorAll('input:checked').length >= Number(group.dataset.max)) {
                blocked = true;
                hints.push(`Máximo ${group.dataset.max} opciones en este grupo`);
            }
            input.disabled = blocked;
            input.setAttribute('aria-disabled', String(blocked));
            const hint = input.closest('label')?.querySelector('[data-option-limit]');
            if (hint) {
                hint.textContent = hints.join(' · ');
                hint.id = `limit-${schema.product_id}-${group.dataset.menuGroup}-${input.name.replace(/[^a-z0-9]/gi, '')}-${input.value}`;
                if (hints.length) input.setAttribute('aria-describedby', hint.id);
                else input.removeAttribute('aria-describedby');
            }
        });
    }

    function navigate(id, focus = true) {
        const body = modal.querySelector('.akila-config-body');
        blocks.forEach(block => {
            block.open = block.dataset.menuBlock === String(id);
            block.querySelector('summary').setAttribute('aria-expanded', String(block.open));
            if (block.open && focus) {
                block.querySelector('summary').focus({ preventScroll: true });
                // Explicit navigation only. Never scroll the document or an ancestor.
                body.scrollTop += block.getBoundingClientRect().top - body.getBoundingClientRect().top - 16;
            }
        });
        modal.querySelectorAll('.akila-unit-nav [data-go-block]').forEach(button => {
            if (button.dataset.goBlock === String(id)) button.setAttribute('aria-current', 'step');
            else button.removeAttribute('aria-current');
        });
    }
    modal.querySelectorAll('[data-go-block]').forEach(button => button.addEventListener('click', () => navigate(button.dataset.goBlock)));
    blocks.forEach(block => block.addEventListener('toggle', () => {
        if (block.open) navigate(block.dataset.menuBlock, false);
    }));
    if (blocks.length) navigate(blocks[0].dataset.menuBlock, false);

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
            } : {}),
            ...((schema.customizable || form.querySelector(':scope > [data-menu-group] input[data-menu-option]:checked')) ? {
                option_ids: [...form.querySelectorAll(':scope > [data-menu-group] input[data-menu-option]:checked')].map(input => Number(input.value)),
            } : {}),
            removed_base_ids: [...form.querySelectorAll('input[name="removed_base_ids[]"]:checked')].map(input => Number(input.value)),
        };
    }

    function completeness() {
        let message = '';
        let completed = 0;
        const required = [...form.querySelectorAll('[data-menu-group]')].filter(group => Number(group.dataset.min) > 0 && group.querySelector('input[data-menu-option]'));
        required.forEach(group => {
            const count = group.querySelectorAll('input:checked').length;
            if (count >= Number(group.dataset.min) && count <= Number(group.dataset.max)) completed++;
        });
        const progress = modal.querySelector('[data-menu-progress-bar]');
        progress.hidden = !required.length;
        progress.max = required.length || 1;
        progress.value = completed;
        form.querySelectorAll('[data-menu-block]').forEach(block => {
            let complete = true;
            if (schema.customizable) block.querySelectorAll('[data-menu-group]').forEach(group => {
                const count = group.querySelectorAll('input:checked').length;
                if (count < Number(group.dataset.min) || count > Number(group.dataset.max)) complete = false;
            });
            block.querySelector('[data-block-status]').textContent = complete ? (schema.customizable ? '✓ Completado' : 'Incluido') : 'Pendiente';
            block.dataset.complete = String(complete);
            modal.querySelector(`.akila-unit-nav [data-go-block="${block.dataset.menuBlock}"] [data-nav-status]`).textContent = complete ? '✓' : String(blocks.indexOf(block) + 1);
            if (!complete && !message) message = `Selecciona las opciones de ${block.dataset.blockLabel}.`;
        });
        if (schema.customizable) form.querySelectorAll(':scope > [data-menu-group]').forEach(group => {
            const count = group.querySelectorAll('input:checked').length;
            if ((count < Number(group.dataset.min) || count > Number(group.dataset.max)) && !message) message = `Selecciona ${group.querySelector('legend').textContent.trim().toLowerCase()}.`;
        });
        const done = blocks.length ? blocks.filter(block => block.dataset.complete === 'true').length : completed;
        const total = blocks.length || required.length;
        modal.querySelector('[data-menu-progress]').textContent = total ? `${done} de ${total} ${blocks.length ? 'completados' : 'elecciones'}` : 'Incluido';
        progress.max = total || 1;
        progress.value = done;
        progress.hidden = !total;
        status.textContent = message || 'Validando precio';
        button.textContent = message ? 'Completa tu selección' : 'Agregar al carrito →';
        restrictions();
        return message;
    }

    function summary(quote) {
        const list = modal.querySelector('[data-menu-summary]');
        list.replaceChildren();
        const grouped = new Map();
        schema.blocks.forEach(block => {
            let names;
            if (quote) {
                const selected = quote.detalle.Bloques.find(b => b.block_id === block.id);
                names = (selected?.grupos ?? []).flatMap(group => group.opciones.map(option => option.nombre));
            } else {
                const ids = payload().blocks?.find(b => b.block_id === block.id)?.option_ids ?? [];
                names = block.groups.flatMap(group => group.options.filter(option => ids.includes(option.id) || (!schema.customizable && group.min === 1 && group.options.length === 1)).map(option => option.nombre));
            }
            const preview = form.querySelector(`[data-menu-block="${block.id}"] [data-block-preview]`);
            if (preview) preview.textContent = names.length ? names.join(' · ') : 'Elige tus ingredientes';
            const label = names.length ? names.join(' · ') : 'Por elegir';
            grouped.set(label, (grouped.get(label) ?? 0) + 1);
        });
        if (schema.customizable && schema.blocks.length) {
            schema.blocks.forEach(block => {
                const item = document.createElement('li');
                item.textContent = `${block.label}: ${form.querySelector(`[data-menu-block="${block.id}"] [data-block-preview]`)?.textContent ?? 'Pendiente'}`;
                list.append(item);
            });
        } else grouped.forEach((count, label) => {
            const item = document.createElement('li');
            item.textContent = `${count} × ${label}`;
            list.append(item);
        });
        schema.groups.forEach(group => {
            const ids = payload().option_ids ?? [];
            const names = quote
                ? (quote.detalle.Grupos.find(g => g.group_id === group.id)?.opciones ?? []).map(o => o.nombre)
                : group.options.filter(o => ids.includes(o.id) || (!schema.customizable && group.min === 1 && group.options.length === 1)).map(o => o.nombre);
            if (!group.options.length || (!names.length && group.min === 0)) return;
            const item = document.createElement('li');
            item.textContent = `${group.nombre}: ${names.length ? names.join(' · ') : (group.min === 0 ? 'Sin adicionales' : 'Pendiente')}`;
            list.append(item);
        });
        const removed = payload().removed_base_ids;
        if (removed.length) {
            const item = document.createElement('li');
            item.textContent = `Sin ${schema.base.filter(base => removed.includes(base.id)).map(base => base.nombre).join(' · ')}`;
            list.append(item);
        }
        if (!list.children.length) {
            const item = document.createElement('li');
            item.textContent = 'Composición incluida';
            list.append(item);
        }
        const sauces = modal.querySelector('[data-menu-sauces]');
        const included = quote?.detalle['Salsas incluidas'] ?? [];
        if (quote) {
            sauces.hidden = !included.length;
            sauces.textContent = included.map(sauce => `${sauce.cantidad} ${sauce.cantidad === 1 ? 'pote' : 'potes'} de ${sauce.nombre}`).join(' · ');
        }
        // Keep confirmed included sauces visible while a new selection is being quoted.
        const includedSection = modal.querySelector('[data-included-sauces]');
        if (quote) {
            includedSection.hidden = !included.length;
            includedSection.querySelector('ul').replaceChildren(...included.map(sauce => {
                const item = document.createElement('li');
                item.textContent = `${sauce.cantidad} × ${sauce.nombre}`;
                return item;
            }));
        }
        modal.querySelector('[data-mobile-summary] ul').replaceChildren(...[...list.children].map(item => item.cloneNode(true)));
        for (const [selector, value] of [['base', quote?.precio_base], ['extras', quote?.recargo], ['total', quote?.total]]) {
            if (value !== undefined) modal.querySelectorAll(`[data-price-${selector}]`).forEach(node => { node.textContent = currency(value); });
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
        clearTimeout(timer);
        const current = revision;
        valid = false;
        button.disabled = true;
        status.textContent = 'Actualizando precio…';
        controller?.abort();
        summary();
        const missing = completeness();
        if (!missing) status.textContent = 'Actualizando precio…';
        errors();
        if (missing) {
            form.removeAttribute('aria-busy');
            return false;
        }
        form.setAttribute('aria-busy', 'true');
        controller = new AbortController();
        try {
            const result = await post(form.dataset.quoteUrl, payload(), controller.signal);
            if (current !== revision) return false;
            summary(result);
            errors();
            valid = true;
            status.textContent = 'Precio confirmado';
            button.disabled = submitting;
            return true;
        } catch (error) {
            if (current === revision && error.name !== 'AbortError') {
                errors(error instanceof TypeError || error instanceof SyntaxError
                    ? 'No pudimos actualizar el precio. Intenta nuevamente.' : error.message);
                status.textContent = 'Revisa tu selección';
            }
            return false;
        } finally {
            if (current === revision) form.removeAttribute('aria-busy');
        }
    }

    form.addEventListener('change', () => {
        revision++;
        valid = false;
        button.disabled = true;
        controller?.abort();
        summary();
        errors();
        completeness();
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
        button.textContent = 'Agregando…';
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
            button.textContent = 'Agregar al carrito →';
        }
    });
    modal.addEventListener('hide.bs.modal', () => {
        clearTimeout(timer);
        controller?.abort();
        revision++;
        valid = false;
        button.disabled = true;
        form.removeAttribute('aria-busy');
    });
    modal.addEventListener('shown.bs.modal', () => {
        modal.querySelector('.akila-close').focus({ preventScroll: true });
        quote();
    });
}
