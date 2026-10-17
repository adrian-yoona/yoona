#!/usr/bin/env node
'use strict';
// Real shipped templates/CSS/fonts. Only unavailable source photos use labelled probes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const { execFileSync } = require('node:child_process');
const { chromium } = require('playwright');
(async () => {
  const [base, cli] = process.argv.slice(2);
  if (!/^http:\/\/127\.0\.0\.1:\d+$/.test(base || '') || !/^yoona-seo-test-.*-cli$/.test(cli || '')) throw new Error('Disposable local WordPress URL and CLI container required.');
  const output = process.env.YOONA_TEST_SCREENSHOTS || '/tmp/yoona-collections-screenshots';
  fs.mkdirSync(output, { recursive: true });
  const wp = (...args) => execFileSync('docker', ['exec', '-u', '0', cli, 'wp', '--allow-root', '--path=/var/www/html', ...args], { encoding: 'utf8' });
  let assertions = 0;
  const check = (condition, label) => { assert(condition, label); ++assertions; console.log('PASS Collections browser ' + label); };
  const expectedCss = crypto.createHash('sha256').update(fs.readFileSync(path.join(__dirname, '../plugin/yoona-seo/assets/design.css'))).digest('hex');
  const data = JSON.parse(fs.readFileSync(path.join(__dirname, '../plugin/yoona-seo/assets/design-data.json')));
  const servicePaths = Object.keys(data.offer_heroes).map(item => base + item).sort();
  const browser = await chromium.launch({ executablePath: process.env.YOONA_TEST_CHROMIUM || '/usr/bin/chromium', headless: true, args: ['--no-sandbox'] });
  const errors = [];
  try {
    const viewports = process.env.YOONA_TEST_VISIBILITY_ONLY === '1' ? [] : [['desktop', { width:1440, height:1000 }], ['desktop-wide', { width:1920, height:1080 }], ['desktop-ultrawide', { width:2560, height:1200 }], ['tablet', { width:900, height:1000 }], ['mobile', { width:390, height:844 }], ['mobile-small', { width:320, height:780 }]];
    for (const [name, viewport] of viewports) {
      const context = await browser.newContext({ viewport });
      const page = await context.newPage();
      page.on('pageerror', error => errors.push(error.message));
      await page.route('**/*', async route => {
        const request = route.request();
        if (request.resourceType() === 'image' && !request.url().includes('/collection-photo-')) await route.fulfill({ status:200, contentType:'image/svg+xml', body:'<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="900"><rect width="1200" height="900" fill="#263537"/><path d="M0 600L1200 350V900H0Z" fill="#c9b99b"/><rect x="160" y="220" width="850" height="370" fill="#151e1d"/><text x="220" y="390" fill="#ff7728" font-family="sans-serif" font-size="80" font-weight="bold">YOONA</text><text x="220" y="460" fill="#fff" font-family="sans-serif" font-size="30">LOCAL PHOTO FIXTURE</text></svg>' });
        else await route.continue();
      });
      for (const [slug, selector, card, count, expectedColumns] of [
        ['pelna-oferta', '.yn-catalog-grid', '.yn-catalog-card', 41, viewport.width > 1100 ? 4 : viewport.width > 760 ? 3 : viewport.width > 480 ? 2 : 1],
        ['nasze-realizacje', '.yn-project-gallery-grid', '.yn-project-gallery-card', 30, viewport.width > 1000 ? 3 : viewport.width > 600 ? 2 : 1],
      ]) {
        const label = name + '/' + slug;
        const response = await page.goto(base + '/' + slug + '/', { waitUntil:'networkidle', timeout:60000 });
        await page.evaluate(() => document.fonts.ready);
        check(response.status() === 200 && await page.locator(card).count() === count, label + ': exact requested card count renders.');
        check(await page.locator('h1').count() === 1, label + ': exactly one semantic page heading.');
        const geometry = await page.locator(selector).evaluate((grid, card) => {
          const rect = grid.getBoundingClientRect();
          const cards = Array.from(grid.querySelectorAll(card));
          return { rect:rect.toJSON(), columns:getComputedStyle(grid).gridTemplateColumns.split(' ').length, overflow:document.documentElement.scrollWidth-innerWidth,
            cards:cards.map(element => { const title=element.querySelector('h2'); const image=element.querySelector('img'); const box=title.getBoundingClientRect(); const range=document.createRange(); range.selectNodeContents(title); return { text:element.innerText.trim(), title:title.innerText.trim(), image:!!image, alt:image && image.alt, font:getComputedStyle(title).fontFamily, transform:getComputedStyle(title).textTransform, clipped:title.scrollWidth > title.clientWidth+2 || Array.from(range.getClientRects()).some(rect=>rect.left<box.left-2 || rect.right>box.right+2), extra:element.querySelectorAll('p,time,.yn-archive-excerpt').length, imageRatio:image ? image.getBoundingClientRect().width/image.getBoundingClientRect().height :0 }; }) };
        }, card);
        check(geometry.columns === expectedColumns, label + ': responsive grid has ' + expectedColumns + ' columns.');
        const margin = Math.max(20, Math.min(viewport.width * .03, 72));
        check(Math.abs(geometry.rect.left - margin) <= 3 && Math.abs(viewport.width-geometry.rect.right-margin) <= 3, label + ': full viewport layout has equal deliberate ' + margin + 'px side margins.');
        const header = await page.locator('.yn-header-inner').boundingBox();
        check(Math.abs(header.x-geometry.rect.x)<=2 && Math.abs(header.width-geometry.rect.width)<=2, label + ': logo and navigation align with the collection margins.');
        check(geometry.overflow <= 2 && geometry.cards.every(item=>!item.clipped), label + ': full page and every real title range fit without clipping.');
        check(geometry.cards.every(item=>item.image && (slug==='nasze-realizacje' || item.alt) && item.text===item.title && item.extra===0), label + ': each card contains only a photograph and its accessible title.');
        check(geometry.cards.every(item=>item.transform==='uppercase' && item.font.includes('YN Manrope')), label + ': service and project titles use the consistent local uppercase font.');
        const cssUrl=await page.locator('link[href*="/assets/design.css"]').getAttribute('href');
        const css=await page.request.get(cssUrl);
        check(css.status()===200 && crypto.createHash('sha256').update(await css.body()).digest('hex')===expectedCss, label + ': served stylesheet exactly matches shipped source.');
        if (slug==='pelna-oferta') {
          const links=await page.locator(card+' a').evaluateAll(nodes=>nodes.map(node=>node.href).sort());
          check(JSON.stringify(links)===JSON.stringify(servicePaths), label + ': all 41 reviewed services link exactly once to their own public pages.');
          check(await page.locator('#collection-preserved-before,#collection-preserved-after').count()===0, label + ': the catalogue replaces the former duplicate offer body.');
        } else {
          const links=await page.locator(card+' a').evaluateAll(nodes=>nodes.map(node=>node.href));
          const expected=Array.from({length:30},(_,index)=>base+'/collection-project-'+(40-index)+'/');
          check(JSON.stringify(links)===JSON.stringify(expected), label + ': native first page uses newest date and stable ID order.');
          check(!/FORBIDDEN_EXCERPT|FORBIDDEN_BODY|FORBIDDEN_PROJECT/.test(await page.locator(selector).innerText()), label + ': excerpts, post bodies and restricted/no-photo posts are absent.');
          check(await page.locator('.yn-project-gallery-card img').first().evaluate(image=>image.complete && image.naturalWidth>0), label + ': an actual local WordPress attachment loads successfully.');
          const next=page.locator('.pagination .next');
          check(await next.count()===1 && /\/page\/2\//.test(await next.getAttribute('href')), label + ': WordPress provides native next-page navigation.');
          await page.screenshot({ path:path.join(output,name+'-'+slug+'.png'), fullPage:false });
          await next.click(); await page.waitForLoadState('networkidle');
          const second=await page.locator(card+' a').evaluateAll(nodes=>nodes.map(node=>node.href));
          check(second.length===10 && JSON.stringify(second)===JSON.stringify(Array.from({length:10},(_,index)=>base+'/collection-project-'+(10-index)+'/')) && second.every(link=>!links.includes(link)), label + ': second native page has the remaining ten unique projects.');
        }
        if(slug==='pelna-oferta') await page.screenshot({path:path.join(output,name+'-'+slug+'.png'),fullPage:false});
      }
      for(const slug of ['plotki-ogrodki-piwne','o-nas','kontakt']) {
        await page.goto(base+'/'+slug+'/',{waitUntil:'networkidle'});
        check(await page.locator('#collection-preserved-before').count()===1 && await page.locator('#collection-preserved-after').count()===1 && !/MASZ PYTANIE/i.test(await page.locator('main').innerText()),name+'/'+slug+': the repeated contact block disappears while following and preceding content remain.');
      }
      await page.goto(base+'/collection-unlisted/',{waitUntil:'networkidle'});
      check(!/MASZ PYTANIE/i.test(await page.locator('main').innerText()) && /UNLISTED SECRET BODY/.test(await page.locator('main').innerText()),name+': a newly created route removes the repeated block and retains unrelated content.');
      await page.goto(base+'/collection-protected/',{waitUntil:'networkidle'});
      check(await page.locator('.post-password-form').count()===1 && !/UNLISTED SECRET BODY/.test(await page.locator('main').innerText()),name+': password protection is preserved.');
      for(const query of ['?et_fb=1','?et_pb_preview=1','?preview=true']) {
        await page.goto(base+'/pelna-oferta/'+query,{waitUntil:'networkidle'});
        check(await page.locator('.yn-offer-catalog').count()===0,name+': catalogue does not override the builder/preview route '+query);
      }
      await context.close();
    }
    const context=await browser.newContext(); const page=await context.newPage();
    page.on('pageerror', error=>errors.push(error.message));
    for(const mode of ['hide-services','protect-catalog','protect-projects']) {
      wp('eval-file','/var/www/html/yoona-tests/integration-collections.php',mode,'--skip-themes');
      try {
        await page.goto(base+(mode==='protect-projects'?'/nasze-realizacje/':'/pelna-oferta/'),{waitUntil:'networkidle'});
        if(mode==='hide-services') check(await page.locator('.yn-catalog-card').count()===38 && await page.locator('.yn-catalog-card a[href$="/litery-3d/"],.yn-catalog-card a[href$="/reklamy-swietlne/"],.yn-catalog-card a[href$="/tablice-olsztyn/"]').count()===0,'Draft, private and password-protected service pages are omitted from the real catalogue.');
        else if(mode==='protect-catalog') check(await page.locator('.yn-offer-catalog').count()===0 && await page.locator('.post-password-form').count()===1,'A password-protected full-offer page keeps its password form and excludes the catalogue.');
        else check(await page.locator('.yn-project-gallery').count()===0,'A protected posts-index configuration excludes the dedicated project gallery.');
      } finally {wp('eval-file','/var/www/html/yoona-tests/integration-collections.php','restore-visibility','--skip-themes');}
    }
    await page.goto(base+'/nasze-realizacje/',{waitUntil:'networkidle'});
    check(await page.locator('.yn-project-gallery').count()===1 && await page.locator('.yn-project-gallery-card').count()===30,'After exclusions are restored, the normal native projects page selects its gallery and thirty photographs.');
    try {
      wp('option','update','yoona_seo_design_enabled','0');
      for(const slug of ['pelna-oferta','nasze-realizacje','plotki-ogrodki-piwne']) {
        await page.goto(base+'/'+slug+'/',{waitUntil:'networkidle'});
        check(await page.locator('body.yn-design,.yn-offer-catalog,.yn-project-gallery').count()===0,'The design off switch restores the original theme for '+slug+'.');
      }
    } finally {wp('option','delete','yoona_seo_design_enabled');}
    await context.close();
    check(errors.length===0,'No uncaught frontend JavaScript errors.');
    console.log('Tested design.css SHA-256: '+expectedCss);
    console.log('Success: '+assertions+' real Chromium collection assertions passed.');
    console.log('Screenshots: '+output+' (unavailable catalogue photographs are explicitly labelled synthetic probes).');
  } finally {await browser.close();}
})().catch(error=>{console.error(error.stack||error.message);process.exitCode=1;});
