#!/usr/bin/env node
'use strict';
// Read-only HTTP integration: actual WP homepage/template/enqueue and real Edge.
// Images are labelled local placeholders; all CSS/fonts come from WordPress.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {chromium} = require('playwright');

(async () => {
 const base=process.argv[2];
 assert(/^http:\/\/127\.0\.0\.1:\d+$/.test(base),'Disposable loopback WordPress site required.');
 assert(process.env.YOONA_TEST_EDGE,'Supply a verified Microsoft Edge executable via YOONA_TEST_EDGE.');
 const plugin=path.join(__dirname,'../plugin/yoona-seo'), sha=bytes=>crypto.createHash('sha256').update(bytes).digest('hex');
 const photo='<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1100"><rect width="1600" height="1100" fill="#293c36"/><text x="40" y="550" fill="white" font-family="sans-serif" font-size="45">PUBLIC LOCAL HOMEPAGE PROBE</text></svg>';
 const browser=await chromium.launch({executablePath:process.env.YOONA_TEST_EDGE,headless:true,args:['--no-sandbox']});
 let assertions=0;
 const check=(condition,label)=>{assert(condition,label);assertions++;console.log('PASS Edge directory351 WP '+label);};
 try {
  for(const width of [1440,390]) {
   const page=await browser.newPage({viewport:{width,height:950},deviceScaleFactor:1.25});
   const loaded=[];
   page.on('response',response=>{if(/\/assets\/(design|collections)\.css\?/.test(response.url()))loaded.push(response);});
   await page.route('**/*',async route=>{
    const url=new URL(route.request().url());
    if(url.origin!==base){await route.abort();return;}
    if(route.request().resourceType()==='image'){await route.fulfill({status:200,contentType:'image/svg+xml',body:photo});return;}
    await route.continue();
   });
   const response=await page.goto(base+'/',{waitUntil:'networkidle',timeout:45000});
   check(response.status()===200,`${width}px: actual homepage HTTP 200.`);
   await page.evaluate(()=>document.fonts.ready);
   check(/Edg\//.test(await page.evaluate(()=>navigator.userAgent)),`${width}px: real Edge native user agent.`);
   check(loaded.length===2,`${width}px: WordPress enqueues both directory stylesheets.`);
   for(const filename of ['design.css','collections.css']) {
    const asset=loaded.find(item=>new URL(item.url()).pathname.endsWith('/assets/'+filename));
    check(!!asset && asset.status()===200 && new URL(asset.url()).searchParams.get('ver')==='3.5.1',`${width}px: ${filename} HTTP 200 with version3.5.1 URL.`);
    const actual=sha(await asset.body()),expected=sha(fs.readFileSync(path.join(plugin,'assets',filename)));
    check(actual===expected,`${width}px: ${filename} served SHA256 ${actual} equals source.`);
   }
   const data=await page.evaluate(()=>{
    const grid=document.querySelector('.yn-category-grid'),heading=document.querySelector('.yn-home-categories>h3');
    return {front:document.body.classList.contains('yn-front-page'),overflow:document.documentElement.scrollWidth-innerWidth,cards:document.querySelectorAll('.yn-service-card').length,columns:getComputedStyle(grid).gridTemplateColumns.split(' ').length,headingFont:getComputedStyle(heading).fontFamily,headingCase:getComputedStyle(heading).textTransform,photoUrls:[...document.querySelectorAll('.yn-service-card')].map(a=>a.href),links:[...grid.querySelectorAll('a')].map(link=>{
     const label=link.querySelector('.yn-category-label'),icon=link.querySelector('svg'),arrow=link.querySelector('span[aria-hidden]'),box=link.getBoundingClientRect(),lb=label.getBoundingClientRect(),ib=icon.getBoundingClientRect(),ab=arrow.getBoundingClientRect(),s=getComputedStyle(link),ls=getComputedStyle(label),as=getComputedStyle(arrow),before=getComputedStyle(arrow,'::before'),after=getComputedStyle(arrow,'::after'),range=document.createRange();range.selectNodeContents(label);
     return {href:link.href,text:label.textContent.trim(),height:box.height,font:s.fontFamily,size:parseFloat(s.fontSize),textCase:s.textTransform,color:ls.color,iconWidth:ib.width,iconHeight:ib.height,overlap:ib.right>lb.left+.5 || lb.right>ab.left+.5,clipped:label.scrollWidth>label.clientWidth+1 || [...range.getClientRects()].some(rect=>rect.left<lb.left-1 || rect.right>lb.right+1),arrow:as.fontSize==='0px' && ab.width===12 && before.content==='""' && after.content==='""' && parseFloat(after.borderTopWidth)===1};
    })};
   });
   check(data.front,`${width}px: actual plugin front-page template is selected.`);
   check(data.cards===8 && data.links.length===33,`${width}px: fixture's 41 public offers appear as 8 photo cards plus33 directory links.`);
   check(new Set([...data.photoUrls,...data.links.map(link=>link.href)]).size===41,`${width}px: every public offer appears once.`);
   check(data.columns===(width>760?3:1),`${width}px: real WordPress directory has responsive ${width>760?3:1} columns.`);
   check(data.overflow<=1,`${width}px: actual full homepage has no horizontal overflow.`);
   check(data.links.every(link=>link.height>=44 && !link.overlap && !link.clipped && link.text),`${width}px: full labels, separated elements and44px-or-larger targets.`);
   check(data.links.every(link=>link.font.includes('YN Manrope') && link.size>=12 && link.textCase==='uppercase' && link.color==='rgb(25, 25, 22)'),`${width}px: dark uppercase Manrope directory typography.`);
   check(data.links.every(link=>link.iconWidth===24 && link.iconHeight===24 && link.arrow),`${width}px: fixed24px SVG icons and CSS-drawn arrows.`);
   check(data.headingFont.includes('YN Manrope') && data.headingCase==='uppercase',`${width}px: compact directory heading uses matching typography.`);
   if(process.env.YOONA_TEST_SCREENSHOTS){fs.mkdirSync(process.env.YOONA_TEST_SCREENSHOTS,{recursive:true});await page.locator('.yn-home-categories').screenshot({path:path.join(process.env.YOONA_TEST_SCREENSHOTS,`directory351-real-wp-${width}.png`)});}
   await page.close();
  }
  console.log(`PASS ${assertions} read-only actual WordPress homepage assertions in Microsoft Edge ${browser.version()},1440+390px. No CLI calls, database mutations or theme changes. Public synthetic photo fixtures; actual plugin template, CSS, local fonts and PHP-generated SVGs.`);
 } finally {await browser.close();}
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
