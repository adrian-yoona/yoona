#!/usr/bin/env node
'use strict';
// Geometry regression using shipped CSS/fonts and clearly labelled synthetic card photos.
// This probe does not claim to run the licensed Divi renderer or production photos.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
(async () => {
  const plugin = path.join(__dirname, '../plugin/yoona-seo');
  const guides = JSON.parse(fs.readFileSync(path.join(plugin, 'content/service-guides.json')));
  const css = ['design.css', 'collections.css'].map(name => fs.readFileSync(path.join(plugin, 'assets', name), 'utf8')).join('\n');
  const photo = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="900" height="675"><rect width="900" height="675" fill="#253b39"/><path d="M0 450 900 220V675H0Z" fill="#af9471"/><text x="40" y="360" font-family="sans-serif" font-size="35" fill="white">LOCAL COLLECTION PROBE</text></svg>');
  const escape = text => text.replace(/[&<>"']/g, value => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[value]));
  const cards = Object.entries(guides).map(([url, guide], index) => ({url, title:guide.title, index}));
  const catalogue = cards.map(card => `<article class="yn-catalog-card"><a href="${card.url}"><div class="yn-catalog-image"><img src="${photo}" alt="Synthetic collection photo"></div><h2>${escape(card.title)}</h2></a></article>`).join('');
  const projects = cards.slice(0, 24).map(card => `<article class="yn-project-gallery-card"><a href="/project-${card.index}/"><div class="yn-project-gallery-image"><img src="${photo}" alt=""></div><h2>${escape(card.title)} — REALIZACJA</h2></a></article>`).join('');
  const native = cards.slice(0, 24).map(card => `<article class="et_pb_post"><a class="entry-featured-image-url" href="/native-${card.index}/"><img src="${photo}" alt=""></a><h2 class="entry-title"><a href="/native-${card.index}/">${escape(card.title)} — REALIZACJA</a></h2></article>`).join('');
  const html = `<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="http://collections.test/"><style>${css}</style></head><body class="yn-design yn-collection-page"><main class="yn-main"><header class="yn-collection-heading"><div class="yn-collection-container"><p class="yn-eyebrow">PUBLICZNA STRUKTURA TESTOWA</p><h1>Pełna oferta</h1></div></header><div class="yn-collection-container yn-collection-content"><div class="yn-catalog-grid">${catalogue}</div><div class="yn-project-gallery-grid">${projects}</div></div><div class="yn-native-divi"><div class="yn-divi-collection-row"><div class="yn-divi-project-index"><div class="et_pb_ajax_pagination_container">${native}<div class="pagination"><a href="/page/2/">Następne realizacje →</a></div></div></div></div></div></main></body></html>`;
  const browser = await chromium.launch({executablePath:process.env.YOONA_TEST_CHROMIUM || '/usr/bin/chromium', headless:true, args:['--no-sandbox']});
  let assertions = 0;
  const check = (condition, label) => { assert(condition, label); assertions++; console.log('PASS Collections35 browser ' + label); };
  try {
    for (const [width, expected] of [[2560,6],[1920,6],[1440,6],[1399,4],[1100,4],[1099,3],[900,3],[760,3],[759,2],[600,2],[480,2],[479,1],[390,1],[320,1]]) {
      const page = await browser.newPage({viewport:{width,height:950}});
      await page.route('**/*', async route => {
        const filename = new URL(route.request().url()).pathname.split('/').pop();
        const font = path.join(plugin,'assets/fonts',filename);
        if (/\.woff2$/.test(filename) && fs.existsSync(font)) await route.fulfill({status:200, contentType:'font/woff2', body:fs.readFileSync(font)});
        else await route.fulfill({status:200, contentType:'text/html', body:html});
      });
      await page.goto('http://collections.test/', {waitUntil:'networkidle'});
      await page.evaluate(() => document.fonts.ready);
      const data = await page.evaluate(() => {
        const grids = [...document.querySelectorAll('.yn-catalog-grid,.yn-project-gallery-grid,.yn-divi-project-index .et_pb_ajax_pagination_container')];
        return {overflow:document.documentElement.scrollWidth-innerWidth, grids:grids.map(grid => ({columns:getComputedStyle(grid).gridTemplateColumns.split(' ').length, x:grid.getBoundingClientRect().x, width:grid.getBoundingClientRect().width, cards:[...grid.querySelectorAll('article')].map(article => {
          const heading=article.querySelector('h2'), box=heading.getBoundingClientRect(), range=document.createRange(); range.selectNodeContents(heading);
          const style=getComputedStyle(heading), image=article.querySelector('img').getBoundingClientRect();
          return {title:heading.innerText, font:style.fontFamily, fontSize:parseFloat(style.fontSize), clipped:heading.scrollWidth>heading.clientWidth+1 || [...range.getClientRects()].some(rect=>rect.left<box.left-1||rect.right>box.right+1), ratio:image.width/image.height, extra:article.querySelectorAll('p,time,.post-meta,.post-content').length};
        })}))};
      });
      check(data.overflow<=1, `${width}px: viewport has no horizontal overflow.`);
      for (let index=0; index<data.grids.length; index++) {
        const grid=data.grids[index], label=`${width}px grid${index+1}`;
        check(grid.columns===expected, `${label}: ${expected} responsive columns.`);
        check(grid.cards.length===(index===0?47:24), `${label}: all requested cards render.`);
        const margin=Math.max(16,Math.min(width*.025,56));
        check(Math.abs(grid.x-margin)<=2 && Math.abs(grid.width-(width-2*margin))<=2, `${label}: equal full-width ${margin}px side margins.`);
        check(grid.cards.every(card=>!card.clipped && card.font.includes('YN Manrope') && card.fontSize>=12), `${label}: complete Manrope titles fit without truncation.`);
        check(grid.cards.every(card=>Math.abs(card.ratio-4/3)<.01 && card.extra===0), `${label}: only 4:3 photos and titles.`);
      }
      if (process.env.YOONA_TEST_SCREENSHOTS && [1440,760,390,320].includes(width)) {
        fs.mkdirSync(process.env.YOONA_TEST_SCREENSHOTS,{recursive:true});
        await page.screenshot({path:path.join(process.env.YOONA_TEST_SCREENSHOTS,`collections35-${width}.png`), fullPage:false});
      }
      await page.close();
    }
    console.log(`PASS ${assertions} collection geometry assertions across 14 viewport/breakpoint widths. Synthetic markup/photos, actual shipped CSS/fonts; native WordPress queries tested separately.`);
  } finally { await browser.close(); }
})().catch(error=>{ console.error(error.stack); process.exitCode=1; });
