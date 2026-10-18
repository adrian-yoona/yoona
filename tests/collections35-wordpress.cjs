#!/usr/bin/env node
'use strict';
// Actual WordPress templates and native SQL pagination; unavailable remote photos get labelled probes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {execFileSync} = require('node:child_process');
const {chromium} = require('playwright');
(async () => {
  const [base, cli] = process.argv.slice(2);
  if (!/^http:\/\/127\.0\.0\.1:\d+$/.test(base || '') || !/^yoona-seo-test-.*-cli$/.test(cli || '')) throw new Error('Disposable loopback WordPress URL and CLI container required.');
  const wp = mode => execFileSync('docker',['exec','-u','0',cli,'wp','--allow-root','--path=/var/www/html','eval-file','/var/www/html/yoona-tests/collections35.php',mode,'--skip-themes'],{encoding:'utf8'});
  const expected = JSON.parse(wp('export-state'));
  assert.equal(expected.offers.length,47,'All 47 public offer pages must be prepared before this test.');
  assert.equal(expected.first.length,24,'At least 24 qualifying projects must be prepared.');
  assert.equal(expected.second.length,24,'At least 48 qualifying projects must be prepared.');
  const social = JSON.parse(fs.readFileSync(path.join(__dirname,'../plugin/yoona-seo/assets/design-data.json'))).social;
  const expectedCss = crypto.createHash('sha256').update(fs.readFileSync(path.join(__dirname,'../plugin/yoona-seo/assets/collections.css'))).digest('hex');
  const output=process.env.YOONA_TEST_SCREENSHOTS || '/tmp/yoona35-collections-screenshots';
  fs.mkdirSync(output,{recursive:true});
  const browser=await chromium.launch({executablePath:process.env.YOONA_TEST_CHROMIUM || '/usr/bin/chromium',headless:true,args:['--no-sandbox']});
  let assertions=0;
  const errors=[];
  const check=(condition,label)=>{assert(condition,label);assertions++;console.log('PASS Collections35 WordPress '+label);};
  try {
    for (const [width,columns] of [[2560,6],[1920,6],[1440,6],[1200,4],[900,3],[760,3],[600,2],[480,2],[390,1],[320,1]]) {
      const page=await browser.newPage({viewport:{width,height:950}});
      page.on('pageerror',error=>errors.push(error.message));
      await page.route('**/*',async route=>{
        const request=route.request();
        if (request.resourceType()==='image' && !request.url().includes('yoona35-collection-photo')) await route.fulfill({status:200,contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="900"><rect width="1200" height="900" fill="#253b39"/><path d="M0 600 1200 300V900H0Z" fill="#aa9277"/><text x="60" y="460" fill="white" font-family="sans-serif" font-size="40">LOCAL PHOTO PROBE - NOT A PRODUCTION PHOTO</text></svg>'});
        else await route.continue();
      });
      // Warm any lazy WordPress metadata before the strict source-preservation snapshot.
      if (width===2560) {
        for (const url of ['/pelna-oferta/','/nasze-realizacje/','/nasze-realizacje/page/2/']) await page.goto(base+url,{waitUntil:'networkidle'});
        console.log(wp('snapshot').trim());
      }
      for (const [slug,gridSelector,cardSelector,count] of [
        ['pelna-oferta','.yn-catalog-grid','.yn-catalog-card',47],
        ['nasze-realizacje','.yn-project-gallery-grid','.yn-project-gallery-card',24],
      ]) {
        const label=width+'px/'+slug;
        const response=await page.goto(base+'/'+slug+'/',{waitUntil:'networkidle'});
        await page.evaluate(()=>document.fonts.ready);
        check(response.status()===200 && await page.locator(cardSelector).count()===count,label+': exact public card count.');
        check(await page.locator('h1').count()===1,label+': one semantic H1.');
        const geometry=await page.locator(gridSelector).evaluate(grid=>({columns:getComputedStyle(grid).gridTemplateColumns.split(' ').length,rect:grid.getBoundingClientRect().toJSON(),overflow:document.documentElement.scrollWidth-innerWidth,cards:[...grid.querySelectorAll('article')].map(article=>{const heading=article.querySelector('h2'),box=heading.getBoundingClientRect(),range=document.createRange();range.selectNodeContents(heading);return {clipped:heading.scrollWidth>heading.clientWidth+1||[...range.getClientRects()].some(rect=>rect.left<box.left-1||rect.right>box.right+1),text:article.innerText.trim(),title:heading.innerText.trim(),font:getComputedStyle(heading).fontFamily,extra:article.querySelectorAll('p,time,.post-meta,.post-content').length,images:article.querySelectorAll('img').length};})}));
        check(geometry.columns===columns,label+': '+columns+' responsive columns.');
        const margin=Math.max(16,Math.min(width*.025,56));
        check(Math.abs(geometry.rect.x-margin)<=2 && Math.abs(geometry.rect.width-(width-2*margin))<=2,label+': deliberate full-screen side margins.');
        check(geometry.overflow<=1&&geometry.cards.every(card=>!card.clipped&&card.font.includes('YN Manrope')),label+': all full titles fit in the local Manrope font.');
        check(geometry.cards.every(card=>card.images===1&&card.text===card.title&&card.extra===0),label+': only photograph and title.');
        const links=await page.locator(cardSelector+' a').evaluateAll(nodes=>nodes.map(node=>node.href));
        if(slug==='pelna-oferta') {
          check(JSON.stringify(links)===JSON.stringify(expected.offers.map(card=>card.url)),label+': every public offer is linked immediately once, without pagination.');
          check(await page.locator('main .pagination').count()===0,label+': catalogue has no page limit.');
          check(await page.locator('.yn-catalog-image img[fetchpriority="high"]').count()===1,label+': only the first photograph has high loading priority.');
        } else {
          check(JSON.stringify(links)===JSON.stringify(expected.first),label+': native date/ID sort matches the real SQL query.');
          check(!/HIDDEN_COLLECTION|FORBIDDEN_COLLECTION_PROJECT/.test(await page.locator(gridSelector).innerText()),label+': body, excerpt, draft/private/password/no-photo probes are absent.');
          const next=page.locator('.pagination .next');
          check(await next.count()===1&&/\/page\/2\//.test(await next.getAttribute('href')),label+': native next-page URL.');
          await next.click();await page.waitForLoadState('networkidle');
          const second=await page.locator(cardSelector+' a').evaluateAll(nodes=>nodes.map(node=>node.href));
          check(JSON.stringify(second)===JSON.stringify(expected.second)&&second.every(link=>!links.includes(link)),label+': next 24 unique public projects are on page two.');
        }
        const cssUrl=await page.locator('link[href*="/assets/collections.css"]').getAttribute('href');
        const css=await page.request.get(cssUrl);
        check(css.status()===200&&crypto.createHash('sha256').update(await css.body()).digest('hex')===expectedCss,label+': browser receives the exact shipped collection stylesheet.');
        const profiles=await page.locator('.yn-footer-social a').evaluateAll(nodes=>nodes.map(node=>({url:node.href,text:node.innerText.trim(),label:node.getAttribute('aria-label'),title:node.title,target:node.target,rel:node.rel,svg:node.querySelectorAll('svg[aria-hidden="true"][focusable="false"]').length,height:node.getBoundingClientRect().height,arrow:/[↗→]/.test(node.innerText)})));
        check(profiles.length===4&&profiles.every(profile=>profile.svg===1&&!profile.arrow&&profile.title&&profile.label.includes(profile.text)&&profile.height>=44&&profile.url===social[profile.text]&&profile.target==='_blank'&&profile.rel.includes('noopener')&&profile.rel.includes('noreferrer')),label+': four accessible recognizable social SVG links preserve the original public profiles.');
        if([1440,760,390,320].includes(width)) await page.screenshot({path:path.join(output,`${width}-${slug}.png`),fullPage:false});
      }
      await page.close();
    }
    console.log(wp('verify').trim());
    check(errors.length===0,'No JavaScript runtime errors during public collections browsing.');
    console.log(`PASS ${assertions} public WordPress collection assertions. ${expected.total} qualifying projects, ${expected.pages} native pages. Actual WP/Rank Math/templates/CSS; remote photo probes, licensed Divi renderer not claimed.`);
  } finally {await browser.close();}
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
