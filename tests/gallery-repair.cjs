#!/usr/bin/env node
'use strict';
// Real WordPress/Rank Math plus synthetic Divi callbacks. No claim to licensed Divi execution.
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {execFileSync}=require('node:child_process');
const {chromium}=require('playwright');
(async()=>{
 const base=process.argv[2], cli=process.argv[3];
 assert(/^http:\/\/127\.0\.0\.1:\d+$/.test(base)&&/^yoona-seo-test-gallery-.*-cli$/.test(cli),'Disposable gallery environment required.');
 const routes=Object.keys(JSON.parse(fs.readFileSync(path.join(__dirname,'../plugin/yoona-seo/assets/design-data.json'))).offer_heroes);
 const ownedRoutes=Object.keys(JSON.parse(fs.readFileSync(path.join(__dirname,'../plugin/yoona-seo/content/service-guides.json')))).filter(route=>!routes.includes(route));
 const wp=(...args)=>execFileSync('docker',['exec','-u','0',cli,'wp','--allow-root','--path=/var/www/html',...args],{encoding:'utf8'});
 const browser=await chromium.launch({executablePath:process.env.YOONA_TEST_CHROMIUM||'/usr/bin/chromium',headless:true,args:['--no-sandbox']});
 let count=0;const check=(condition,label)=>{assert(condition,label);++count;console.log('PASS Gallery repair '+label);};
 const errors=[];
 const fixture='<svg xmlns="http://www.w3.org/2000/svg" width="600" height="420"><rect width="600" height="420" fill="#24211e"/><text x="24" y="220" fill="#ff7000" font-size="24">LOCAL GALLERY STRUCTURE PROBE</text></svg>';
 try{
  console.log(wp('eval-file','/var/www/html/yoona-tests/gallery-repair.php','owned','--skip-themes').trim());
  // Warm Rank Math's legitimate first-view Open Graph cache before strict rows baseline.
  for(const route of [...routes,...ownedRoutes]) await (await fetch(base+route)).arrayBuffer();
  wp('eval-file','/var/www/html/yoona-tests/gallery-repair.php','snapshot','--skip-themes');
  for(const width of [1440,390,320]){
   const context=await browser.newContext({viewport:{width,height:950}});context.on('page',page=>page.on('pageerror',error=>errors.push(error.message)));
   await context.route('**/*',async route=>{if(route.request().resourceType()==='image')await route.fulfill({status:200,contentType:'image/svg+xml',body:fixture});else await route.continue();});
   const page=await context.newPage();
   for(const route of width===1440?routes:['/banery/','/frezowanie-cnc/','/markizy-z-logo/','/beczki-reklamowe/']){
    const response=await page.goto(base+route,{waitUntil:'networkidle'});
    check(response.status()===200&&await page.locator('main.yn-service-guide').count()===1,width+' service template '+route);
    check(await page.locator('.repair-gallery-section>.repair-gallery-row>.repair-gallery-column>.repair-gallery').count()===1,width+' gallery retains section / row / column ancestors '+route);
    check(await page.locator('.repair-gallery .et_pb_gallery_item').count()===3&&await page.locator('.repair-gallery .et_pb_gallery_item a>img').count()===3,width+' every source photo, lightbox link and item wrapper survives once '+route);
    check(await page.locator('.repair-bottom-section>.repair-bottom-row>.repair-bottom-left>.repair-cta').count()===1&&await page.locator('.repair-bottom-left>.repair-toggle').count()===1&&await page.locator('.repair-bottom-right>.repair-social').count()===1&&await page.locator('.repair-bottom-right>.repair-form').count()===1,width+' lower CTA, toggle, social links and form retain their actual columns '+route);
    check(await page.locator('.repair-retired-contact').count()===0&&await page.locator('main img[src*="agencja-reklamowa-olsztyn"]').count()===0,width+' retired knob contact section remains removed '+route);
    check(await page.locator('.yn-guide-media-grid .repair-gallery').count()===0,width+' source gallery never enters an unrelated image grid '+route);
    const columns=await page.locator('.repair-gallery .et_pb_gallery_items').evaluate(el=>getComputedStyle(el).gridTemplateColumns.split(' ').length);
    check(columns===(width>600?3:2),width+' wrapper-dependent original gallery stylesheet still applies '+route);
    const callbacks=JSON.parse(await page.locator('#repair-callback-counts').textContent());
    check(callbacks.et_pb_gallery===1&&callbacks.et_pb_cta===1&&callbacks.et_pb_toggle===1&&callbacks.et_pb_social_media_follow===1&&callbacks.et_pb_contact_form===1,width+' every preserved shortcode callback runs exactly once '+route);
    check((await page.title()).startsWith('REPAIR NATIVE ')&&await page.locator('meta[name="description"]').count()===1,width+' native Rank Math metadata stays present '+route);
    const over=await page.evaluate(()=>document.documentElement.scrollWidth>document.documentElement.clientWidth+2);
    check(!over,width+' gallery and bottom have no horizontal overflow '+route);
    if(route==='/banery/') {const output=process.env.YOONA_TEST_SCREENSHOTS||'/tmp/yoona-gallery-repair-screenshots';fs.mkdirSync(output,{recursive:true});await page.screenshot({path:path.join(output,width+'-offer.png'),fullPage:true});}
   }
   for(const route of ownedRoutes){
    const response=await page.goto(base+route,{waitUntil:'networkidle'});
    check(response.status()===200&&await page.locator('main.yn-service-guide').count()===1,width+' owned specialist page uses the repaired template '+route);
    check(await page.locator('.yn-guide').count()===1&&await page.locator('.yn-guide-panel').count()===5&&await page.locator('.yn-growth-page-content').count()===0,width+' owned details appear once without duplicating the stored fallback '+route);
    check(await page.locator('.yn-guide-media-grid,.yn-service-original,.repair-gallery,.repair-bottom-section').count()===0,width+' owned pages add no detached gallery or original-body wrapper '+route);
    const callbacks=JSON.parse(await page.locator('#repair-callback-counts').textContent());
    check(Object.keys(callbacks).length===0,width+' owned pages execute no original shortcode callbacks '+route);
    check(await page.locator('main h1').count()===1&&await page.locator('meta[name="description"]').count()===1,width+' owned title and native Rank Math description remain present '+route);
    check(!await page.evaluate(()=>document.documentElement.scrollWidth>document.documentElement.clientWidth+2),width+' owned specialist page has no horizontal overflow '+route);
   }
   await context.close();
  }
  check(errors.length===0,'No browser JavaScript errors: '+JSON.stringify(errors));
  console.log(wp('eval-file','/var/www/html/yoona-tests/gallery-repair.php','verify','--skip-themes').trim());
  console.log('Success: '+count+' focused gallery / bottom browser assertions passed.');
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
