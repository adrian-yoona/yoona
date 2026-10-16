#!/usr/bin/env node
// Execute the shipped batching controller against a minimal DOM/fetch contract.
// HTTP integration separately checks the real WordPress endpoints and nonces.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const admin = fs.readFileSync(path.join(__dirname, '../plugin/yoona-seo/includes/class-yoona-seo-admin.php'), 'utf8');
const script = admin.match(/<script>\s*([\s\S]*?)\s*<\/script>/);
assert(script, 'The shipped admin batching controller is present.');

async function scenario(failAt) {
  const paths = Array.from({length: 200}, (_, i) => ({value: '/fixture-' + i + '/', checked: false, disabled: false}));
  const disabled = [{value: '/blocked/', checked: false, disabled: true}];
  const controls = Array.from({length: 3}, () => ({disabled: false}));
  const calls = [];
  const progress = {max: 0, value: 0};
  const status = {textContent: ''};
  const results = {children: [], appendChild(item) {this.children.push(item);}, set textContent(value) {this.children = [];}};
  const element = () => ({hidden: true, listeners: {}, addEventListener(name, listener) {this.listeners[name] = listener;}});
  const refresh = element();
  const selectAll = element();
  const selectNone = element();
  const form = Object.assign(element(), {
    action: 'http://127.0.0.1/wp-admin/admin-post.php',
    querySelectorAll(selector) {return selector.startsWith('input[name=') ? paths.filter(input => !input.disabled) : controls;},
  });
  const box = Object.assign(element(), {querySelector(selector) {return selector === 'progress' ? progress : selector === 'ul' ? results : status;}});
  class FormData {
    constructor(source) {
      this.fields = [];
      if (source) {
        this.append('action', 'yoona_seo_apply');
        this.append('_wpnonce', 'test-nonce');
        this.append('bundle_hash', 'test-bundle-hash');
        paths.filter(input => input.checked && !input.disabled).forEach(input => this.append('selected_paths[]', input.value));
      }
    }
    append(key, value) {this.fields.push([key, value]);}
    delete(key) {this.fields = this.fields.filter(field => field[0] !== key);}
    set(key, value) {this.delete(key); this.append(key, value);}
    forEach(callback) {this.fields.forEach(([key, value]) => callback(value, key));}
    getAll(key) {return this.fields.filter(field => field[0] === key).map(field => field[1]);}
    get(key) {return this.getAll(key)[0];}
  }
  const events = {};
  let concurrent = 0;
  let peak = 0;
  const fetch = async (url, options) => {
    ++concurrent;
    peak = Math.max(peak, concurrent);
    assert.equal(url, form.action);
    assert.equal(options.method, 'POST');
    assert.equal(options.credentials, 'same-origin');
    assert.equal(options.body.get('_wpnonce'), 'test-nonce');
    assert.equal(options.body.get('bundle_hash'), 'test-bundle-hash');
    assert.equal(options.body.get('yoona_json'), '1');
    const selected = options.body.getAll('selected_paths[]');
    assert.equal(selected.length, 10);
    calls.push(selected);
    // Repeated submission and select-none clicks during a request cannot create
    // duplicate jobs or change the already captured selection.
    if (calls.length === 1) {
      await form.listeners.submit({preventDefault() {}});
      selectNone.listeners.click();
      assert(paths.every(input => input.checked));
      let guarded = false;
      events.beforeunload({preventDefault() {guarded = true;}});
      assert(guarded);
    }
    await Promise.resolve();
    --concurrent;
    const failed = failAt && calls.length === failAt;
    return {
      ok: !failed,
      async json() {
        return failed ? {success: false, data: {message: 'Deliberate request failure'}} : {
          success: true,
          data: {status: 'success', pages: selected.map(selectedPath => ({path: selectedPath, status: 'applied', message: 'Saved'}))},
        };
      },
    };
  };
  const document = {
    getElementById(id) {return {'yoona-seo-apply-form': form, 'yoona-progress': box, 'yoona-refresh': refresh, 'yoona-select-all': selectAll, 'yoona-select-none': selectNone}[id];},
    createElement() {return {textContent: ''};},
  };
  const window = {fetch, FormData, location: {reload() {}}, addEventListener(name, callback) {events[name] = callback;}};
  vm.runInNewContext(script[1], {document, window, FormData, fetch, Array, Error});
  selectAll.listeners.click();
  assert(paths.every(input => input.checked));
  assert(disabled.every(input => !input.checked));
  selectNone.listeners.click();
  assert(paths.every(input => !input.checked));
  await form.listeners.submit({preventDefault() {}});
  assert.equal(calls.length, 0, 'An empty selection does not send a request.');
  selectAll.listeners.click();
  await form.listeners.submit({preventDefault() {}});
  assert.equal(peak, 1, 'Requests run sequentially.');
  assert(controls.every(control => !control.disabled), 'Controls are re-enabled.');
  assert.equal(refresh.hidden, false);
  if (failAt) {
    assert.equal(calls.length, failAt, 'The controller stops after the failed request.');
    assert.equal(progress.value, (failAt - 1) * 10);
    assert(status.textContent.includes('Deliberate request failure'));
  } else {
    assert.equal(calls.length, 20, 'All 200 selected pages are sent in 20 batches.');
    assert.deepEqual(calls.flat(), paths.map(input => input.value), 'Every selected page is sent exactly once, in order.');
    assert.equal(progress.value, 200);
    assert.equal(results.children.length, 200);
  }
}

(async () => {
  await scenario(0);
  await scenario(3);
  console.log('Success: Shipped batching JavaScript sends 200 pages in 20 sequential requests, prevents duplicate submits, and stops safely on a failed batch.');
})().catch(error => {console.error(error); process.exitCode = 1;});
