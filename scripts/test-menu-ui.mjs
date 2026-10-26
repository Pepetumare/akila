// Controlled Chrome checks. Start the local app and Chrome with remote debugging first.
// Uses an isolated browser context; never places orders or calls an external payment API.
// node scripts/test-menu-ui.mjs (Node 22+). AKILA_URL / AKILA_CDP may override localhost.
import fs from 'node:fs/promises';
const base = process.env.AKILA_URL || 'http://127.0.0.1:8000';
const version = await (await fetch((process.env.AKILA_CDP || 'http://127.0.0.1:9223') + '/json/version')).json();
const socket = new WebSocket(version.webSocketDebuggerUrl);
await new Promise(resolve => socket.addEventListener('open', resolve, { once: true }));
let sequence = 0, session;
const pending = new Map(), exceptions = [], results = [];
socket.addEventListener('message', event => {
    const message = JSON.parse(event.data);
    if (message.id) {
        const task = pending.get(message.id);
        pending.delete(message.id);
        message.error ? task.reject(message.error) : task.resolve(message.result);
    }
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
const wait = async expression => {
    for (let i = 0; i < 250; i++) { if (await evaluate(expression)) return; await pause(100); }
    throw Error('Timeout: ' + expression);
};
const assert = (value, message) => { if (!value) throw Error(message); };
const captureDirectory = process.env.AKILA_CAPTURE_DIR || process.env.TEMP || '.';
await fs.mkdir(captureDirectory, { recursive: true });
const capture = async name => {
    // Wait for composited sticky/sidebar paint, not only the quote's DOM updates.
    await evaluate(`document.fonts.ready.then(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))))`);
    await pause(100);
    await fs.writeFile(captureDirectory + '/' + name + '.png', Buffer.from((await call('Page.captureScreenshot', { format: 'png' })).data, 'base64'));
};
const ready = `document.readyState === 'complete' && typeof openProductModal === 'function'`;
const navigate = async path => {
    await call('Page.navigate', { url: base + path });
    await wait(`location.href === ${JSON.stringify(base + path)} && ${ready}`);
};
const viewport = (width, height) => call('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
const close = async () => {
    await evaluate(`document.querySelector('.modal.show .akila-close').click()`);
    await wait(`!document.querySelector('.modal.show') && document.body.style.position !== 'fixed'`);
};
const open = async name => {
    await navigate('/menu?q=' + encodeURIComponent(name));
    await evaluate(`document.querySelector('[data-product-modal-id]').click()`);
    await wait(`!!document.querySelector('.modal.show [data-menu-form]')`);
    await wait(`document.activeElement === document.querySelector('.modal.show .akila-close')`);
};
const confirmed = () => wait(`document.querySelector('.modal.show [data-menu-add]')?.disabled === false`);
const fill = async second => {
    const blockIds = await evaluate(`[...document.querySelectorAll('.modal.show [data-menu-block]')].map(b=>b.dataset.menuBlock)`);
    for (const id of blockIds.length ? blockIds : [null]) {
        await evaluate(`(() => {
            const modal = document.querySelector('.modal.show');
            ${id ? `modal.querySelector('[data-go-block="${id}"]').click();` : ''}
            const scope = ${id ? `modal.querySelector('[data-menu-block="${id}"]')` : `modal.querySelector('[data-menu-form]')`};
            for (const group of scope.querySelectorAll('[data-menu-group]')) {
                const min = Number(group.dataset.min);
                if (group.querySelectorAll('input:checked').length >= min && !(Number(group.dataset.min) === 0 && ${Boolean(second)})) continue;
                if (!min && !${Boolean(second)}) continue;
                const optional = group.closest('.akila-optional-group');
                if (optional) optional.open = true;
                const pickedNames = [...scope.querySelectorAll('input:checked')].map(i=>i.closest('label')?.querySelector('span > span')?.firstChild?.textContent.trim());
                const input = [...group.querySelectorAll('input[data-menu-option]')].find(i=>!i.disabled && !i.checked && !pickedNames.includes(i.closest('label')?.querySelector('span > span')?.firstChild?.textContent.trim()));
                if (input) input.click();
            }
        })()`);
        await pause(250);
    }
    await confirmed();
};
const geometry = () => evaluate(`(() => {
    const m=document.querySelector('.modal.show'), b=m.querySelector('.modal-body'), h=m.querySelector('.modal-header').getBoundingClientRect(), f=m.querySelector('.modal-footer').getBoundingClientRect();
    return { overflow:document.documentElement.scrollWidth>innerWidth || b.scrollWidth>b.clientWidth, header:h.top, footer:f.bottom, height:innerHeight, body:b.clientHeight, scroll:b.scrollTop, open:m.querySelectorAll('[data-menu-block][open]').length, panelScroll:m.querySelector('.akila-config-panel').scrollTop, price:m.querySelector('[data-price-total]').textContent, blocks:m.querySelectorAll('[data-menu-block]').length };
})()`);
const context = await call('Target.createBrowserContext', {}, true);
const target = await call('Target.createTarget', { url: 'about:blank', browserContextId: context.browserContextId }, true);
session = (await call('Target.attachToTarget', { targetId: target.targetId, flatten: true }, true)).sessionId;
try {
    await call('Runtime.enable'); await call('Page.enable');
    await viewport(1440, 900);
    for (const [name, price, blocks, second] of [
        ['20 Piezas a Elección', '$10.000', 2], ['30 Piezas a Elección', '$14.900', 3],
        ['60 Piezas a Elección', '$25.990', 6], ['5 Hand Rolls', '$23.000', 5, true],
        ['Muchupleto', '$6.500', 0], ['AkilaBurger', '$7.500', 0],
        ['Noche de Sushi', '$17.990', 0], ['Arrollados', '$5.000', 0, true],
    ]) {
        await open(name); await fill(second);
        const state = await geometry();
        assert(state.price === price, name + ': wrong quote ' + state.price);
        assert(state.blocks === blocks, name + ': wrong blocks');
        assert(!blocks || state.open === 1, name + ': completed options disappeared');
        assert(!state.overflow && state.header >= 0 && state.footer <= state.height, name + ': layout');
        if (name === '30 Piezas a Elección' || name === 'Noche de Sushi') {
            await evaluate(`(() => {const m=document.querySelector('.modal.show');m.querySelector('.akila-unit-nav button')?.click();m.querySelector('.modal-body').scrollTop=0;})()`);
            await capture(name === 'Noche de Sushi' ? 'fixed-desktop' : 'modal-desktop');
            if (name === '30 Piezas a Elección') {
                await viewport(390,844);await capture('modal-mobile');await viewport(1440,900);
            }
        }
        if (name === '60 Piezas a Elección') {
            // A quote must not replace the scrolling node, close the block or steal focus.
            await evaluate(`window.uiBody=document.querySelector('.modal.show .modal-body');window.uiBlock=document.querySelector('.modal.show [data-menu-block][open]');window.uiForm=document.querySelector('.modal.show form');window.uiInputs=[...uiForm.querySelectorAll('input[data-menu-option]')];`);
            for (let i=0;i<12;i++) {
                const before = await evaluate(`(() => {const b=uiBlock;const group=b.querySelector('[data-menu-group]');const input=[...group.querySelectorAll('input')].find(i=>!i.checked&&!i.disabled);input.focus({preventScroll:true});input.click();return {scroll:uiBody.scrollTop,value:input.value};})()`);
                await confirmed();
                const after = await evaluate(`({same:uiBody===document.querySelector('.modal.show .modal-body')&&uiForm===document.querySelector('.modal.show form')&&uiInputs.every(i=>i.isConnected),scroll:uiBody.scrollTop,open:uiBlock.open,focus:document.activeElement.value,blocks:uiForm.querySelectorAll('[data-menu-block]').length})`);
                assert(after.same && after.open && after.blocks===6 && after.focus===before.value && Math.abs(after.scroll-before.scroll)<2, 'Stress quote lost state ' + JSON.stringify({before,after}));
            }
            await evaluate(`window.uiBlock.querySelector('[data-option-tab="vegetal"]').click();const input=[...uiBlock.querySelectorAll('[data-option-panel="vegetal"] input')].find(i=>!i.checked&&!i.disabled);input.focus({preventScroll:true});input.click();`);
            await confirmed();
            assert(await evaluate(`uiBlock.querySelector('[data-option-tab="vegetal"]').getAttribute('aria-selected')==='true' && uiBlock.querySelector('[data-option-panel="vegetal"] input:checked')!==null && uiBlock.querySelectorAll('[role="tabpanel"]:not([hidden])').length===1`),'Vegetal tab or selection lost after quote');
            await evaluate(`uiBlock.querySelector('[data-option-tab="vegetal"]').focus({preventScroll:true})`);
            await call('Input.dispatchKeyEvent',{type:'keyDown',key:'ArrowLeft',code:'ArrowLeft',windowsVirtualKeyCode:37});
            assert(await evaluate(`document.activeElement===uiBlock.querySelector('[data-option-tab="proteina"]') && uiBlock.querySelector('[data-option-tab="proteina"]').getAttribute('aria-selected')==='true' && uiBlock.querySelector('[data-option-panel="vegetal"] input:checked')!==null`),'Keyboard tab lost selection');
            for (const [width,height] of [[1920,1080],[1440,900],[1366,768],[1024,768],[768,1024],[430,932],[390,844],[360,800]]) {
                await viewport(width,height);
                const size = await geometry();
                assert(!size.overflow && size.header>=0 && size.footer<=height && size.body>150 && size.panelScroll===0, 'Responsive '+width+' '+JSON.stringify(size));
                await fs.writeFile((process.env.TEMP || '.') + '/akila-ui-'+width+'.png', Buffer.from((await call('Page.captureScreenshot',{format:'png'})).data,'base64'));
            }
            await viewport(1440,900);
        }
        if (name === 'Arrollados') {
            const flavors = await evaluate(`[...document.querySelectorAll('.modal.show input[data-menu-option]:checked')].map(i=>i.closest('label').innerText)`);
            // fill selected one flavor; selecting the second must keep the same total.
            await evaluate(`const i=document.querySelector('.modal.show input[data-menu-option]:not(:checked)');if(i)i.click();`);
            await confirmed();
            assert((await geometry()).price==='$5.000', 'Mixed surcharge');
            assert(flavors.length>0, 'No flavors');
            await evaluate(`document.querySelector('.modal.show input[data-menu-option]:checked').click()`);
            await confirmed();
            assert((await geometry()).price==='$5.000', 'Single flavor surcharge');
            await evaluate(`document.querySelector('.modal.show input[data-menu-option]:not(:checked)').click()`);
            await confirmed();
        }
        if (name === '5 Hand Rolls') {
            for (let i=0;i<4;i++) {
                await evaluate(`(() => {const b=document.querySelector('.modal.show [data-menu-block][open]');const g=[...b.querySelectorAll('[data-menu-group]')].find(g=>g.dataset.min==='0');window.secondInput=g.querySelector('input:checked');g.querySelector('[data-clear-group]').click();})()`);
                await confirmed();
                assert((await geometry()).price==='$22.000','Optional ingredient removal');
                await evaluate('secondInput.click()');await confirmed();
                const state=await geometry();
                assert(state.price==='$23.000' && state.blocks===5 && state.open===1,'Hand Roll stress lost configuration');
            }
        }
        await evaluate(`document.querySelector('.modal.show [data-menu-add]').click()`);
        await wait(`location.pathname === '/cart' && document.readyState==='complete'`);
        assert(await evaluate(`document.body.innerText.includes(${JSON.stringify(name)})`), name + ': cart lost selection');
        if (name === 'Arrollados') {
            await evaluate(`const item=[...document.querySelectorAll('.akila-cart-item')].find(a=>a.querySelector('h2').textContent==='Arrollados');item.querySelector('.akila-cart-update button[value="2"]').click()`);
            await wait(`([...document.querySelectorAll('.akila-cart-item')].find(a=>a.querySelector('h2').textContent==='Arrollados')?.querySelector('.akila-cart-update span')?.textContent)==='2'`);
            assert(await evaluate(`[...document.querySelectorAll('.akila-cart-item')].find(a=>a.querySelector('h2').textContent==='Arrollados').textContent.includes('$10.000')`),'Cart quantity did not use server total');
            await evaluate(`const item=[...document.querySelectorAll('.akila-cart-item')].find(a=>a.querySelector('h2').textContent==='Arrollados');item.querySelector('.akila-cart-update button[value="1"]').click()`);
            await wait(`([...document.querySelectorAll('.akila-cart-item')].find(a=>a.querySelector('h2').textContent==='Arrollados')?.querySelector('.akila-cart-update span')?.textContent)==='1'`);
            assert(await evaluate(`[...document.querySelectorAll('.akila-cart-item')].find(a=>a.querySelector('h2').textContent==='Arrollados').textContent.includes('Mixto')`),'Cart quantity lost mixed flavors');
            await capture('cart-desktop');
        }
        await viewport(390,844);
        assert(await evaluate('document.documentElement.scrollWidth===innerWidth'), name + ': mobile cart overflow');
        if (name === 'Arrollados') await capture('cart-mobile');
        await viewport(1440,900);
        results.push({ name, ...state, cart:true });
        console.log('PASS '+name);
    }
    await open('60 Piezas a Elección'); await fill();
    // Simulate a failed quote: leave the user's input and the last confirmed price intact.
    await evaluate(`window.realFetch=window.fetch;window.quoteCalls=0;window.failNext=true;window.fetch=(url,options)=>{if(String(url).includes('/cart/quote')){quoteCalls++;if(failNext){failNext=false;return Promise.resolve(new Response(JSON.stringify({message:'Fallo controlado'}),{status:500,headers:{'Content-Type':'application/json'}}));}}return realFetch(url,options);};document.querySelector('.modal.show form').dispatchEvent(new Event('change',{bubbles:true}));`);
    await wait(`!document.querySelector('.modal.show [data-menu-error]').hidden`);
    assert((await geometry()).price==='$25.990', 'Quote failure erased price');
    assert(await evaluate(`document.querySelectorAll('.modal.show input:checked[data-menu-option]').length===6`), 'Quote failure erased selections');
    await evaluate(`document.querySelector('.modal.show form').dispatchEvent(new Event('change',{bubbles:true}));`); await confirmed();
    // Deliberately ignore AbortSignal for an older request and delay its fake result.
    await evaluate(`window.delayNext=true;window.fetch=async(url,options)=>{if(!String(url).includes('/cart/quote'))return realFetch(url,options);quoteCalls++;if(delayNext){delayNext=false;const response=await realFetch(url,{...options,signal:undefined});const data=await response.json();data.total=123;await new Promise(r=>setTimeout(r,1500));return new Response(JSON.stringify(data),{headers:{'Content-Type':'application/json'}});}return realFetch(url,options);};document.querySelector('.modal.show form').dispatchEvent(new Event('change',{bubbles:true}));`);
    await pause(350);
    await evaluate(`document.querySelector('.modal.show form').dispatchEvent(new Event('change',{bubbles:true}));`);await confirmed();await pause(1800);
    assert((await geometry()).price==='$25.990','Stale quote overwrote latest state');
    await close();
    const start = await evaluate('quoteCalls');
    for(let i=0;i<20;i++) {
        await evaluate(`window.scrollTo({top:250,behavior:'instant'});window.savedPageY=scrollY;document.querySelector('[data-product-modal-id]').click()`);
        await confirmed();
        await call('Input.dispatchKeyEvent',{type:'keyDown',key:'Tab',code:'Tab',windowsVirtualKeyCode:9,modifiers:1});
        assert(await evaluate(`document.querySelector('.modal.show').contains(document.activeElement)`),'Tab escaped');
        await call('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});
        await wait(`!document.querySelector('.modal.show') && document.body.style.position !== 'fixed'`);
        assert(await evaluate(`scrollY===savedPageY && document.activeElement.hasAttribute('data-product-modal-id')`),'Scroll/focus not restored');
    }
    assert(await evaluate(`quoteCalls-${start}===20`),'Duplicate listeners / requests');
    await navigate('/menu');
    for (const [width,height] of [[1920,1080],[1440,900],[1366,768],[1024,768],[768,1024],[430,932],[390,844],[360,800]]) {
        await viewport(width,height);
        const state=await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth,columns:getComputedStyle(document.querySelector('.akila-product-grid')).gridTemplateColumns.split(' ').length,empty:[...document.querySelectorAll('.akila-categories a')].some(a=>a.textContent.includes('Gohan'))})`);
        assert(!state.overflow&&!state.empty&&state.columns===(width>=1200?4:width>=992?3:width>=576?2:1),'Menu responsive '+width+' '+JSON.stringify(state));
    }
    await viewport(390,844);
    await capture('menu-mobile');
    await fs.writeFile((process.env.TEMP || '.')+'/akila-ui-menu-mobile.png',Buffer.from((await call('Page.captureScreenshot',{format:'png'})).data,'base64'));
    await viewport(1920,1080);await fs.writeFile((process.env.TEMP || '.')+'/akila-ui-menu-desktop.png',Buffer.from((await call('Page.captureScreenshot',{format:'png'})).data,'base64'));
    await capture('menu-desktop');
    await viewport(1024,768);await capture('menu-tablet');
    assert(!exceptions.length,'JS exceptions '+JSON.stringify(exceptions));
    console.log(JSON.stringify({products:results,stressQuotes:21,openClose:20,responsive:8,staleResponse:true,failedQuote:true,keyboardTabs:true,cartQuantity:true,exceptions},null,2));
} finally {
    await call('Target.disposeBrowserContext',{browserContextId:context.browserContextId},true);
    socket.close();
}
