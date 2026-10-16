#!/usr/bin/env node
'use strict';

// Run the shipped frontend in Chromium. Only remote photos receive local SVG
// fixtures because the test container cannot retrieve yoona.pl media.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const { execFileSync } = require('node:child_process');
const { chromium } = require('playwright');

(async () => {
  const base = process.argv[2];
  if (!base) throw new Error('Usage: integration-design-browser.cjs http://127.0.0.1:PORT');
  const output = process.env.YOONA_TEST_SCREENSHOTS || '/tmp/yoona-design-screenshots';
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch({
    executablePath: process.env.YOONA_TEST_CHROMIUM || '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox'],
  });
  const failures = [];
  const expectedCss = fs.readFileSync(path.join(__dirname, '../plugin/yoona-seo/assets/design.css'));
  const expectedCssHash = crypto.createHash('sha256').update(expectedCss).digest('hex');
  console.log(`Tested design.css SHA-256: ${expectedCssHash}`);
  let assertions = 0;
  const check = (condition, message) => {
    assert(condition, message);
    assertions += 1;
    console.log(`PASS Design browser ${message}`);
  };
  try {
    for (const [name, viewport] of [
      ['desktop', { width: 1440, height: 1000 }],
      ['desktop-wide', { width: 1920, height: 1080 }],
      ['mobile', { width: 390, height: 844 }],
      ['mobile-small', { width: 320, height: 780 }],
    ]) {
      const context = await browser.newContext({ viewport, deviceScaleFactor: 1 });
      const page = await context.newPage();
      page.on('pageerror', error => failures.push(`${name}: ${error.message}`));
      await page.route('**/*', async route => {
        const request = route.request();
        if (request.resourceType() === 'image' && (!request.url().startsWith(base) || new URL(request.url()).pathname.includes('/wp-content/uploads/'))) {
          const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800"><defs><linearGradient id="g" x2="1" y2="1"><stop stop-color="#453427"/><stop offset="1" stop-color="#141414"/></linearGradient></defs><rect width="1200" height="800" fill="url(#g)"/><rect x="190" y="140" width="800" height="490" fill="#242424" stroke="#ff7000" stroke-width="9"/><text x="250" y="390" fill="#eee" font-family="sans-serif" font-size="120" font-weight="bold">YOONA</text><text x="255" y="475" fill="#ff7000" font-family="sans-serif" font-size="33">LOCAL PHOTO FIXTURE</text></svg>';
          await route.fulfill({ status: 200, contentType: 'image/svg+xml', body: svg });
        } else {
          await route.continue();
        }
      });
      const response = await page.goto(base + '/', { waitUntil: 'networkidle', timeout: 45000 });
      check(response.status() === 200, `${name}: the real homepage loads successfully.`);
      const stylesheetUrl = await page.locator('link[href*="/assets/design.css"]').first().getAttribute('href');
      const stylesheet = await page.request.get(stylesheetUrl);
      const actualCssHash = crypto.createHash('sha256').update(await stylesheet.body()).digest('hex');
      check(stylesheet.status() === 200 && actualCssHash === expectedCssHash,
        `${name}: the frontend serves the exact current source stylesheet.`);
      check(await page.locator('body.yn-design').count() === 1, `${name}: the visual template is active.`);
      check(await page.locator('main#main-content').count() === 1, `${name}: one accessible main region is present.`);
      check(await page.locator('h1').count() === 1, `${name}: the homepage has exactly one H1.`);
      check(await page.locator('#yn-hero-title').isVisible(), `${name}: the new hero title is visible.`);
      const geometry = await page.evaluate(() => ({
        scroll: document.documentElement.scrollWidth,
        client: document.documentElement.clientWidth,
        hero: document.querySelector('#yn-hero-title').getBoundingClientRect().toJSON(),
      }));
      check(geometry.scroll <= geometry.client + 2, `${name}: the page has no horizontal overflow.`);
      check(geometry.hero.x >= -1 && geometry.hero.right <= viewport.width + 1,
        `${name}: the hero text fits inside the viewport.`);
      const textFit = await page.locator('#yn-hero-title, main h2, .yn-contact h2, .yn-service-card h3, .yn-capability-grid strong, .yn-footer-services a, .yn-hero-caption').evaluateAll(elements => elements.map(element => {
        const box = element.getBoundingClientRect();
        const text = [];
        const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
        while (walker.nextNode()) {
          if (!walker.currentNode.textContent.trim()) continue;
          const range = document.createRange();
          range.selectNodeContents(walker.currentNode);
          text.push(...Array.from(range.getClientRects()).filter(rect => rect.width > 0).map(rect => ({ left: rect.left, right: rect.right })));
        }
        return { label: element.textContent.trim(), scroll: element.scrollWidth, client: element.clientWidth,
          left: box.left, right: box.right, width: box.width, text };
      }));
      const clipped = textFit.filter(element => element.width <= 0 || (element.client > 0 && element.scroll > element.client + 2)
        || element.text.some(rect => rect.left < element.left - 2 || rect.right > element.right + 2));
      check(textFit.length >= 25 && clipped.length === 0,
        `${name}: headings, service labels and captions fit their boxes and actual text ranges (${JSON.stringify(clipped)}).`);
      const uppercase = await page.locator('.yn-service-card h3, .yn-capability-grid strong, .yn-footer-services a').evaluateAll(elements => elements.map(element => ({
        text: element.textContent.trim(), transform: getComputedStyle(element).textTransform,
      })));
      check(uppercase.length === 20 && uppercase.every(element => element.transform === 'uppercase'),
        `${name}: all eight offer cards, four capabilities and eight footer offer labels display uppercase.`);
      check(await page.locator('.yn-hero-description').evaluate((element, minimum) => parseFloat(getComputedStyle(element).fontSize) >= minimum,
        name.startsWith('mobile') ? 15 : 16), `${name}: the hero description retains a readable minimum font size.`);
      const links = await page.locator('.yn-desktop-nav a').evaluateAll(elements => elements.map(element => {
        const url = new URL(element.href);
        return { valid: url.origin === location.origin && Boolean(url.pathname) && (!url.hash || Boolean(document.getElementById(decodeURIComponent(url.hash.slice(1))))),
          text: element.textContent.trim() };
      }));
      check(links.length >= 5 && links.every(link => link.valid && link.text),
        `${name}: the header navigation keeps valid internal URLs and existing anchor targets.`);
      if (name.startsWith('mobile')) {
        const headings = await page.locator('main h2, .yn-contact h2').evaluateAll(elements => elements.map(element => ({
          scroll: element.scrollWidth,
          client: element.clientWidth,
          text: element.textContent.trim(),
        })));
        check(headings.length >= 4 && headings.every(heading => heading.client > 0 && heading.scroll <= heading.client + 1),
          `${name}: all section headings fit their own content box without clipped text.`);
        const gallery = await page.locator('.yn-project-grid').evaluate(element => ({
          width: element.getBoundingClientRect().width,
          gap: parseFloat(getComputedStyle(element).columnGap),
          cards: Array.from(element.querySelectorAll('.yn-project-card')).map(card => card.getBoundingClientRect().toJSON()),
        }));
        check(gallery.cards.length === 8, `${name}: the gallery has all eight project cards.`);
        const half = (gallery.width - gallery.gap) / 2;
        for (const index of [0, 1, 3, 4, 6, 7]) {
          const card = gallery.cards[index];
          check(card.width >= (viewport.width === 390 ? 130 : 100)
            && Math.abs(card.width - half) <= 2
            && card.left >= -1 && card.right <= viewport.width + 1,
          `${name}: gallery card ${index + 1} keeps half width inside the viewport.`);
        }
        for (const index of [2, 5]) {
          const card = gallery.cards[index];
          check(Math.abs(card.width - gallery.width) <= 2
            && card.left >= -1 && card.right <= viewport.width + 1,
          `${name}: gallery card ${index + 1} keeps full width inside the viewport.`);
        }
        const menu = page.locator('.yn-mobile-menu');
        const summary = menu.locator('summary');
        check(await summary.isVisible(), 'mobile: the native keyboard-accessible menu control is visible.');
        check(Boolean(await summary.getAttribute('aria-controls')), 'mobile: the menu control identifies its navigation target.');
        await summary.focus();
        await summary.press('Enter');
        check(await menu.evaluate(element => element.open), 'mobile: Enter opens the native menu.');
        check(await menu.locator('nav a').first().isVisible(), 'mobile: opening the menu reveals navigation links.');
        await summary.press('Enter');
        check(!(await menu.evaluate(element => element.open)), 'mobile: Enter closes the menu again.');
        await summary.evaluate(element => element.blur());
      }
      // Trigger every lazy image through an actual bounded viewport scroll.
      const height = await page.evaluate(() => Math.min(document.documentElement.scrollHeight, 60000));
      for (let offset = 0; offset < height; offset += viewport.height) {
        await page.evaluate(y => window.scrollTo(0, y), offset);
        await page.waitForTimeout(60);
      }
      await page.evaluate(() => window.scrollTo(0, 0));
      await page.waitForLoadState('networkidle');
      await page.evaluate(() => document.fonts.ready);
      check(await page.evaluate(() => {
        const face = Array.from(document.fonts).find(font => font.family.replace(/["']/g, '') === 'YN Archivo' && font.status === 'loaded');
        return Boolean(face) && getComputedStyle(document.querySelector('#yn-hero-title')).fontFamily.includes('YN Archivo');
      }), `${name}: the shipped local display font loads and styles the hero.`);
      check(await page.evaluate(() => {
        const faces = Array.from(document.fonts).filter(font => font.family.replace(/["']/g, '') === 'YN Manrope' && font.status === 'loaded');
        return faces.length > 0 && ['body', '.yn-hero-description', '.yn-service-card h3', '.yn-desktop-nav a'].every(selector => getComputedStyle(document.querySelector(selector)).fontFamily.includes('YN Manrope'));
      }), `${name}: the shipped local Manrope font loads for body, description, navigation and offer labels.`);
      await page.screenshot({ path: path.join(output, `${name}-home.png`), fullPage: true });
      await page.screenshot({ path: path.join(output, `${name}-hero.png`), fullPage: false });
      if (name.startsWith('mobile')) {
        await page.locator('.yn-about').screenshot({ path: path.join(output, `${name}-about.png`) });
      }
      await page.evaluate(() => window.scrollTo(0, 0));
      // A solid white image is the worst bright photo: a misplaced overlay can
      // otherwise pass unnoticed when all test photographs are already dark.
      await page.locator('.yn-hero-media img').evaluate(async image => {
        image.removeAttribute('srcset');
        image.src = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800"><rect width="1200" height="800" fill="white"/></svg>');
        await image.decode();
      });
      const contrastProbe = await page.evaluate(() => {
        const hero = document.querySelector('.yn-hero').getBoundingClientRect();
        const points = [];
        for (const selector of ['#yn-hero-title', '#yn-hero-title span', '.yn-hero-description']) {
          const element = document.querySelector(selector);
          const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
          while (walker.nextNode()) {
            // The H1's orange child is measured separately with its own color.
            if (walker.currentNode.parentElement !== element || !walker.currentNode.textContent.trim()) continue;
            const range = document.createRange();
            range.selectNodeContents(walker.currentNode);
            for (const rect of range.getClientRects()) {
              if (rect.width < 1) continue;
              points.push({ x: Math.min(innerWidth - 1, Math.round(rect.right + 3)), y: Math.round(rect.top + rect.height / 2),
                color: getComputedStyle(element).color, minimum: selector === '.yn-hero-description' ? 4.5 : 3, selector });
            }
          }
        }
        return { points, photo: { x: innerWidth - 5, y: Math.round(hero.top + hero.height / 2) }, hero: hero.toJSON() };
      });
      const whitePath = path.join(output, `${name}-hero-white-photo.png`);
      await page.screenshot({ path: whitePath, fullPage: false });
      const pixels = JSON.parse(execFileSync('python3', ['-c',
        'import json,sys; from PIL import Image; im=Image.open(sys.argv[1]).convert("RGB"); pts=json.loads(sys.argv[2]); print(json.dumps([im.getpixel((p["x"],p["y"])) for p in pts]))',
        whitePath, JSON.stringify([...contrastProbe.points, contrastProbe.photo])], { encoding: 'utf8' }));
      const luminance = color => color.map(channel => channel / 255).map(channel => channel <= .04045 ? channel / 12.92 : ((channel + .055) / 1.055) ** 2.4)
        .reduce((sum, channel, index) => sum + channel * [.2126, .7152, .0722][index], 0);
      const contrast = (a, b) => (Math.max(luminance(a), luminance(b)) + .05) / (Math.min(luminance(a), luminance(b)) + .05);
      const measured = contrastProbe.points.map((point, index) => ({
        selector: point.selector, ratio: contrast(point.color.match(/[\d.]+/g).slice(0, 3).map(Number), pixels[index]), minimum: point.minimum,
      }));
      check(measured.length >= 5 && measured.every(point => point.ratio >= point.minimum),
        `${name}: hero text stays readable over a white photo (${measured.map(point => `${point.selector} ${point.ratio.toFixed(2)}:1`).join(', ')}).`);
      // Inspect the actual rendered pixels, not merely the intended z-index.
      const photoPixel = pixels[pixels.length - 1];
      const textBackground = pixels[0];
      check(photoPixel[0] > 35 && photoPixel[0] > textBackground[0] + 15,
        `${name}: the photo remains visible while the text area receives a stronger overlay.`);
      const lightHighlights = await page.locator('.yn-services h2 span, .yn-projects h2 span, .yn-about h2 span').evaluateAll(elements => elements.map(element => ({
        color: getComputedStyle(element).color,
        background: getComputedStyle(document.querySelector('main.yn-main')).backgroundColor,
      })));
      check(lightHighlights.length === 3 && lightHighlights.every(element => contrast(
        element.color.match(/[\d.]+/g).slice(0, 3).map(Number), element.background.match(/[\d.]+/g).slice(0, 3).map(Number)) >= 4.5),
        `${name}: all orange heading highlights on the light paper background retain at least 4.5:1 contrast.`);
      const offerResponse = await page.goto(base + '/pelna-oferta/', { waitUntil: 'networkidle', timeout: 45000 });
      check(offerResponse.status() === 200 && await page.locator('body.yn-offer-page').count() === 1,
        `${name}: the actual full-offer route receives its scoped offer styling.`);
      const offerHeadings = await page.locator('.design-offer-sample :is(h3,h4,h5,h6), .design-offer-sample :is(h3,h4,h5,h6) :is(span,a)').evaluateAll(elements => elements.map(element => ({
        font: getComputedStyle(element).fontFamily, transform: getComputedStyle(element).textTransform,
        scroll: element.scrollWidth, client: element.clientWidth, display: getComputedStyle(element).display,
      })));
      check(offerHeadings.length === 6 && offerHeadings.every(element => element.font.includes('YN Manrope') && element.transform === 'uppercase'),
        `${name}: full-offer headings and nested labels override conflicting serif fonts and display uppercase.`);
      check(offerHeadings.filter(element => element.display !== 'inline').every(element => element.scroll <= element.client + 2),
        `${name}: long full-offer labels fit their available width.`);
      if (name === 'mobile') {
        const secondary = await page.goto(base + '/design-fixture/', { waitUntil: 'networkidle', timeout: 45000 });
        check(secondary.status() === 200 && await page.locator('#yoona-preserved-content').isVisible(),
          'mobile: existing secondary content remains visible in the branded layout.');
        check(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 2),
          'mobile: the secondary content has no horizontal overflow.');
        await page.screenshot({ path: path.join(output, 'mobile-secondary.png'), fullPage: true });
      }
      await context.close();
    }
    check(failures.length === 0, 'The actual frontend raises no uncaught JavaScript errors.');
    console.log(`Success: ${assertions} real Chromium visual design assertions passed.`);
    console.log(`Screenshots: ${output} (remote media replaced with labelled local test images).`);
  } finally {
    await browser.close();
  }
})().catch(error => {
  console.error(error.stack || error.message);
  process.exitCode = 1;
});
