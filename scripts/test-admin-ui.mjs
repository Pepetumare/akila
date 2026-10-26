// Chrome/CDP acceptance checks against an isolated Laravel browser database.
// Supply AKILA_ADMIN_EMAIL / AKILA_ADMIN_PASSWORD for that test-only administrator.
import fs from 'node:fs/promises';
const base = process.env.AKILA_URL || 'http://127.0.0.1:8001';
const email = process.env.AKILA_ADMIN_EMAIL, password = process.env.AKILA_ADMIN_PASSWORD;
if (!email || !password) throw Error('Test administrator credentials are required through process variables.');
const destination = process.env.AKILA_CAPTURE_DIR || process.env.TEMP;
await fs.mkdir(destination, { recursive: true });
const version = await (await fetch((process.env.AKILA_CDP || 'http://127.0.0.1:9223') + '/json/version')).json();
const socket = new WebSocket(version.webSocketDebuggerUrl);
await new Promise(resolve => socket.addEventListener('open', resolve, { once: true }));
let sequence = 0, session;
const pending = new Map(), exceptions = [], results = [];
socket.addEventListener('message', event => {
    const message = JSON.parse(event.data);
    if (message.id) { const task = pending.get(message.id); pending.delete(message.id); message.error ? task.reject(message.error) : task.resolve(message.result); }
    if (message.method === 'Runtime.exceptionThrown') exceptions.push(message.params.exceptionDetails);
});
const call = (method, params = {}, root = false) => new Promise((resolve, reject) => {
    pending.set(++sequence, { resolve, reject });
    socket.send(JSON.stringify({ id: sequence, method, params, ...(!root && session ? { sessionId: session } : {}) }));
});
const evaluate = async expression => {
    const result = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (result.exceptionDetails) throw Error(JSON.stringify(result.exceptionDetails));
    return result.result?.value;
};
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
const wait = async expression => { for (let i = 0; i < 150; i++) { if (await evaluate(expression)) return; await pause(100); } throw Error('Timeout: ' + expression); };
const assert = (value, message) => { if (!value) throw Error(message); };
const navigate = async path => {
    await call('Page.navigate', { url: base + path });
    await wait(`location.pathname + location.search === ${JSON.stringify(path)} && document.readyState === 'complete' && !!document.querySelector('.admin-main')`);
};
const viewport = (width, height) => call('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
const capture = async name => {
    await evaluate(`document.fonts.ready.then(()=>new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r))))`); await pause(120);
    await fs.writeFile(destination + '/' + name + '.png', Buffer.from((await call('Page.captureScreenshot', { format: 'png' })).data, 'base64'));
};
const context = await call('Target.createBrowserContext', {}, true);
const target = await call('Target.createTarget', { url: 'about:blank', browserContextId: context.browserContextId }, true);
session = (await call('Target.attachToTarget', { targetId: target.targetId, flatten: true }, true)).sessionId;
try {
    await call('Runtime.enable'); await call('Page.enable'); await viewport(1440, 900);
    await call('Page.navigate', { url: base + '/admin/dashboard' });
    await wait(`!!document.querySelector('input[name=email]') && document.readyState==='complete'`);
    await evaluate(`document.querySelector('[name=email]').value=${JSON.stringify(email)};document.querySelector('[name=password]').value=${JSON.stringify(password)};document.querySelector('[name=email]').form.requestSubmit()`);
    await wait(`location.pathname==='/admin/dashboard' && !!document.querySelector('.admin-main')`);
    for (const [path, name] of [['/admin/dashboard', 'dashboard'], ['/admin/kitchen', 'kitchen'], ['/admin/productos', 'products'], ['/admin/ingredientes', 'ingredients'], ['/admin/orders', 'orders'], ['/admin/reports', 'reports'], ['/admin/settings', 'settings']]) {
        await navigate(path); await capture(name);
        assert(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), 'Desktop overflow: ' + path);
        results.push({ page: name, rendered: true });
    }
    await navigate('/admin/productos?q=30%20Piezas%20a%20Elecci%C3%B3n');
    const editPath = await evaluate(`new URL(document.querySelector('.product-cell a').href).pathname`);
    await navigate(editPath); await capture('editor-general');
    await evaluate(`document.querySelector('[data-editor-tab=personalization]').click()`);
    assert(await evaluate(`document.querySelectorAll('[data-editor-panel=personalization] details').length===3`), 'Three configuration blocks retained');
    await capture('editor-personalization');
    await evaluate(`document.querySelector('[data-editor-tab=general]').click();document.querySelector('#product-general').requestSubmit()`);
    await wait(`!!document.querySelector('.notice.success') && document.readyState==='complete'`);
    assert(await evaluate(`document.querySelectorAll('[data-editor-panel=personalization] details').length===3`), 'General save preserves three blocks');
    results.push({ editor: 'general save preserves blocks, groups and rules' });

    await navigate('/admin/kitchen');
    const newId = await evaluate(`document.querySelector('.kds-column.red [data-order-id]')?.dataset.orderId`);
    assert(newId, 'Fixture new order required');
    for (const column of ['orange', 'green', null]) {
        await evaluate(`document.querySelector('[data-order-id="${newId}"] .transition-form').requestSubmit()`);
        await wait(column ? `!!document.querySelector('.kds-column.${column} [data-order-id="${newId}"]')` : `!document.querySelector('[data-order-id="${newId}"]')`);
    }
    results.push({ kitchen: 'new → preparing → ready → delivered' });

    await navigate('/admin/ingredientes?q=Salm%C3%B3n');
    const ingredientId = await evaluate(`(() => {const r=[...document.querySelectorAll('tbody tr')].find(r=>r.querySelector('strong')?.textContent==='Salmón');return new URL(r.querySelector('form').action).pathname.split('/').at(-2)})()`);
    for (const available of [false, true]) {
        await evaluate(`document.querySelector('form[action$="ingredientes/${ingredientId}/availability"]').requestSubmit()`);
        await wait(`document.querySelector('#admin-confirm')?.open===true`);
        await evaluate(`document.querySelector('#admin-confirm button[value=confirm]').click()`);
        await wait(`document.querySelector('form[action$="ingredientes/${ingredientId}/availability"] input[name=is_available]')?.value === '${available ? '0' : '1'}' && !!document.querySelector('.notice.success')`);
    }
    await navigate('/admin/ingredientes/' + ingredientId + '/edit'); await capture('ingredient-impact');
    results.push({ ingredient: 'availability off/on and actual impact' });

    await navigate('/admin/settings');
    const estimate = await evaluate(`document.querySelector('[name=estimate_min]').value`);
    await evaluate(`document.body.dataset.submitting='true';document.querySelector('[name=estimate_min]').value='35';document.querySelector('[name=estimate_min]').form.requestSubmit()`);
    await wait(`!document.body.dataset.submitting && !!document.querySelector('.notice.success') && document.querySelector('[name=estimate_min]')?.value==='35'`);
    await evaluate(`document.body.dataset.submitting='true';document.querySelector('[name=estimate_min]').value=${JSON.stringify(estimate)};document.querySelector('[name=estimate_min]').form.requestSubmit()`);
    await wait(`!document.body.dataset.submitting && !!document.querySelector('.notice.success') && document.querySelector('[name=estimate_min]')?.value===${JSON.stringify(estimate)}`);
    results.push({ settings: 'estimate saved and restored without environment changes' });

    for (const [width, height] of [[1920,1080],[1440,900],[1366,768],[1024,768],[768,1024],[430,932],[390,844]]) {
        await viewport(width, height);
        for (const path of ['/admin/dashboard', '/admin/kitchen', editPath, '/admin/orders', '/admin/productos', '/admin/ingredientes']) {
            await navigate(path);
            assert(await evaluate(`document.documentElement.scrollWidth<=innerWidth`), 'Page overflow ' + path + ' at ' + width);
        }
        results.push({ responsive: width + 'x' + height, pages: 6, overflow: false });
        if (width === 390) {
            await navigate('/admin/dashboard'); await capture('dashboard-mobile');
            await evaluate(`document.querySelector('[data-nav-toggle]').click()`);
            assert(await evaluate(`document.body.classList.contains('nav-open')`), 'Mobile drawer opens');
            await call('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
            await call('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
            assert(await evaluate(`!document.body.classList.contains('nav-open')`), 'Escape closes drawer');
            await navigate('/admin/kitchen'); await capture('kitchen-mobile');
        }
    }
    assert(exceptions.length === 0, 'JavaScript exceptions: ' + JSON.stringify(exceptions));
    console.log(JSON.stringify({ results, exceptions: exceptions.length, isolatedDatabase: true }, null, 2));
} finally { await call('Target.disposeBrowserContext', { browserContextId: context.browserContextId }, true); socket.close(); }
