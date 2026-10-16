#!/usr/bin/env node
'use strict';

// Run the shipped controller with a tiny DOM adapter and native Node FormData.
// WordPress endpoint, permissions and nonce checks have separate HTTP tests.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const sourcePath = path.join(__dirname, '../plugin/yoona-seo/includes/class-yoona-seo-admin.php');
const source = fs.readFileSync(sourcePath, 'utf8');
const scripts = [...source.matchAll(/<script>\s*([\s\S]*?)\s*<\/script>/g)];
assert.equal(scripts.length, 1, 'Exactly one shipped admin controller is tested.');
const NativeFormData = globalThis.FormData;
assert.equal(typeof NativeFormData, 'function', 'Use a Node version with native FormData.');

class Element {
  constructor(properties = {}) {
    this.listeners = new Map();
    this.children = [];
    this.hidden = false;
    this.disabled = false;
    this._text = '';
    Object.assign(this, properties);
  }
  addEventListener(type, callback) {
    if (!this.listeners.has(type)) this.listeners.set(type, []);
    this.listeners.get(type).push(callback);
  }
  async emit(type, event = {}) {
    for (const callback of this.listeners.get(type) || []) await callback(event);
  }
  appendChild(child) { this.children.push(child); }
  set textContent(value) { this._text = String(value); this.children = []; }
  get textContent() { return this._text; }
  set innerHTML(value) { throw new Error('Dynamic messages must not be interpreted as HTML.'); }
}

