#!/usr/bin/env node
'use strict';
// Actual WordPress / Rank Math output, known synthetic Divi sources and labelled
// test photographs. Does not claim licensed Divi or production-image validation.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {execFileSync} = require('node:child_process');
const {chromium} = require('playwright');

(async () => {
 const base = process.argv[2], cli = process.argv[3];
 assert(/^http:\/\/127\.0\.0\.1:\d+$/.test(base) && /^yoona-seo-test-gallery-.*-cli$/.test(cli), 'A disposable loopback gallery test site is required.');
 const root = path.join(__dirname, '..');
 const guides = JSON.parse(fs.readFileSync(path.join(root, 'plugin/yoona-seo/content/service-guides.json')));
 const definitions = JSON.parse(fs.readFileSync(path.join(root, 'plugin/yoona-seo/assets/design-data.json'))).offer_heroes;
 const existing = Object.keys(definitions), owned = Object.keys(guides).filter(route => !existing.includes(route));
 assert(existing.length === 41 && owned.length === 6, 'The full 41 + 6 public service-copy inventory is required.');
 const wp = (...args) => execFileSync('docker', ['exec', '-u', '0', cli, 'wp', '--allow-root', '--path=/var/www/html', ...args], {encoding:'utf8'});
 assert(wp('option', 'get', 'yoona_compact_fixture_ready').trim() === 'public-synthetic-41-v1', 'Run compact-service.php prepare first.');
 const sourceCss = fs.readFileSync(path.join(root, 'plugin/yoona-seo/assets/design.css'));
 const baselineRef = process.env.YOONA_COMPACT_BASELINE_REF || 'f9955dd6c711c422d9e01fff17c07b8ac0bb4e78';
 const baselineCss = execFileSync('git', ['show', baselineRef + ':plugin/yoona-seo/assets/design.css'], {cwd:root});
 const sha = body => crypto.createHash('sha256').update(body).digest('hex');
 assert(sha(sourceCss) !== sha(baselineCss), 'Baseline must be the previous 3.4.1 stylesheet, not the compact source.');
 const output = process.env.YOONA_TEST_SCREENSHOTS || '/tmp/yoona-compact-service-screenshots';
 fs.mkdirSync(output, {recursive:true});
 let assertions = 0;
 const check = (condition, label) => {assert(condition, label); ++assertions;};
 const errors = [], geometry = {baselineRef, baselineCssSha256:sha(baselineCss), currentCssSha256:sha(sourceCss), baseline:{}, current:[]};
 const fixture = '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1100"><defs><linearGradient id="g"><stop stop-color="#61745f"/><stop offset="1" stop-color="#b6b0a3"/></linearGradient></defs><rect width="1600" height="1100" fill="url(#g)"/><path d="M0 650L1600 400V1100H0Z" fill="#24211e"/><text x="60" y="750" fill="#ff7000" font-family="sans-serif" font-size="54">LOCAL STRUCTURE PROBE — NOT A PRODUCTION PHOTO</text></svg>';
 const whiteFixture = '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1100"><rect width="1600" height="1100" fill="#fff"/><text x="40" y="1050" fill="#888" font-size="36">WHITE IMAGE CONTRAST PROBE</text></svg>';
 const browser = await chromium.launch({executablePath:process.env.YOONA_TEST_CHROMIUM || '/usr/bin/chromium', headless:true, args:['--no-sandbox']});
 const makeContext = async (width, baseline = false, white = false) => {
  const context = await browser.newContext({viewport:{width,height:950}, deviceScaleFactor:1});
  context.on('page', page => page.on('pageerror', error => errors.push(error.message)));
  await context.route('**/*', async route => {
   const request = route.request();
   if (baseline && new URL(request.url()).pathname.endsWith('/yoona-seo/assets/design.css')) await route.fulfill({status:200, contentType:'text/css', body:baselineCss});
   else if (request.resourceType() === 'image') await route.fulfill({status:200, contentType:'image/svg+xml', body:white ? whiteFixture : fixture});
   else await route.continue();
  });
  return context;
 };
 const visit = async (page, route) => {
  const response = await page.goto(base + route, {waitUntil:'load',timeout:45000});
  check(response.status() === 200, 'HTTP 200 ' + route);
  await page.evaluate(() => document.fonts.ready);
 };
 const measure = page => page.evaluate(() => {
  const rect = element => element.getBoundingClientRect().toJSON();
  const title = document.querySelector('.yn-offer-title');
  const hero = document.querySelector('.yn-offer-hero');
  const image = document.querySelector('.yn-offer-visual');
  const panels = [...document.querySelectorAll('.yn-guide-panel')];
  const grid = document.querySelector('.yn-guide-grid');
  const fits = [...document.querySelectorAll('.yn-offer-title,.yn-offer-lead,.yn-offer-actions a,.yn-guide h2,.yn-guide li')].flatMap(element => {
   const box = rect(element); if (!box.width || !box.height) return [];
   const range = document.createRange(); range.selectNodeContents(element);
   const clipped = [...range.getClientRects()].some(part => part.width > 0 && (part.left < box.left - 2 || part.right > box.right + 2));
   return clipped || element.scrollWidth > element.clientWidth + 2 ? [element.textContent.trim()] : [];
  });
  return {
   overflow:document.documentElement.scrollWidth - document.documentElement.clientWidth,
   clipped:fits, hero:hero && rect(hero), image:image && rect(image), imagePosition:image && getComputedStyle(image).position,
   copy:rect(document.querySelector('.yn-offer-copy')), title:rect(title), titleTag:title.tagName,
   titleText:title.textContent.trim(), grid:rect(grid), panels:panels.map(rect),
   headings:panels.map(panel => {const h=panel.querySelector('h2'), s=getComputedStyle(h); return {font:s.fontFamily,weight:s.fontWeight,size:parseFloat(s.fontSize),box:rect(h)};}),
   actions:[...document.querySelectorAll('.yn-offer-actions a')].map(rect),
   bodyFontLoaded:[...document.fonts].some(font => font.family.replace(/["']/g,'') === 'YN Manrope' && font.status === 'loaded'),
   guideAction:(() => {const s=getComputedStyle(document.querySelector('.yn-guide .yn-button')); return {color:s.color,decoration:s.textDecorationLine};})(),
   overlay:getComputedStyle(image,'::after').backgroundImage,
  };
 });
 const preserved = async (page, route, width) => {
  if (owned.includes(route)) {
   check(await page.locator('.yn-service-original,.repair-gallery,.repair-bottom-section,.yn-growth-page-content').count() === 0, width + ' owned source is rendered once ' + route);
   return;
  }
  check(await page.locator('.yn-service-original .yn-guide').count() === 1, width + ' curated panel font is tested inside the real !important content scope ' + route);
  check(await page.locator('.repair-gallery-section>.repair-gallery-row>.repair-gallery-column>.repair-gallery').count() === 1, width + ' gallery section / row / column ancestry ' + route);
  check(await page.locator('.repair-gallery .et_pb_gallery_item a>img').count() === 3, width + ' all three linked gallery photographs retained once ' + route);
  check(await page.locator('.repair-bottom-section>.repair-bottom-row>.repair-bottom-left>.repair-cta').count() === 1 && await page.locator('.repair-bottom-left>.repair-toggle').count() === 1 && await page.locator('.repair-bottom-right>.repair-social').count() === 1 && await page.locator('.repair-bottom-right>.repair-form').count() === 1, width + ' complete lower modules and their original columns ' + route);
  check(await page.locator('.repair-retired-contact,.compact-old-hero,.yn-guide-media-grid .repair-gallery').count() === 0, width + ' retired contact, replaced hero and detached gallery grid remain absent ' + route);
  const galleryColumns = await page.locator('.repair-gallery .et_pb_gallery_items').evaluate(element => getComputedStyle(element).gridTemplateColumns.split(' ').length);
  check(galleryColumns === (width > 600 ? 3 : 2), width + ' wrapper-dependent gallery columns remain unchanged ' + route);
  const callbacks = JSON.parse(await page.locator('#repair-callback-counts').textContent());
  check(callbacks.et_pb_section === 5 && callbacks.et_pb_gallery === 1 && callbacks.et_pb_cta === 1 && callbacks.et_pb_toggle === 1 && callbacks.et_pb_social_media_follow === 1 && callbacks.et_pb_contact_form === 1, width + ' every original shortcode executes once including hidden sections ' + route);
  check(await page.locator('.compact-old-intro').count() === (['/flagi/','/totemy/'].includes(route) ? 1 : 0), width + ' only the two media-bearing append introductions survive ' + route);
 };
 try {
  // Existing gallery-repair.php owned explicitly creates the six pages before
  // this runner. First views may populate legitimate Rank Math OG metadata.
  for (const route of Object.keys(guides)) {
   const response = await fetch(base + route); check(response.status === 200, 'Warmup ' + route); await response.arrayBuffer();
  }
  console.log(wp('eval-file','/var/www/html/yoona-tests/compact-service.php','snapshot','--skip-themes').trim());
  const baselineContext = await makeContext(1440, true), baselinePage = await baselineContext.newPage();
  for (const route of ['/banery/','/frezowanie-cnc/','/flagi/']) {
   await visit(baselinePage, route);
   const value = await measure(baselinePage);
   check(value.headings.every(item => item.font.includes('YN Archivo')), 'Previous stylesheet reproduces large inherited Archivo panel labels ' + route);
   geometry.baseline[route] = value;
  }
  await baselinePage.goto(base + '/banery/', {waitUntil:'load'}); await baselinePage.evaluate(() => document.fonts.ready);
  await baselinePage.locator('.yn-guide').screenshot({path:path.join(output,'1440-before-guide.png')});
  await baselineContext.close();

  for (const width of [1440,760,390,320]) {
   const context = await makeContext(width), page = await context.newPage();
   const routes = width === 1440 ? ['/banery/','/frezowanie-cnc/','/oklejanie-przyczep-gastronomicznych/','/flagi/','/totemy/', ...owned] : Object.keys(guides);
   let verifiedCss = false;
   for (const route of routes) {
    await visit(page, route);
    check(await page.locator('main.yn-service-guide,.yn-offer-hero,.yn-guide').count() === 3 && await page.locator('.yn-guide-panel').count() === 5, width + ' one real service template / header / five panels ' + route);
    if (!verifiedCss) {
     const cssUrl = await page.locator('link[href*="/assets/design.css"]').first().getAttribute('href');
     const response = await page.request.get(cssUrl);
     check(response.status() === 200 && sha(await response.body()) === sha(sourceCss), width + ' actual served CSS bytes equal current source'); verifiedCss = true;
    }
    const value = await measure(page);
    check(value.overflow <= 2 && value.clipped.length === 0, width + ' no page or text overflow ' + route + ' ' + JSON.stringify(value.clipped));
    check(value.bodyFontLoaded && value.headings.every(item => item.font.includes('YN Manrope') && item.weight === '800' && item.size >= 12 && item.size <= 14), width + ' compact Manrope 800 labels override content !important ' + route + ' ' + JSON.stringify(value.headings));
    check(value.guideAction.color === 'rgb(17, 17, 17)' && value.guideAction.decoration === 'none', width + ' quote button retains dark legible text instead of inheriting the content link color ' + route);
    check(value.actions.length === 2 && value.actions.every(box => box.height >= 44 && box.width >= 44 && box.x >= value.hero.x - 1 && box.right <= value.hero.right + 1 && box.bottom <= value.hero.bottom + 1), width + ' both full-size calls to action remain inside the hero ' + route);
    check(await page.locator('main h1').count() === 1 && value.titleTag === (route === '/flagi/' ? 'P' : 'H1'), width + ' correct p / h1 hero semantics with exactly one source H1 ' + route);
    check(await page.locator('.yn-offer-visual img').evaluate(image => image.complete && image.naturalWidth > 0 && image.loading === 'eager' && image.fetchPriority === 'high' && image.alt.length > 5), width + ' accessible single hero image is present and prioritized ' + route);
    const p = value.panels;
    if (width === 1440) {
     check(Math.abs(p[0].y-p[1].y) <= 1 && Math.abs(p[1].y-p[2].y) <= 1 && p[3].y > p[0].y && Math.abs(p[3].y-p[4].y) <= 1 && Math.abs(p[3].width-p[4].width) <= 1 && p[3].width > p[0].width && Math.abs(p[3].x-value.grid.x) <= 1 && Math.abs(p[4].right-value.grid.right) <= 1, 'Desktop is a compact three + two grid without vacant columns ' + route);
     if (geometry.baseline[route]) check(value.grid.height <= geometry.baseline[route].grid.height * 0.7, 'Desktop panels are at least 30% shorter than the actual 3.4.1 stylesheet ' + route + ' (' + value.grid.height + '/' + geometry.baseline[route].grid.height + ')');
    } else if (width === 760) {
     check(Math.abs(p[0].y-p[1].y) <= 1 && Math.abs(p[2].y-p[3].y) <= 1 && p[2].y > p[0].y && p[4].y > p[2].y && Math.abs(p[4].width-value.grid.width) <= 1, '760px retains two columns and a full final row ' + route);
    } else {
     check(p.every((box,index) => Math.abs(box.width-value.grid.width) <= 1 && (index === 0 || box.y >= p[index-1].bottom)), width + ' panels stack naturally into one readable column ' + route);
    }
    if (width <= 760) {
     check(value.imagePosition === 'absolute' && Math.abs(value.image.x-value.hero.x) <= 2 && Math.abs(value.image.y-value.hero.y) <= 2 && Math.abs(value.image.width-value.hero.width) <= 2 && Math.abs(value.image.height-value.hero.height) <= 2 && value.copy.bottom <= value.hero.bottom + 1, width + ' one photographic hero replaces the separate tall mobile image ' + route);
    }
    await preserved(page, route, width);
    check(await page.locator('meta[name="description"]').count() === 1 && (owned.includes(route) || (await page.title()).startsWith('REPAIR NATIVE ')), width + ' native Rank Math metadata preserved ' + route);
    if (['/banery/','/frezowanie-cnc/','/flagi/','/totemy/'].includes(route)) {
     geometry.current.push({width,route,...value});
     await page.locator('.yn-offer-hero').screenshot({path:path.join(output,width+'-'+route.replaceAll('/','')+'-hero.png')});
     if (route === '/banery/') {
      await page.locator('.yn-guide').screenshot({path:path.join(output,width+'-after-guide.png')});
      await page.screenshot({path:path.join(output,width+'-complete-offer.png'),fullPage:true});
     }
    }
    console.log('PASS Compact ' + width + ' ' + route);
   }
   await context.close();
  }
  // A deliberately pure-white photograph makes a missing/diluted mobile overlay
  // visible in screenshots. A dark pseudo-element and white text are asserted;
  // visual approval of the resulting PNG remains part of the review.
  for (const width of [390,320]) {
   const context = await makeContext(width,false,true), page = await context.newPage();
   await visit(page,'/banery/');
   const contrast = await page.locator('.yn-offer-hero').evaluate(hero => {
    const visual=hero.querySelector('.yn-offer-visual'), title=hero.querySelector('.yn-offer-title'), lead=hero.querySelector('.yn-offer-lead');
    const after=getComputedStyle(visual,'::after');
    return {overlay:after.backgroundImage,opacity:after.opacity,title:getComputedStyle(title).color,lead:getComputedStyle(lead).color};
   });
   check(contrast.overlay !== 'none' && Number(contrast.opacity) > 0 && contrast.title === 'rgb(255, 255, 255)', width + ' deliberate white-image probe keeps a visible overlay and white heading');
   await page.locator('.yn-offer-hero').screenshot({path:path.join(output,width+'-white-image-contrast.png')});
   await context.close();
  }
  check(errors.length === 0,'No uncaught browser JavaScript errors: ' + JSON.stringify(errors));
  console.log(wp('eval-file','/var/www/html/yoona-tests/compact-service.php','verify','--skip-themes').trim());
  fs.writeFileSync(path.join(output,'geometry.json'),JSON.stringify(geometry,null,2)+'\n');
  console.log('Current CSS SHA-256: ' + sha(sourceCss));
  console.log('Baseline CSS SHA-256 (' + baselineRef + '): ' + sha(baselineCss));
  console.log('Success: ' + assertions + ' compact-offer / mobile-hero / preserved-gallery assertions passed.');
  console.log('Screenshots: ' + output + ' (synthetic photos and known synthetic Divi callbacks, not licensed Divi or production-media validation).');
 } finally {await browser.close();}
})().catch(error => {console.error(error.stack || error.message);process.exitCode=1;});
