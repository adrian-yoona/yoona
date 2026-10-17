#!/usr/bin/env node
'use strict';
// The installed plugin and WordPress hooks are real; all content below is synthetic.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const { execFileSync } = require('node:child_process');
const { chromium } = require('playwright');
(async () => {
  const [base, cli] = process.argv.slice(2);
  if (!/^http:\/\/127\.0\.0\.1:\d+$/.test(base || '') || !/^yoona-seo-test-.*-cli$/.test(cli || '')) throw new Error('Disposable local WordPress URL and CLI container required.');
  const wp = (...args) => execFileSync('docker', ['exec', '-u', '0', cli, 'wp', '--allow-root', '--path=/var/www/html', ...args], {encoding:'utf8'});
  const browser = await chromium.launch({executablePath:process.env.YOONA_TEST_CHROMIUM || '/usr/bin/chromium', headless:true, args:['--no-sandbox']});
  let assertions=0;
  const check=(value,label)=>{assert(value,label);assertions++;console.log('PASS Contact cleanup browser '+label);};
  const errors=[];
  try {
    for (const width of [320,1440]) {
      const context=await browser.newContext({viewport:{width,height:900}});
      const page=await context.newPage();
      page.on('pageerror',error=>errors.push(error.message));
      await page.route('**/*',async route=>{
        if(route.request().resourceType()==='image' && !route.request().url().startsWith(base)) await route.fulfill({status:200,contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="900" height="650"><rect width="900" height="650" fill="#15292d"/></svg>'});
        else await route.continue();
      });
      for(const slug of ['cleanup-new-page','cleanup-project','cleanup-edited','cleanup-global','cleanup-html']) {
        const response=await page.goto(base+'/'+slug+'/',{waitUntil:'networkidle'});
        check(response.status()===200 && await page.locator('#cleanup-retired-contact').count()===0,width+'/'+slug+': no retired contact block remains in the rendered DOM.');
        check(await page.locator('#cleanup-preserved-before').count()===1 && await page.locator('#cleanup-preserved-after').count()===1,width+'/'+slug+': both adjacent sections remain.');
        check(!await page.locator('main').evaluate(node=>/agencja-reklamowa-olsztyn(?:-1)?(?:-\d+x\d+)?\.(?:png|jpe?g|webp|avif)/i.test(node.innerHTML)),width+'/'+slug+': no retired knob image is downloaded from main content.');
      }
      for(const query of ['?cleanup_early_shortcode=1','?cleanup_rendered_transform=1','?cleanup_early_shortcode=1&cleanup_rendered_transform=1']) {
        await page.goto(base+'/cleanup-new-page/'+query,{waitUntil:'networkidle'});
        check(await page.locator('#cleanup-retired-contact').count()===0 && await page.locator('#cleanup-preserved-after').count()===1,width+query+': earlier and later content filters cannot restore the block.');
      }
      await page.goto(base+'/cleanup-unrelated/',{waitUntil:'networkidle'});
      check(await page.locator('#cleanup-legitimate-contact').count()===1 && /LEGITIMATE CONTACT CONTENT/.test(await page.locator('main').innerText()),width+': legitimate different contact section stays visible.');
      for(const query of ['?et_fb=1','?et_pb_preview=1','?preview=true']) {
        await page.goto(base+'/cleanup-new-page/'+query,{waitUntil:'networkidle'});
        check(await page.locator('#cleanup-retired-contact').count()===1,width+query+': builder and preview keep the editable source component.');
      }
      await context.close();
    }
    const context=await browser.newContext();const page=await context.newPage();
    try {
      wp('option','update','yoona_seo_design_enabled','0');
      await page.goto(base+'/cleanup-new-page/',{waitUntil:'networkidle'});
      check(await page.locator('#cleanup-retired-contact').count()===1,'The design off switch restores the full original component.');
    } finally {wp('option','delete','yoona_seo_design_enabled');}
    await page.goto(base+'/cleanup-new-page/',{waitUntil:'networkidle'});
    check(await page.locator('#cleanup-retired-contact').count()===0,'Enabling the design again removes the component without a content rewrite.');
    await context.close();
    check(errors.length===0,'No uncaught frontend JavaScript errors.');
    console.log('Success: '+assertions+' real Chromium semantic cleanup assertions passed.');
  } finally {await browser.close();}
})().catch(error=>{console.error(error.stack||error.message);process.exitCode=1;});