async function scenario({ failAt = 0, unchangedAt = 0, nonJsonStatus = 0 } = {}) {
  const selected = Array.from({ length: 25 }, (_, index) => new Element({
    type: 'checkbox', name: 'selected_paths[]', value: '/page-' + index + '/', checked: false,
  }));
  const blockedChecked = new Element({ type: 'checkbox', name: 'selected_paths[]', value: '/blocked-checked/', disabled: true, checked: true });
  const blockedUnchecked = new Element({ type: 'checkbox', name: 'selected_paths[]', value: '/blocked-unchecked/', disabled: true, checked: false });
  const hidden = [
    ['action', 'yoona_seo_apply'], ['_wpnonce', 'fixture-nonce'],
    ['bundle_hash', 'fixture-sha256'], ['_wp_http_referer', '/wp-admin/tools.php?page=yoona-seo'],
  ].map(([name, value]) => new Element({ type: 'hidden', name, value }));
  const selectAll = new Element();
  const selectNone = new Element();
  const refresh = new Element({ hidden: true });
  const submit = new Element({ type: 'submit' });
  const buttons = [selectAll, selectNone, refresh, submit];
  const inputs = [...hidden, ...selected, blockedChecked, blockedUnchecked];
  const endpoint = 'https://example.invalid/wp-admin/admin-post.php';
  const form = new Element({
    // Native forms expose the hidden <input name="action"> as form.action.
    action: new Element({ name: 'action', value: 'yoona_seo_apply' }), inputs,
    getAttribute(name) { assert.equal(name, 'action'); return endpoint; },
  });
  form.querySelectorAll = selector => {
    if (selector === 'input[name="selected_paths[]"]:not(:disabled)') return inputs.filter(input => input.name === 'selected_paths[]' && !input.disabled);
    if (selector === 'button,input[type="submit"]') return buttons;
    throw new Error('Unsupported DOM query: ' + selector);
  };
  const progress = new Element({ value: 0, max: 1 });
  const status = new Element();
  const results = new Element();
  const box = new Element({ hidden: true });
  box.querySelector = selector => ({ progress, '[role="status"]': status, ul: results })[selector];
  const elements = {
    'yoona-seo-apply-form': form, 'yoona-progress': box, 'yoona-refresh': refresh,
    'yoona-select-all': selectAll, 'yoona-select-none': selectNone,
  };
  const document = {
    getElementById: id => elements[id],
    createElement(tag) { assert.equal(tag, 'li'); return new Element(); },
  };

  // Native Node FormData has no HTMLFormElement overload. Only that browser
  // constructor overload is adapted; every returned object is real FormData.
  const FormData = new Proxy(NativeFormData, {
    construct(target, argumentsList) {
      const data = new target();
      if (argumentsList.length) {
        assert.equal(argumentsList[0], form);
        for (const input of form.inputs) {
          if (input.disabled || !input.name || (input.type === 'checkbox' && !input.checked)) continue;
          data.append(input.name, input.value);
        }
      }
      return data;
    },
  });

  const calls = [];
  const hostileMessage = '<img src=x onerror="alert(1)"> & <script>bad()</script>';
  let active = 0;
  let peak = 0;
  let responseRead = 0;
  const fetch = async (url, options) => {
    ++active;
    peak = Math.max(peak, active);
    assert.equal(calls.length, responseRead, 'Read the previous response before sending another batch.');
    assert.equal(url, endpoint, 'Read the real form attribute even when an input shadows form.action.');
    assert.equal(options.method, 'POST');
    assert.equal(options.credentials, 'same-origin');
    assert(options.body instanceof NativeFormData, 'Payload uses native FormData.');
    for (const [name, value] of [['action', 'yoona_seo_apply'], ['_wpnonce', 'fixture-nonce'], ['bundle_hash', 'fixture-sha256'], ['yoona_json', '1']]) {
      assert.deepEqual(options.body.getAll(name), [value], 'Preserve one value of ' + name + '.');
    }
    const batch = options.body.getAll('selected_paths[]');
    calls.push(batch);
    const number = calls.length;
    const nonJsonFailure = nonJsonStatus && number === 2;
    await new Promise(resolve => setImmediate(resolve));
    --active;
    return {
      ok: number !== failAt && !nonJsonFailure,
      status: nonJsonFailure ? nonJsonStatus : number === failAt ? 409 : 200,
      async json() {
        await new Promise(resolve => setImmediate(resolve));
        ++responseRead;
        if (nonJsonFailure) throw new SyntaxError('Unexpected token < in HTML response');
        if (number === failAt) return { success: false, data: { message: hostileMessage } };
        return {
          success: true,
          data: {
            status: number === unchangedAt ? 'failed' : 'success',
            pages: batch.map(pagePath => ({ path: pagePath, status: number === unchangedAt ? 'unchanged' : 'applied', message: hostileMessage })),
          },
        };
      },
    };
  };
  const window = new Element({ fetch, FormData, location: { reload() {} } });
  vm.runInNewContext(scripts[0][1], { document, window, FormData, fetch }, { filename: sourcePath + ':batch_script' });

  await selectAll.emit('click');
  assert(selected.every(input => input.checked), 'Select-all includes ready pages.');
  assert.equal(blockedUnchecked.checked, false, 'Select-all does not check disabled pages.');
  assert.equal(blockedChecked.checked, true, 'Select-all leaves disabled inputs unchanged.');
  await selectNone.emit('click');
  assert(selected.every(input => !input.checked), 'Select-none clears ready pages.');
  assert.equal(blockedChecked.checked, true, 'Select-none leaves disabled inputs unchanged.');
  await form.emit('submit', { preventDefault() {} });
  assert.equal(calls.length, 0, 'An empty ready-page selection never starts an import.');
  await selectAll.emit('click');
  await form.emit('submit', { preventDefault() {} });

  assert.equal(peak, 1, 'Requests execute sequentially.');
  const failed = Boolean(failAt || nonJsonStatus);
  assert.deepEqual(calls.map(batch => batch.length), failed ? [10, 10] : [10, 10, 5]);
  assert.deepEqual(calls.flat(), selected.slice(0, failed ? 20 : 25).map(input => input.value), 'Each ready page is sent once in selection order; disabled pages are excluded.');
  assert.equal(progress.max, 25);
  assert.equal(progress.value, failed ? 10 : 25, 'Completed progress survives a later request failure.');
  assert.equal(results.children.length, failed ? 10 : 25);
  assert(results.children.every(item => item.textContent.endsWith(hostileMessage)), 'Server messages remain literal text.');
  assert(results.children.every(item => item.children.length === 0), 'Server markup creates no child elements.');
  assert.equal(refresh.hidden, false, 'History refresh becomes available after completion or failure.');
  assert(buttons.every(button => !button.disabled), 'Controls become available again.');
  if (failed) {
    assert.equal(calls.length, 2, 'A failed second batch prevents the third request.');
    assert(status.textContent.startsWith('Zatrzymano po 10 z 25 stron.'));
    if (nonJsonStatus) {
      assert(status.textContent.includes('HTTP ' + nonJsonStatus), 'Non-JSON errors identify the actual HTTP status.');
      assert(!status.textContent.includes('Unexpected token'), 'A raw JSON parser error is not shown as the diagnosis.');
    } else {
      assert(status.textContent.endsWith(hostileMessage), 'Failure messages are also literal text.');
    }
  } else {
    assert(status.textContent.startsWith('Zakończono. Zapisano 25 z 25 stron.'));
  }
}

(async () => {
  await scenario();
  await scenario({ failAt: 2 });
  await scenario({ unchangedAt: 2 });
  for (const nonJsonStatus of [403, 404, 502]) await scenario({ nonJsonStatus });
  console.log('Success: actual admin script reads the real endpoint despite a named action input, sends native FormData in 10/10/5 batches, and safely stops on JSON or non-JSON HTTP 403/404/502 failures.');
})().catch(error => { console.error(error); process.exitCode = 1; });
