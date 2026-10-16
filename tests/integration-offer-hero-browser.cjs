#!/usr/bin/env node
'use strict';
// Actual shipped PHP/CSS in Chromium. Photos are explicitly labelled SVG probes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const { execFileSync } = require('node:child_process');
const { chromium } = require('playwright');
(async () => {
  const base = process.argv[2];
  const cli = process.argv[3];
  if (!base || !cli) throw new Error('Usage: integration-offer-hero-browser.cjs http://127.0.0.1:PORT CLI_CONTAINER');
  const output = process.env.YOONA_TEST_SCREENSHOTS || '/tmp/yoona-offer-hero-screenshots';
  fs.mkdirSync(output, { recursive: true });
  let assertions = 0;
  const check = (condition, label) => { assert(condition, label); ++assertions; console.log('PASS Offer browser ' + label); };
  const wp = (...args) => execFileSync('docker', ['exec', '-u', '0', cli, 'wp', '--allow-root', '--path=/var/www/html', ...args], { encoding: 'utf8' });
  const expectedCss = crypto.createHash('sha256').update(fs.readFileSync(path.join(__dirname, '../plugin/yoona-seo/assets/design.css'))).digest('hex');
  const definitions = JSON.parse(fs.readFileSync(path.join(__dirname, '../plugin/yoona-seo/assets/design-data.json'))).offer_heroes;
  const titles = process.env.YOONA_TEST_EXPORT_JSON ? JSON.parse(fs.readFileSync(process.env.YOONA_TEST_EXPORT_JSON)).pages.filter(page => definitions[page.path]).map(page => page.post_title) : [];
  const failures = [];
  const browser = await chromium.launch({ executablePath: process.env.YOONA_TEST_CHROMIUM || '/usr/bin/chromium', headless: true, args: ['--no-sandbox'] });
  try {
    for (const [name, viewport] of [
      ['desktop', { width: 1440, height: 1000 }], ['desktop-wide', { width: 1920, height: 1080 }],
      ['mobile', { width: 390, height: 844 }], ['mobile-small', { width: 320, height: 780 }],
    ]) {
      const context = await browser.newContext({ viewport, deviceScaleFactor: 1 });
      const page = await context.newPage();
      page.on('pageerror', error => failures.push(error.message));
      await page.route('**/*', async route => {
        const request = route.request();
        if (request.resourceType() === 'image' && (!request.url().startsWith(base) || new URL(request.url()).pathname.includes('/wp-content/uploads/'))) {
          await route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1100"><defs><linearGradient id="g" x2="1" y2="1"><stop stop-color="#62755a"/><stop offset="1" stop-color="#252e22"/></linearGradient></defs><rect width="1600" height="1100" fill="url(#g)"/><path d="M0 700L1600 550V1100H0Z" fill="#b6ac8b"/><rect x="210" y="310" width="950" height="400" fill="#171b16" stroke="#d0b170" stroke-width="15"/><text x="290" y="510" fill="#f5eee3" font-family="sans-serif" font-size="92" font-weight="bold">YOONA</text><text x="290" y="590" fill="#ff863c" font-family="sans-serif" font-size="34">LOCAL PHOTO FIXTURE</text></svg>' });
        } else await route.continue();
      });
      for (const [slug, expectsBodyH1] of [
        ['plotki-ogrodki-piwne', true], ['oklejanie-przyczep-gastronomicznych', false], ['tabliczki-informacyjne', false],
      ]) {
        const label = name + '/' + slug;
        const response = await page.goto(base + '/' + slug + '/', { waitUntil: 'networkidle', timeout: 45000 });
        await page.evaluate(() => document.fonts.ready);
        check(response.status() === 200 && await page.locator('.yn-offer-hero').count() === 1, label + ': exactly one new offer header loads.');
        check(await page.locator('.yn-page-heading').count() === 0 && await page.locator('[data-module="et_pb_section"][data-counter="0"]').count() === 0, label + ': both previous duplicate banners are absent.');
        check(await page.locator('#offer-preserved-body').isVisible() && await page.locator('[data-module="et_pb_section"][data-counter="1"]').count() === 1, label + ': preserved following body keeps the original section index.');
        check(await page.locator('h1').count() === 1 && await page.locator((expectsBodyH1 ? 'p' : 'h1') + '#yn-offer-title').count() === 1, label + ': the title preserves exactly one H1 with the existing body semantics.');
        check(await page.locator('.yn-offer-lead').isVisible() && (await page.locator('.yn-offer-lead').innerText()).length > 50, label + ': a service-specific readable introduction is visible.');
        check(await page.locator('.yn-offer-visual img').evaluate(image => image.complete && image.naturalWidth > 0 && image.alt.length > 5 && image.loading === 'eager' && image.fetchPriority === 'high'), label + ': the accessible hero photograph is present and prioritized.');
        check(await page.locator('.yn-offer-actions a').first().getAttribute('href') === base + '/kontakt/' && await page.locator('.yn-offer-more').getAttribute('href') === '#yn-offer-content', label + ': both calls to action use working contact/content targets.');
        const geometry = await page.evaluate(() => {
          const hero = document.querySelector('.yn-offer-hero').getBoundingClientRect();
          const copy = document.querySelector('.yn-offer-copy').getBoundingClientRect();
          const image = document.querySelector('.yn-offer-visual').getBoundingClientRect();
          const title = document.querySelector('.yn-offer-title');
          const lead = document.querySelector('.yn-offer-lead');
          const selectors = ['.yn-offer-title', '.yn-offer-lead', '.yn-offer-actions a', '.yn-offer-breadcrumbs', '.yn-offer-signature'];
          const boxes = selectors.flatMap(selector => Array.from(document.querySelectorAll(selector))).map(element => {
            const box = element.getBoundingClientRect();
            const range = document.createRange();
            range.selectNodeContents(element);
            return { label: element.className, box: box.toJSON(), scroll: element.scrollWidth, client: element.clientWidth, ranges: Array.from(range.getClientRects()).map(rect => rect.toJSON()) };
          });
          return { hero: hero.toJSON(), copy: copy.toJSON(), image: image.toJSON(), boxes, overflow: document.documentElement.scrollWidth - innerWidth,
            titleFont: getComputedStyle(title).fontFamily, titleTransform: getComputedStyle(title).textTransform,
            leadFont: getComputedStyle(lead).fontFamily, leadSize: parseFloat(getComputedStyle(lead).fontSize),
            displayLoaded: Array.from(document.fonts).some(font => font.family.replace(/["']/g, '') === 'YN Archivo' && font.status === 'loaded'),
            bodyLoaded: Array.from(document.fonts).some(font => font.family.replace(/["']/g, '') === 'YN Manrope' && font.status === 'loaded') };
        });
        const clipped = geometry.boxes.filter(item => item.box.width < 1 || item.scroll > item.client + 2 || item.ranges.some(rect => rect.width > 0 && (rect.left < item.box.left - 2 || rect.right > item.box.right + 2)));
        check(geometry.overflow <= 2 && clipped.length === 0, label + ': actual text ranges, actions and full page fit without clipping (' + JSON.stringify(clipped.map(item => item.label)) + ').');
        check(geometry.displayLoaded && geometry.bodyLoaded && geometry.titleFont.includes('YN Archivo') && geometry.titleTransform === 'uppercase' && geometry.leadFont.includes('YN Manrope') && geometry.leadSize >= 16, label + ': actual local fonts, uppercase title and readable body sizing are applied.');
        if (viewport.width > 760) check(geometry.image.x > geometry.copy.x && geometry.image.y === geometry.hero.y && Math.abs(geometry.image.height - geometry.hero.height) <= 2, label + ': desktop photo shares a single split header with the copy.');
        else check(geometry.image.y >= geometry.copy.bottom - 1 && geometry.image.width >= viewport.width - 2 && geometry.image.height >= 230, label + ': mobile photo follows the text at a useful full width.');
        if (slug === 'plotki-ogrodki-piwne') {
          const cssUrl = await page.locator('link[href*="/assets/design.css"]').first().getAttribute('href');
          const css = await page.request.get(cssUrl);
          check(css.status() === 200 && crypto.createHash('sha256').update(await css.body()).digest('hex') === expectedCss, name + ': served CSS exactly matches the current source.');
        }
        await page.screenshot({ path: path.join(output, name + '-' + slug + '.png'), fullPage: false });
        if (slug === 'plotki-ogrodki-piwne' && viewport.width < 760) await page.locator('.yn-offer-hero').screenshot({ path: path.join(output, name + '-whole-offer.png') });
      }
      if (titles.length) {
        const titleFit = await page.evaluate(titles => {
          const element = document.querySelector('.yn-offer-title');
          const results = [];
          const originalText = element.textContent;
          const originalClass = element.className;
          const canvas = document.createElement('canvas');
          const ctx = canvas.getContext('2d');
          for (const title of titles) {
            element.textContent = title;
            element.classList.toggle('yn-offer-title-long', title.length > 28);
            element.classList.toggle('yn-offer-title-wide', title.split(/[\s,–—-]+/).some(word => word.length >= 14));
            const style = getComputedStyle(element);
            const box = element.getBoundingClientRect();
            ctx.font = [style.fontStyle, style.fontWeight, style.fontSize, style.fontFamily].join(' ');
            const maxWord = Math.max(...title.toUpperCase().split(/[\s,–—-]+/).filter(Boolean).map(word => ctx.measureText(word).width + (word.length - 1) * parseFloat(style.letterSpacing || 0)));
            const range = document.createRange(); range.selectNodeContents(element);
            const rects = Array.from(range.getClientRects());
            results.push({ title, maxWord, width: box.width, scroll: element.scrollWidth, client: element.clientWidth, clipped: rects.some(rect => rect.left < box.left - 2 || rect.right > box.right + 2) });
          }
          element.textContent = originalText; element.className = originalClass;
          return results;
        }, titles);
        const bad = titleFit.filter(item => item.clipped || item.scroll > item.client + 2 || item.maxWord > item.width + 1);
        check(titles.length === 41 && bad.length === 0, name + ': all 41 original service titles fit, including their longest unbroken words (' + JSON.stringify(bad) + ').');
      }
      const fallback = await page.goto(base + '/plotki-ogrodki-piwne/?yoona_offer_mutate=1', { waitUntil: 'networkidle' });
      check(fallback.status() === 200 && await page.locator('.yn-offer-hero').count() === 0 && await page.locator('.yn-page-heading').count() === 1 && await page.locator('[data-module="et_pb_section"][data-counter="0"]').count() === 1, name + ': a changed rendered header falls back without removing any original banner.');
      for (const route of ['/o-nas/', '/design-post/', '/offer-post/', '/offer-protected/', '/plotki-ogrodki-piwne/?et_fb=1', '/plotki-ogrodki-piwne/?et_pb_preview=1', '/plotki-ogrodki-piwne/?preview=true']) {
        await page.goto(base + route, { waitUntil: 'networkidle' });
        check(await page.locator('.yn-offer-hero').count() === 0, name + ': nonservice/protected/builder/preview route remains excluded: ' + route);
      }
      await context.close();
    }
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    try {
      wp('option', 'update', 'yoona_seo_design_enabled', '0');
      await page.goto(base + '/plotki-ogrodki-piwne/', { waitUntil: 'networkidle' });
      check(await page.locator('.yn-offer-hero').count() === 0 && await page.locator('body.yn-design').count() === 0, 'The visual off switch restores the theme and removes the premium offer template.');
    } finally { wp('option', 'delete', 'yoona_seo_design_enabled'); }
    await context.close();
    check(failures.length === 0, 'There are no uncaught frontend JavaScript errors.');
    console.log('Tested design.css SHA-256: ' + expectedCss);
    console.log('Success: ' + assertions + ' focused real Chromium offer assertions passed.');
    console.log('Screenshots: ' + output + ' (labelled synthetic photos, not production image review).');
  } finally { await browser.close(); }
})().catch(error => { console.error(error.stack || error.message); process.exitCode = 1; });
