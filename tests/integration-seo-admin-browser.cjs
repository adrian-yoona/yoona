#!/usr/bin/env node
'use strict';

// Exercise the actual WordPress form and the shipped controller in Chromium.
// Native HTMLFormElement named properties cannot be reproduced by a plain DOM
// object: <input name="action"> shadows form.action in every supported browser.
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { chromium } = require('playwright');

(async () => {
  const base = process.argv[2];
  const cli = process.argv[3];
  if (!base || !cli) throw new Error('Usage: integration-seo-admin-browser.cjs http://127.0.0.1:PORT CLI_CONTAINER');
  const wp = (...args) => execFileSync('docker', ['exec', '-u', '0', cli, 'wp', '--allow-root', '--path=/var/www/html', ...args], {encoding: 'utf8', maxBuffer: 10 * 1024 * 1024});
  const fixture = (...args) => wp('eval-file', '/var/www/html/yoona-tests/prepare-seo-transport.php', ...args, '--skip-themes');
  const state = () => JSON.parse(fixture('state'));
  let checks = 0;
  const check = (condition, message) => {
    assert(condition, message);
    ++checks;
    console.log('PASS SEO admin browser ' + message);
  };
  console.log(fixture('prepare').trim());
  const browser = await chromium.launch({
    executablePath: process.env.YOONA_TEST_CHROMIUM || '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox'],
  });
  try {
    const page = await browser.newPage();
    page.setDefaultTimeout(45000);
    const failures = [];
    page.on('pageerror', error => failures.push(error.message));
    await page.goto(base + '/wp-login.php');
    await page.locator('#user_login').fill('yoona_test_admin');
    await page.locator('#user_pass').fill('local-admin-test-only');
    await Promise.all([page.waitForURL('**/wp-admin/**'), page.locator('#wp-submit').click()]);
    const open = async () => page.goto(base + '/wp-admin/tools.php?page=yoona-seo');
    const submit = async () => {
      await page.locator('#yoona-seo-apply-form input[type="submit"]').click();
      await page.waitForFunction(() => !document.querySelector('#yoona-progress').hidden && !document.querySelector('#yoona-refresh').hidden, null, {timeout: 120000});
      return page.locator('#yoona-progress [role="status"]').textContent();
    };
    await open();
    const nativeForm = await page.locator('#yoona-seo-apply-form').evaluate(form => ({
      clobbered: form.action instanceof HTMLInputElement,
      hiddenValue: form.action.value,
      attribute: form.getAttribute('action'),
    }));
    check(nativeForm.clobbered && nativeForm.hiddenValue === 'yoona_seo_apply', 'The real hidden action input shadows the native form.action property.');
    check(nativeForm.attribute === base + '/wp-admin/admin-post.php', 'The real action attribute points to the authenticated WordPress endpoint.');
    check(await page.locator('#yoona-seo-apply-form input[name="selected_paths[]"]:not(:disabled)').count() === 200, 'All 200 synthetic public pages are ready, including three large Divi pages.');

    for (const status of [403, 404, 502]) {
      let attempts = 0;
      const intercept = async route => {
        ++attempts;
        await route.fulfill({status, contentType: 'text/html', body: '<html><h1>Blocked upstream</h1><script>window.untrustedResponseExecuted=true</script></html>'});
      };
      await page.route('**/wp-admin/admin-post.php', intercept);
      await page.locator('#yoona-seo-apply-form input[name="selected_paths[]"]').first().check();
      const message = await submit();
      check(message.includes('HTTP ' + status) && message.startsWith('Zatrzymano po 0 z 1 stron.'), 'Non-JSON HTTP ' + status + ' is explained with the real status and no claimed completed write.');
      check(attempts === 1 && !await page.evaluate(() => Boolean(window.untrustedResponseExecuted)), 'HTTP ' + status + ' does not execute response HTML or automatically retry a write.');
      const untouched = state();
      check(untouched.batches.length === 0 && Object.values(untouched.pages).every(item => item.exact_before), 'HTTP ' + status + ' before the server leaves every fixture and the backup history unchanged.');
      await page.unroute('**/wp-admin/admin-post.php', intercept);
      await open();
    }

    // Let the backend save the first ten pages, but simulate a gateway replacing
    // its acknowledgement. The browser must stop and never replay the batch.
    let ambiguousAttempts = 0;
    const ambiguous = async route => {
      ++ambiguousAttempts;
      const response = await route.fetch();
      const payload = await response.json();
      assert.equal(response.status(), 200);
      assert.equal(payload.success, true);
      await route.fulfill({status: 502, contentType: 'text/html', body: '<h1>Gateway lost acknowledgement after backend save</h1>'});
    };
    await page.route('**/wp-admin/admin-post.php', ambiguous);
    await page.locator('#yoona-select-all').click();
    const ambiguousMessage = await submit();
    check(ambiguousAttempts === 1 && ambiguousMessage.includes('HTTP 502') && ambiguousMessage.includes('Stan ostatniej partii nie jest potwierdzony'), 'A lost acknowledgement stops once and tells the administrator that the last batch is unconfirmed.');
    const ambiguousState = state();
    check(ambiguousState.batches.length === 1 && Object.values(ambiguousState.pages).filter(item => item.exact_after).length === 10, 'The ten writes made before the lost acknowledgement remain in one durable backup without duplicate saves.');
    await page.unroute('**/wp-admin/admin-post.php', ambiguous);
    check(fixture('rollback').includes('Success:'), 'The durable backup restores the batch whose acknowledgement was lost.');
    check(Object.values(state().pages).every(item => item.exact_before), 'Recovery restores every field, native Rank Math value and original Divi byte exactly.');

    console.log(fixture('prepare').trim());
    await open();
    const responses = [];
    const requests = [];
    let pending = 0;
    let peak = 0;
    page.on('request', request => {
      if (request.method() !== 'POST' || !request.postData()?.includes('yoona_seo_apply')) return;
      requests.push(request);
      ++pending;
      peak = Math.max(peak, pending);
    });
    page.on('response', response => {
      const request = response.request();
      if (requests.includes(request)) {
        --pending;
        responses.push(response);
      }
    });
    await page.locator('#yoona-select-all').click();
    const message = await submit();
    check(message.startsWith('Zakończono. Zapisano 200 z 200 stron.'), 'Clicking the actual admin submit control completes all 200 pages.');
    check(requests.length === 20 && responses.length === 20 && peak === 1, 'The native browser sends twenty sequential ten-page requests.');
    check(requests.every(request => request.url() === base + '/wp-admin/admin-post.php'), 'Every request uses admin-post.php rather than [object HTMLInputElement].');
    const allPages = [];
    for (const response of responses) {
      const payload = await response.json();
      check(response.status() === 200 && payload.success && payload.data.status === 'success' && payload.data.pages.length === 10, 'A real ten-page backend request returns a successful JSON acknowledgement.');
      allPages.push(...payload.data.pages);
    }
    check(allPages.length === 200 && new Set(allPages.map(item => item.path)).size === 200 && allPages.every(item => item.status === 'applied'), 'Every selected path is written exactly once.');
    const applied = state();
    check(Object.values(applied.pages).length === 200 && Object.values(applied.pages).every(item => item.exact_after), 'All 200 Rank Math titles/descriptions and 51 Divi module replacements match their exact expected database state.');
    check(applied.batches.length === 20 && applied.batches.every(row => row.count === 10 && row.status === 'success' && row.can_rollback), 'All twenty batches have durable, usable backups.');
    check(await page.locator('#yoona-progress li').count() === 200, 'The real progress list acknowledges all 200 pages.');
    await open();
    check(await page.locator('#yoona-seo-apply-form input[name="selected_paths[]"]:not(:disabled)').count() === 0, 'Reloaded preview recognises every page as already applied.');
    const rollbackForms = [];
    for (const batch of applied.batches) {
      const form = page.locator('form').filter({has: page.locator('input[name="batch_id"][value="' + batch.batch_id + '"]')});
      check(await form.count() === 1, 'The backup for a real browser batch is visible in the admin history.');
      rollbackForms.push(await form.evaluate(element => ({url: element.getAttribute('action'), data: Object.fromEntries(new FormData(element))})));
    }
    for (const form of rollbackForms) {
      const response = await page.request.post(form.url, {form: form.data});
      assert.equal(response.status(), 200);
    }
    const restored = state();
    check(Object.values(restored.pages).every(item => item.exact_before), 'All twenty admin rollback forms restore the exact original metadata and Divi content.');
    check(restored.batches.length === 20 && restored.batches.every(row => row.status === 'rolled_back'), 'History confirms all twenty batches were rolled back.');
    check(failures.length === 0, 'The actual admin page has no uncaught JavaScript errors.');
    console.log('Success: ' + checks + ' real Chromium SEO admin assertions passed.');
  } finally {
    await browser.close();
    fixture('cleanup');
  }
})().catch(error => {console.error(error); process.exitCode = 1;});
