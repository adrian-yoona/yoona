#!/usr/bin/env node
'use strict';

// Regression for the unstyled homepage directory reported in Edge. Runs the
// installed Chromium with an Edge/Windows UA by default, or an official
// Microsoft Edge executable supplied via YOONA_TEST_EDGE.
// Real plugin CSS, font files, SVG helper and public offer titles; local markup.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const http = require('node:http');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {chromium} = require('playwright');

(async () => {
  const plugin = path.join(__dirname, '../plugin/yoona-seo');
  const mainCss = fs.readFileSync(path.join(plugin, 'assets/design.css'), 'utf8');
  const collectionsCss = fs.readFileSync(path.join(plugin, 'assets/collections.css'), 'utf8');
  const guides = JSON.parse(fs.readFileSync(path.join(plugin, 'content/service-guides.json'), 'utf8'));
  const iconPhp = "define('ABSPATH', '/'); require $argv[1]; $out=[]; foreach(array_keys(json_decode(file_get_contents($argv[2]),true)) as $path){$out[$path]=Yoona_SEO_Icons::service($path);} echo json_encode($out);";
  // A Docker-only PHP setup can export this public SVG map to a local file.
  const icons = JSON.parse(process.env.YOONA_TEST_ICON_JSON ? fs.readFileSync(process.env.YOONA_TEST_ICON_JSON, 'utf8') : execFileSync('php', ['-r', iconPhp, path.join(plugin, 'includes/class-yoona-seo-icons.php'), path.join(plugin, 'content/service-guides.json')], {encoding:'utf8'}));
  const esc = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));
  const offers = Object.entries(guides);
  const cards = offers.slice(0, 8).map(([url, guide]) => `<a class="yn-service-card" href="${esc(url)}"><div class="yn-service-image"><img src="/fixture.svg" width="900" height="675" alt="Local test photo"></div><h3>${esc(guide.title)}<span aria-hidden="true">↗</span></h3></a>`).join('');
  const directory = offers.slice(8).map(([url, guide]) => `<li><a class="yn-category-link" href="${esc(url)}">${icons[url]}<span class="yn-category-label">${esc(guide.title)}</span><span aria-hidden="true">↗</span></a></li>`).join('');
  const photo = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="675"><rect width="900" height="675" fill="#293c36"/><text x="40" y="330" fill="white" font-family="sans-serif" font-size="35">LOCAL DIRECTORY PROBE</text></svg>';
  const modes = ['normal', 'collections-blocked', 'stale-main', 'minified'];
  let staleCss = '';
  let assertions = 0;
  const check = (condition, label) => { assert(condition, label); assertions++; console.log('PASS Edge directory351 ' + label); };
  // Preserve selector/value token boundaries; this is a whitespace/comment
  // compaction probe, not a claim to execute LiteSpeed's production optimizer.
  const minify = css => css.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\s+/g, ' ');
  const server = http.createServer((req, res) => {
    const url = new URL(req.url, 'http://localhost');
    const mode = url.searchParams.get('mode') || 'normal';
    if (url.pathname === '/design.css') {
      res.setHeader('content-type', 'text/css');
      res.end(mode === 'stale-main' || mode === 'baseline' ? staleCss : mode === 'minified' ? minify(mainCss) : mainCss);
    } else if (url.pathname === '/collections.css') {
      res.setHeader('content-type', 'text/css');
      // Deliberately stale auxiliary rules reproduce the original symptom when
      // the main directory rules are also absent. Fresh component rules must
      // recover with either stylesheet individually available.
      res.end(mode === 'baseline' ? '.yn-design .yn-icon{display:inline-block;vertical-align:middle}.yn-design .yn-category-link{justify-content:flex-start;gap:12px}.yn-design .yn-category-link .yn-icon-service{width:24px;height:24px;color:#9b410c}.yn-design .yn-category-link>.yn-category-label{flex:1 1 auto;min-width:0;color:inherit;font-size:inherit;overflow-wrap:anywhere;}' : mode === 'minified' ? minify(collectionsCss) : collectionsCss);
    } else if (url.pathname.startsWith('/fonts/')) {
      const filename = path.join(plugin, 'assets/fonts', path.basename(url.pathname));
      if (!fs.existsSync(filename)) { res.writeHead(404); res.end(); return; }
      res.setHeader('content-type', 'font/woff2'); res.end(fs.readFileSync(filename));
    } else if (url.pathname === '/fixture.svg') {
      res.setHeader('content-type', 'image/svg+xml'); res.end(photo);
    } else {
      res.setHeader('content-type', 'text/html; charset=utf-8');
      res.end(`<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:0}a{color:#ff6500}ul{list-style:none;padding:0}h3{margin:0;font-family:Arial,sans-serif;font-size:22px}</style><link rel="stylesheet" href="/design.css?mode=${mode}"><link rel="stylesheet" href="/collections.css?mode=${mode}"></head><body class="yn-design yn-front-page"><main class="yn-main"><section class="yn-section yn-services"><div class="yn-container"><div class="yn-service-grid">${cards}</div><div class="yn-home-categories" aria-labelledby="yn-home-categories-title"><h3 id="yn-home-categories-title">Wszystkie możliwości dla Twojej marki</h3><ul class="yn-category-grid">${directory}</ul></div></div></section></main></body></html>`);
    }
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const base = `http://127.0.0.1:${server.address().port}`;
  const actualEdge = process.env.YOONA_TEST_EDGE || '';
  const browser = await chromium.launch({executablePath:actualEdge || process.env.YOONA_TEST_CHROMIUM || '/usr/bin/chromium', headless:true, args:['--no-sandbox']});
  try {
    const parser = await browser.newPage();
    const browserUa = await parser.evaluate(() => navigator.userAgent);
    console.log(`Browser ${browser.version()}; UA ${browserUa}`);
    if (actualEdge) check(/Edg\//.test(browserUa), 'official Microsoft Edge executable identifies itself as Edge.');
    // CSSOM removes only directory/icon rules from the actual current main
    // file, leaving eight already styled photo cards as in the user screenshot.
    staleCss = await parser.evaluate(css => {
      const sheet = new CSSStyleSheet(); sheet.replaceSync(css);
      const keep = rules => [...rules].map(rule => {
        if (rule.cssRules && !rule.selectorText) {
          const inner = keep(rule.cssRules);
          if (!inner.trim()) return '';
          const prefix = rule.cssText.slice(0, rule.cssText.indexOf('{'));
          return prefix + '{' + inner + '}';
        }
        return rule.selectorText && /\.yn-(?:category|home-categories)/.test(rule.selectorText) ? '' : rule.cssText;
      }).join('\n');
      return keep(sheet.cssRules);
    }, mainCss);
    await parser.goto(`${base}/?mode=baseline`, {waitUntil:'networkidle'});
    const baseline = await parser.evaluate(() => ({grid:getComputedStyle(document.querySelector('.yn-category-grid')).display, link:getComputedStyle(document.querySelector('.yn-category-link')).display, photoGrid:getComputedStyle(document.querySelector('.yn-service-grid')).display}));
    check(baseline.grid !== 'grid' && baseline.link === 'inline' && baseline.photoGrid === 'grid', 'controlled stale-style baseline reproduces inline directory while photo cards remain styled: ' + JSON.stringify(baseline));
    await parser.close();
    for (const mode of modes) {
      for (const width of [320, 390, 600, 760, 900, 1440, 2560]) {
        const page = await browser.newPage({viewport:{width,height:950}, deviceScaleFactor:1.25, ...(actualEdge ? {} : {userAgent:'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0'})});
        let blocked = 0;
        if (mode === 'collections-blocked') await page.route('**/collections.css?*', async route => { blocked++; await route.abort('failed'); });
        await page.goto(`${base}/?mode=${mode}`, {waitUntil:'networkidle'});
        await page.evaluate(() => document.fonts.ready);
        const data = await page.evaluate(() => {
          const grid = document.querySelector('.yn-category-grid');
          const heading = document.querySelector('.yn-home-categories>h3');
          return {overflow:document.documentElement.scrollWidth-innerWidth, columns:getComputedStyle(grid).gridTemplateColumns.split(' ').length, display:getComputedStyle(grid).display, heading:{font:getComputedStyle(heading).fontFamily, size:getComputedStyle(heading).fontSize, transform:getComputedStyle(heading).textTransform}, links:[...grid.querySelectorAll('a')].map(link => {
            const box=link.getBoundingClientRect(), icon=link.querySelector('svg').getBoundingClientRect(), label=link.querySelector('.yn-category-label'), labelBox=label.getBoundingClientRect(), arrowElement=link.querySelector('span[aria-hidden]'), arrow=arrowElement.getBoundingClientRect(), arrowStyle=getComputedStyle(arrowElement), arrowLine=getComputedStyle(arrowElement,'::before'), arrowHead=getComputedStyle(arrowElement,'::after'), style=getComputedStyle(link), labelStyle=getComputedStyle(label), range=document.createRange(); range.selectNodeContents(label);
            return {href:link.getAttribute('href'), height:box.height, display:style.display, font:style.fontFamily, size:parseFloat(style.fontSize), transform:style.textTransform, color:labelStyle.color, iconWidth:icon.width, iconHeight:icon.height, arrowFont:arrowStyle.fontSize, arrowWidth:arrow.width, drawnArrow:arrowLine.content==='""' && arrowHead.content==='""' && parseFloat(arrowHead.borderTopWidth)===1, overlap:icon.right>labelBox.left+.5 || labelBox.right>arrow.left+.5, clipped:label.scrollWidth>label.clientWidth+1 || [...range.getClientRects()].some(rect=>rect.left<labelBox.left-1 || rect.right>labelBox.right+1)};
          })};
        });
        const prefix = `${mode} ${width}px`;
        check(data.overflow <= 1, `${prefix}: no horizontal overflow.`);
        check(data.display === 'grid' && data.columns === (width <= 600 ? 1 : width <= 760 ? 2 : 3), `${prefix}: responsive directory columns.`);
        check(data.links.length === offers.length-8 && new Set(data.links.map(link=>link.href)).size === offers.length-8, `${prefix}: every offer remains a unique, visible link.`);
        check(data.links.every(link=>link.display === 'flex' && link.height >= 44 && !link.overlap && !link.clipped), `${prefix}: touch targets, complete labels and separated icon/text/arrow.`);
        check(data.links.every(link=>link.font.includes('YN Manrope') && link.size >= 12 && link.transform === 'uppercase' && link.color === 'rgb(25, 25, 22)' && link.iconWidth === 24 && link.iconHeight === 24), `${prefix}: homepage typography, dark readable text, fixed-size icons.`);
        check(data.links.every(link=>link.arrowFont==='0px' && link.arrowWidth===12 && link.drawnArrow), `${prefix}: arrows use CSS strokes, independent of font glyph availability.`);
        check(data.heading.font.includes('YN Manrope') && data.heading.size === '13px' && data.heading.transform === 'uppercase', `${prefix}: compact matching directory heading.`);
        if (mode === 'collections-blocked') check(blocked === 1, `${prefix}: auxiliary stylesheet really failed to load.`);
        await page.locator('.yn-category-link').first().focus();
        const focus = await page.locator('.yn-category-link').first().evaluate(link=>({style:getComputedStyle(link).outlineStyle,width:parseFloat(getComputedStyle(link).outlineWidth)}));
        check(focus.style !== 'none' && focus.width >= 2, `${prefix}: keyboard focus stays visible.`);
        if (process.env.YOONA_TEST_SCREENSHOTS && [390,1440].includes(width)) {
          fs.mkdirSync(process.env.YOONA_TEST_SCREENSHOTS,{recursive:true});
          await page.locator('.yn-home-categories').screenshot({path:path.join(process.env.YOONA_TEST_SCREENSHOTS,`directory351-${mode}-${width}.png`)});
        }
        await page.close();
      }
    }
    console.log(`PASS ${assertions} assertions: ${actualEdge ? 'official Microsoft Edge on Linux' : 'Chromium with Edge/Windows UA'}, 7 widths × 4 stylesheet states, plus controlled reproduction. Public component fixtures; no access to the user's Windows Edge profile or production CSS cache.`);
  } finally {
    await browser.close();
    await new Promise(resolve=>server.close(resolve));
  }
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
