#!/usr/bin/env node
'use strict';
// Real WordPress / Rank Math; public synthetic Divi callbacks and labelled SVGs.
// Google Maps is a local structural placeholder, not a live map service test.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {execFileSync} = require('node:child_process');
const {chromium} = require('playwright');

(async () => {
 const base=process.argv[2], cli=process.argv[3];
 assert(/^http:\/\/127\.0\.0\.1:\d+$/.test(base) && /^yoona-seo-test-gallery-.*-cli$/.test(cli),'Disposable loopback gallery site required.');
 const root=path.join(__dirname,'..'), output=process.env.YOONA_TEST_SCREENSHOTS || '/tmp/yoona-company-pages-screenshots';
 fs.mkdirSync(output,{recursive:true});
 const wp=(...args)=>execFileSync('docker',['exec','-u','0',cli,'wp','--allow-root','--path=/var/www/html',...args],{encoding:'utf8'});
 assert(wp('option','get','yoona_company_fixture_ready').trim()==='public-synthetic-2-v1','Prepare company-pages.php first.');
 const expected=JSON.parse(wp('option','get','yoona_company_expected_callbacks','--format=json'));
 const css=fs.readFileSync(path.join(root,'plugin/yoona-seo/assets/design.css'));
 const sha=body=>crypto.createHash('sha256').update(body).digest('hex');
 const routes=['/o-nas/','/kontakt/'], widths=[320,390,760,1024,1440,1920,2560];
 const report={cssSha256:sha(css),inventory:'Two public synthetic company sources; known hash overrides only in MU memory.',views:[]};
 let assertions=0; const check=(condition,label)=>{assert(condition,label);++assertions;};
 const errors=[];
 const photo='<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1100"><defs><linearGradient id="g"><stop stop-color="#747d70"/><stop offset="1" stop-color="#302e2b"/></linearGradient></defs><rect width="1600" height="1100" fill="url(#g)"/><path d="M0 800L1600 450V1100H0Z" fill="#191919"/><text x="50" y="950" fill="#ff7000" font-size="45" font-family="sans-serif">PUBLIC SYNTHETIC STRUCTURE PROBE</text></svg>';
 const browser=await chromium.launch({executablePath:process.env.YOONA_TEST_CHROMIUM || '/usr/bin/chromium',headless:true,args:['--no-sandbox']});
 const visit=async(page,route)=>{const response=await page.goto(base+route,{waitUntil:'load',timeout:45000});check(response.status()===200,'HTTP 200 '+route);await page.evaluate(()=>document.fonts.ready);};
 const measure=page=>page.evaluate(()=>{
  const rect=e=>e.getBoundingClientRect().toJSON();
  const main=document.querySelector('.yn-company-page'), header=main.querySelector('.yn-company-heading'), content=main.querySelector('.yn-company-content');
  const elements=[...main.querySelectorAll('h1,h2,h3,h4,h5,p,a,label,button,span,strong')].filter(e=>e.getBoundingClientRect().height>0 && !e.closest('.et_pb_toggle_content[hidden]'));
  const clipped=[];
  for(const element of elements){const b=rect(element),range=document.createRange();range.selectNodeContents(element);if(element.scrollWidth>element.clientWidth+2 || [...range.getClientRects()].some(r=>r.width>0 && (r.left<b.left-2 || r.right>b.right+2)))clipped.push({text:element.textContent.trim().slice(0,130),width:b.width,scroll:element.scrollWidth});}
  const rgba=value=>{const values=value.match(/[\d.]+/g);if(!values)return [0,0,0,0];return [Number(values[0]),Number(values[1]),Number(values[2]),values[3]===undefined?1:Number(values[3])];};
  const background=element=>{if(!element)return [255,255,255];const c=rgba(getComputedStyle(element).backgroundColor);if(c[3]===1)return c.slice(0,3);const p=background(element.parentElement);return c.slice(0,3).map((v,i)=>v*c[3]+p[i]*(1-c[3]));};
  const luminance=c=>{const v=c.map(x=>{x/=255;return x<=.04045?x/12.92:((x+.055)/1.055)**2.4;});return .2126*v[0]+.7152*v[1]+.0722*v[2];};
  const contrast=[];
  for(const element of elements){if(!element.textContent.trim())continue;const s=getComputedStyle(element),color=rgba(s.color),bg=background(element);if(s.backgroundImage!=='none')continue;const a=luminance(color),b=luminance(bg),ratio=(Math.max(a,b)+.05)/(Math.min(a,b)+.05),size=parseFloat(s.fontSize),large=size>=24 || (size>=18.66 && Number(s.fontWeight)>=700),minimum=large?3:4.5;if(ratio<minimum-.01)contrast.push({text:element.textContent.trim().slice(0,100),color:s.color,background:bg,size,weight:s.fontWeight,ratio,minimum});}
  const offices=[...main.querySelectorAll('.yn-company-office-row>.et_pb_column')];
  const paragraphs=[...main.querySelectorAll('.yn-company-intro p,.yn-company-service p,.yn-company-contact-code p,.yn-company-narrative p,.yn-company-values p')].filter(e=>e.getBoundingClientRect().height>0).map(e=>{const s=getComputedStyle(e);return {font:s.fontFamily,size:parseFloat(s.fontSize),align:s.textAlign,color:s.color};});
  return {overflow:document.documentElement.scrollWidth-document.documentElement.clientWidth,clipped,contrast,main:rect(main),header:rect(header),content:rect(content),gutters:[...main.querySelectorAll('.yn-company-heading .yn-container,.yn-company-content .et_pb_row')].map(rect),title:(()=>{const e=main.querySelector('.yn-company-title'),s=getComputedStyle(e);return {box:rect(e),tag:e.tagName,font:s.fontFamily,size:parseFloat(s.fontSize),color:s.color};})(),serviceHeadings:[...main.querySelectorAll('.yn-company-service h2')].map(e=>parseFloat(getComputedStyle(e).fontSize)),gridRows:[...main.querySelectorAll('.yn-company-service>.et_pb_row,.yn-company-faq>.et_pb_row,.yn-company-office-row')].map(e=>({before:getComputedStyle(e,'::before').display,after:getComputedStyle(e,'::after').display})),offices:offices.map(rect),paragraphs,map:main.querySelector('iframe')?rect(main.querySelector('iframe')):null,cta:main.querySelector('.yn-company-cta')?rect(main.querySelector('.yn-company-cta')):null,ctaTitle:main.querySelector('.yn-company-cta h2')?parseFloat(getComputedStyle(main.querySelector('.yn-company-cta h2')).fontSize):null,serviceImages:[...main.querySelectorAll('.yn-company-service img')].map(rect),touchTargets:[...main.querySelectorAll('.yn-company-contact-code a,.yn-company-map-code a,.yn-company-review-link a')].map(e=>({text:e.textContent.trim(),...rect(e)})),fontLoaded:[...document.fonts].some(f=>f.family.replace(/["']/g,'')==='YN Manrope' && f.status==='loaded')};
 });
 try {
  for(const route of routes){const r=await fetch(base+route);check(r.status===200,'Warmup '+route);await r.arrayBuffer();}
  console.log(wp('eval-file','/var/www/html/yoona-tests/company-pages.php','snapshot','--skip-themes').trim());
  for(const width of widths){
   const context=await browser.newContext({viewport:{width,height:950},deviceScaleFactor:1});
   await context.route('**/*',async route=>{
    const request=route.request(),url=new URL(request.url());
    if(request.resourceType()==='image')await route.fulfill({status:200,contentType:'image/svg+xml',body:photo});
    else if(url.hostname.includes('google') && request.resourceType()==='document')await route.fulfill({status:200,contentType:'text/html',body:'<!doctype html><html lang="pl"><body style="margin:0;background:#deded7;font-family:sans-serif;display:grid;place-items:center;height:100vh"><p>LOKALNY TEST RAMKI MAPY — NIE AKTYWNA MAPA GOOGLE</p></body></html>'});
    else await route.continue();
   });
   const page=await context.newPage();page.on('pageerror',error=>errors.push(error.message));
   for(const route of routes){
    await visit(page,route);
    check(await page.locator('main.yn-company-page').count()===1 && await page.locator('.yn-company-heading').count()===1 && await page.locator('main h1').count()===1,width+' one compact company header with a single H1 '+route);
    check(await page.locator('.company-old-hero,.company-old-contact-intro').count()===0,width+' known old empty hero / duplicate contact introduction removed '+route);
    const cssUrl=await page.locator('link[href*="/assets/design.css"]').first().getAttribute('href'),response=await page.request.get(cssUrl);
    check(response.status()===200 && sha(await response.body())===sha(css),width+' served stylesheet matches reviewed source bytes '+route);
    const value=await measure(page);
    await page.screenshot({path:path.join(output,width+'-'+route.replaceAll('/','')+'-initial.png'),fullPage:true});
    check(value.overflow<=2 && value.clipped.length===0,width+' no document or text overflow '+route+' '+JSON.stringify(value.clipped));
    check(value.contrast.length===0,width+' solid-background text meets calculated normal/large text contrast '+route+' '+JSON.stringify(value.contrast));
    check(value.fontLoaded && value.paragraphs.length>0 && value.paragraphs.every(p=>p.font.includes('YN Manrope') && p.size>=14 && p.size<=17 && p.align!=='justify'),width+' normalized readable body typography overrides legacy fonts/colors/justification '+route+' '+JSON.stringify(value.paragraphs));
    check(value.title.size<=(width<=640?30:40) && value.title.size>=24 && value.header.height<=360,width+' compact heading dimensions '+route+' '+JSON.stringify({title:value.title,header:value.header}));
    check(value.main.width<=width+1 && value.gutters.length>0 && value.gutters.every(b=>b.x>=12 && b.right<=width-12),width+' responsive gutters retain breathing room '+route);
    check(value.gridRows.length>0 && value.gridRows.every(r=>r.before==='none' && r.after==='none'),width+' real Divi clearfix pseudo-elements do not create empty grid cells '+route);
    const callbacks=JSON.parse(await page.locator('#company-callback-counts').textContent());
    check(JSON.stringify(Object.fromEntries(Object.entries(callbacks).sort()))===JSON.stringify(expected[route]),width+' every original shortcode callback executes exactly once '+route+' '+JSON.stringify(callbacks));
    check((await page.title()).startsWith('COMPANY NATIVE ') && await page.locator('meta[name="description"]').count()===1,width+' native Rank Math metadata preserved '+route);
    check(await page.locator('main .et_pb_social_media_follow_network a').count()===2,width+' original social links preserved once '+route);
    if(route==='/o-nas/'){
     check(await page.locator('.yn-company-service>.et_pb_row>.et_pb_column .et_pb_image img').count()===7,width+' seven original photos retain Divi section / row / column ancestry');
     check(await page.locator('.yn-company-faq .et_pb_toggle').count()===8,width+' all eight original FAQs preserved');
     check(value.serviceHeadings.length===7 && value.serviceHeadings.every(s=>s<=(width<=640?23:30)),width+' service headings override the old !important content heading size '+JSON.stringify(value.serviceHeadings));
     check(value.serviceImages.every(b=>b.width>0 && b.height>0 && b.height<=390),width+' all service photos visible with compact aspect ratios');
     const first=page.locator('.yn-company-faq .et_pb_toggle').first();
     await first.locator('.et_pb_toggle_title').click();
     check(await first.locator('.et_pb_toggle_content').isVisible() && await first.locator('.et_pb_toggle_title').getAttribute('aria-expanded')==='true',width+' original FAQ remains interactive');
     await first.locator('.et_pb_toggle_title').click();
    }else{
     check(await page.locator('.yn-company-contact-main>.yn-company-office-row>.et_pb_column>.yn-company-contact-code').count()===2,width+' exactly two original office code modules retain complete parents');
     check(value.offices.length===2 && (width<=640 ? value.offices[1].y>=value.offices[0].bottom-1 : Math.abs(value.offices[0].y-value.offices[1].y)<=1),width+' office columns switch cleanly to stacked cards at mobile width');
     check(await page.locator('.yn-company-contact-code a[href="tel:+48791144100"]').count()===1 && await page.locator('.yn-company-contact-code a[href="tel:+48896422399"]').count()===1 && await page.locator('.yn-company-contact-code a[href="mailto:wyceny@yoona.pl"]').count()===1 && await page.locator('.yn-company-contact-code a[href="mailto:biuro@yoona.pl"]').count()===1,width+' all original phone and email links preserved without duplicate contact cards');
     check(value.touchTargets.length===6 && value.touchTargets.every(b=>b.height>=44 && b.width>=44),width+' phone, email, directions and reviews links have at least 44px touch areas '+JSON.stringify(value.touchTargets));
     check(await page.locator('main iframe[title="Mapa dojazdu do YOONA"]').count()===1 && value.map.height<=(width<=640?240:360) && value.map.width>0,width+' original map remains visible in a compact iframe');
     check(await page.locator('.yn-company-reviews .ti-review-item').count()===3 && callbacks.trustindex===1,width+' original review shortcode output executes once');
     check(value.ctaTitle<=40 && value.cta.height<=320,width+' former 9em call to action is compact '+JSON.stringify({height:value.cta.height,size:value.ctaTitle}));
     const form=page.locator('.company-preserved-form');
     check(await form.count()===1 && await form.locator('input').count()===2 && await form.locator('textarea').count()===1,width+' later native form retains all its fields');
     check(await form.evaluate(f=>!f.checkValidity()),width+' native empty-form validation retained');
     await form.locator('[name="company-name"]').fill('Test publiczny');await form.locator('[name="company-email"]').fill('test@example.invalid');await form.locator('[name="company-message"]').fill('Próba zachowania formularza.');
     check(await form.evaluate(f=>f.checkValidity()),width+' complete valid form passes native validation');
     await form.locator('button').click();check(await form.locator('[role="status"]').textContent()==='TEST: formularz działa',width+' original local form handler remains functional');
    }
    if(width<=760){const menu=page.locator('.yn-mobile-menu');await menu.locator('summary').click();check(await menu.locator('nav').isVisible(),width+' mobile navigation opens '+route);await menu.locator('summary').click();}
    report.views.push({width,route,...value});
    await page.screenshot({path:path.join(output,width+'-'+route.replaceAll('/','')+'-complete.png'),fullPage:true});
    await page.locator('.yn-company-heading').screenshot({path:path.join(output,width+'-'+route.replaceAll('/','')+'-heading.png')});
    console.log('PASS Company '+width+' '+route);
   }
   await context.close();
  }
  check(errors.length===0,'No uncaught browser JavaScript errors: '+JSON.stringify(errors));
  console.log(wp('eval-file','/var/www/html/yoona-tests/company-pages.php','verify','--skip-themes').trim());
  fs.writeFileSync(path.join(output,'geometry.json'),JSON.stringify(report,null,2)+'\n');
  console.log('Current CSS SHA-256: '+sha(css));
  console.log('Success: '+assertions+' responsive company-page / source-preservation / native-form assertions passed.');
  console.log('Screenshots: '+output+'; synthetic Divi and photos. Calculated contrast covers solid CSS backgrounds, not photographic or third-party widget contrast. Maps use a local iframe placeholder.');
 }finally{await browser.close();}
})().catch(error=>{console.error(error.stack||error.message);process.exitCode=1;});
