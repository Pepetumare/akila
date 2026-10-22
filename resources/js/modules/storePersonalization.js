// Prices and composition limits are calculated exclusively by ProductPricingService.
const states = new Map();

export function initLegacyModal(id) {
    const modal = document.getElementById(`productModal-${id}`);
    if (!states.has(id)) {
        states.set(id, {
            proteina: JSON.parse(modal.dataset.proteins).map(i => i.id),
            vegetal: JSON.parse(modal.dataset.vegetables).map(i => i.id),
            ingredients: JSON.parse(modal.dataset.all), version: 0,
        });
        const form = document.getElementById(`form-${id}`);
        form.addEventListener('change', () => quote(id));
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const version = states.get(id).version;
            if (await quote(id) && version === states.get(id).version) form.submit();
        });
    }
    render(id);
};

function render(id) {
    const state = states.get(id);
    const form = document.getElementById(`form-${id}`);
    form.querySelectorAll('.ing-input').forEach(input => input.remove());
    for (const [group, key, listId] of [['proteina', 'Proteínas', 'prot'], ['vegetal', 'vegetales', 'veg']]) {
        const list = document.getElementById(`${listId}-list-${id}`);
        list.replaceChildren();
        state[group].forEach((ingredientId, index) => {
            const ingredient = state.ingredients.find(i => i.id === ingredientId);
            const li = document.createElement('li');
            li.className = 'chip';
            li.append(document.createTextNode(`${ingredient?.nombre ?? ingredientId} ×10 `));
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-outline-warning';
            button.textContent = 'Cambiar';
            button.addEventListener('click', () => startSwap(id, index, group));
            li.append(button);
            list.append(li);
            const input = document.createElement('input');
            Object.assign(input, {type: 'hidden', name: `${key}[]`, value: ingredientId, className: 'ing-input'});
            form.append(input);
        });
    }
    quote(id);
}

function startSwap(id, index, group) {
    const state = states.get(id);
    state.swapping = {index, group};
    const select = document.getElementById(`swap-select-${id}`);
    select.replaceChildren(new Option('-- elige --', ''));
    state.ingredients.filter(i => i.tipo === group && i.id !== state[group][index])
        .forEach(i => select.add(new Option(i.nombre, i.id)));
    document.getElementById(`swap-box-${id}`).classList.remove('hidden');
}

window.confirmSwap = function (id) {
    const value = Number(document.getElementById(`swap-select-${id}`).value);
    if (!value) return;
    const state = states.get(id);
    state[state.swapping.group][state.swapping.index] = value;
    state.version++;
    render(id);
    window.cancelSwap(id);
};

window.cancelSwap = function (id) {
    document.getElementById(`swap-box-${id}`).classList.add('hidden');
};

async function quote(id) {
    const state = states.get(id);
    const sequence = state.sequence = (state.sequence ?? 0) + 1;
    const form = document.getElementById(`form-${id}`);
    const payload = Object.fromEntries(new FormData(form));
    payload['Proteínas'] = state.proteina;
    payload.vegetales = state.vegetal;
    delete payload['Proteínas[]'];
    delete payload['vegetales[]'];
    const display = document.getElementById(`price-${id}`);
    try {
        const response = await fetch('/cart/quote', {
            method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (state.sequence !== sequence) return false;
        if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] ?? 'Configuración no válida.');
        display.textContent = `$${Number(data.precio_unit).toLocaleString('es-CL')} CLP`;
        return true;
    } catch (error) {
        if (state.sequence === sequence) display.textContent = error.message;
        return false;
    }
}
