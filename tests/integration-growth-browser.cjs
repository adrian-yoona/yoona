#!/usr/bin/env node
'use strict';
// Real WordPress/Rank Math templates and CSS. Remote media are explicitly labelled
// local probes because production image retrieval is unavailable in this environment.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {execFileSync} = require('node:child_process');
const {chromium} = require('playwright');
(async () => {
  const base = process.argv[2]; const cli = process.argv[3];
  if (!/^http:\/\/127\.0\.0\.1:\d+$/.test(base) || !/^yoona-seo-test-.*-cli$/.test(cli)) throw new Error('Use a disposable local WordPress URL and its test CLI container.');
  const guides = JSON.parse(fs.readFileSync(path.join(__dirname, '../plugin/yoona-seo/content/service-guides.json')));
  const projects = JSON.parse(fs.readFileSync(path.join(__dirname, '../plugin/yoona-seo/content/project-guides.json')));
  const design = JSON.parse(fs.readFileSync(path.join(__dirname, '../plugin/yoona-seo/assets/design-data.json')));
  const cssHash = crypto.createHash('sha256').update(fs.readFileSync(path.join(__dirname, '../plugin/yoona-seo/assets/design.css'))).digest('hex');
  const output = process.env.YOONA_TEST_SCREENSHOTS || '/tmp/yoona-growth-screenshots'; fs.mkdirSync(output, {recursive:true});
  const wp = (...args) => execFileSync('docker', ['exec','-u','0',cli,'wp','--allow-root','--path=/var/www/html',...args], {encoding:'utf8'});
  let count = 0; const errors = [];
  const check = (condition, message) => {assert(condition, message); ++count; console.log('PASS Growth browser ' + message);};
  const browser = await chromium.launch({executablePath:process.env.YOONA_TEST_CHROMIUM || '/usr/bin/chromium',headless:true,args:['--no-sandbox']});
  const fixture = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="850"><rect width="1200" height="850" fill="#24211e"/><path d="M50 650L800 180L1150 650Z" fill="#45413d"/><text x="70" y="720" font-size="48" fill="#ff7000">LOCAL MEDIA PROBE — NOT A PRODUCTION PHOTO</text></svg>';
  const wire = async context => {
    context.on('page', page => page.on('pageerror', error => errors.push(error.message)));
    await context.route('**/*', async route => {
      const request = route.request();
      if (request.resourceType() === 'image' && (!request.url().startsWith(base) || new URL(request.url()).pathname.includes('/wp-content/uploads/'))) await route.fulfill({status:200,contentType:'image/svg+xml',body:fixture});
      else await route.continue();
    });
  };
  const fit = async (page, label) => {
    const bad = await page.evaluate(() => ({overflow:document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
      headings:[...document.querySelectorAll('h1,main h2,main h3,.yn-contact-location h3,.yn-contact-phone,.yn-category-link')].flatMap(element => {
        const box=element.getBoundingClientRect(); if(!box.width || !box.height) return [];
        const range=document.createRange(); range.selectNodeContents(element);
        return [...range.getClientRects()].some(rect => rect.width > 0 && (rect.left < box.left - 2 || rect.right > box.right + 2)) ? [element.textContent.trim()] : [];
      })}));
    check(!bad.overflow && bad.headings.length === 0, label + ': no horizontal page overflow or clipped heading/contact text (' + JSON.stringify(bad) + ').');
  };
  const cities = async (page, selector, label) => {
    const values=await page.locator(selector).evaluateAll(elements=>elements.map(element=>({text:element.innerText,box:element.getBoundingClientRect().toJSON(),font:getComputedStyle(element).fontSize})));
    check(values.length === 2 && values.some(value=>/Olsztyn/i.test(value.text)) && values.some(value=>/Ostróda/i.test(value.text)),label+': both real city contacts are present.');
    check(Math.abs(values[0].box.width-values[1].box.width)<=1 && Math.abs(values[0].box.y-values[1].box.y)<=1,label+': both city contacts occupy equal side-by-side columns.');
  };
  try {
    for(const [name,width] of (process.env.YOONA_GROWTH_TEST_MODE==='security'?[]:[['desktop',1440],['mobile',390],['mobile-small',320]])) {
      const context=await browser.newContext({viewport:{width,height:950},deviceScaleFactor:1}); await wire(context);
      const page=await context.newPage();
      await page.goto(base+'/',{waitUntil:'networkidle'});
      check(await page.locator('h1').count()===1,name+': the homepage retains one H1.');
      const stylesheet=await page.locator('link[href*="/assets/design.css"]').first().getAttribute('href');
      const served=await page.request.get(stylesheet);
      check(served.status()===200 && crypto.createHash('sha256').update(await served.body()).digest('hex')===cssHash,name+': WordPress serves the exact current stylesheet.');
      const homeLinks=await page.locator('.yn-service-card,.yn-category-link').evaluateAll(elements=>elements.map(element=>element.pathname));
      check(homeLinks.length===47 && new Set(homeLinks).size===47 && Object.keys(guides).every(route=>homeLinks.includes(route)),name+': all 47 public service categories are linked once on the homepage.');
      check(await page.locator('.yn-service-card').count()===8 && await page.locator('.yn-category-link').count()===39,name+': eight photographic cards lead to the complete compact category list.');
      check((await page.locator('.yn-desktop-nav a[href$="/pelna-oferta/"]').textContent()).trim()==='Pełna oferta',name+': desktop navigation labels the full offer clearly.');
      await cities(page,'.yn-location-strip-inner>a',name+' header');
      await cities(page,'.yn-contact .yn-location-grid>.yn-contact-location',name+' footer');
      const equalPhones=await page.locator('.yn-contact .yn-contact-phone').evaluateAll(elements=>elements.map(element=>getComputedStyle(element).fontSize));
      check(equalPhones.length===2 && equalPhones[0]===equalPhones[1],name+': both footer telephone numbers receive equal typography.');
      await fit(page,name+' homepage');
      if(width<600) {
        await page.locator('.yn-mobile-menu summary').click();
        check(await page.locator('.yn-mobile-menu a[href$="/pelna-oferta/"]').isVisible(),name+': the full offer is directly available in the mobile menu.');
      }
      await page.screenshot({path:path.join(output,name+'-home.png'),fullPage:true});
      await page.goto(base+'/kontakt/',{waitUntil:'networkidle'});
      check(await page.locator('h1').count()===1,name+': the contact page retains one H1.');
      await cities(page,'.yn-contact-page-locations>.yn-contact-location',name+' contact page');
      await fit(page,name+' contact page');
      await page.screenshot({path:path.join(output,name+'-contact.png'),fullPage:true});
      await page.goto(base+'/pelna-oferta/',{waitUntil:'networkidle'});
      const catalogueLinks=await page.locator('.yn-catalog-card>a').evaluateAll(elements=>elements.map(element=>element.pathname));
      check(catalogueLinks.length===47 && new Set(catalogueLinks).size===47 && Object.keys(guides).every(route=>catalogueLinks.includes(route)),name+': the full-offer page exposes all 47 distinct photographic service cards.');
      const catalogueColumns=await page.locator('.yn-catalog-grid').evaluate(element=>getComputedStyle(element).gridTemplateColumns.split(' ').length);
      check(catalogueColumns===(width===1440?4:1) && await page.locator('h1').count()===1,name+': the full-offer page keeps its responsive card columns and single H1.');
      await fit(page,name+' full-offer page');
      const servicePaths=width===1440?Object.keys(guides):['/frezowanie-cnc/','/markizy-z-logo/','/markizy-wolnostojace-duo/','/frezowanie-elementow-reklamowych/','/oklejanie-przyczep-gastronomicznych/'];
      for(const route of servicePaths) {
        if(!guides[route]) continue;
        const response=await page.goto(base+route,{waitUntil:'networkidle'}); const guide=guides[route];
        check(response.status()===200 && await page.locator('main.yn-service-guide').count()===1,name+': a real service template renders '+route);
        check(await page.locator('h1').count()===1 && await page.locator('meta[name="description"]').count()===1 && (await page.title()).startsWith('GROWTH_NATIVE ')===!guide.image_source_path,name+': one H1 and one native Rank Math description remain on '+route);
        check(await page.locator('.yn-guide-panel').count()===5 && await page.locator('.yn-guide-faq details').count()===guide.faq.length,name+': material, application, process, pricing, quote and FAQ sections are complete for '+route);
        const lastPanel=await page.locator('.yn-guide-grid').evaluate(grid=>{const list=getComputedStyle(grid.lastElementChild.querySelector('ul'));return {grid:grid.getBoundingClientRect().width,last:grid.lastElementChild.getBoundingClientRect().width,columns:list.display==='grid'?list.gridTemplateColumns.split(' ').length:1};});
        check(Math.abs(lastPanel.grid-lastPanel.last)<=1 && lastPanel.columns===(width>=901?3:1),name+': the final quote panel fills the row without an empty half-column and its list remains responsive for '+route);
        check((await page.locator('.yn-guide-intro>p:last-child').innerText()).trim()===guide.overview,name+': the full reviewed service overview is visible on '+route);
        const expected=design.offer_heroes[guide.image_source_path || route].image;
        const image=await page.locator('.yn-offer-image').getAttribute('src');
        check(new URL(image).pathname===new URL(expected).pathname,name+': the service uses its actual existing source photograph on '+route);
        const main=await page.locator('main').innerText();
        check(!main.includes('GROWTH_OLD_COPY_SENTINEL') && await page.locator('main img[src*="agencja-reklamowa-olsztyn"]').count()===0,name+': retired contact imagery and replaced old copy are absent on '+route);
        check(await page.locator('.growth-original-form').count()===(guide.image_source_path?0:1) && await page.locator('.growth-original-image').count()===(guide.image_source_path?0:1),name+': distinct original media/forms survive while owned child pages avoid duplicate legacy media on '+route);
        await fit(page,name+' '+route);
        if(route==='/markizy-wolnostojace-duo/' || route==='/frezowanie-elementow-reklamowych/') await page.screenshot({path:path.join(output,name+'-'+route.replaceAll('/','')+'.png'),fullPage:true});
      }
      for(const [slug,source] of [['growth-project-color','/zmiana-koloru-auta-ostroda/'],['growth-project-pylons','/pylony-ergo-arena/'],['growth-project-awning','/markiza-irjados/']]) {
        await page.goto(base+'/'+slug+'/',{waitUntil:'networkidle'}); const guide=projects[source];
        check(await page.locator('h1').count()===1 && await page.locator('.yn-project-guide').count()===1,name+': the real project component appears once beneath its single source title for '+slug);
        check((await page.locator('.yn-project-guide .yn-guide-intro').innerText()).trim()===guide.summary,name+': the project summary exactly matches the reviewed public facts for '+slug);
        const panelTitles=await page.locator('.yn-project-guide .yn-guide-panel h2').allTextContents();
        check(panelTitles.includes('Miejsce realizacji')===Boolean(guide.location) && panelTitles.includes('Materiały')===Boolean(guide.materials?.length),name+': optional city/material fields appear only when source facts exist for '+slug);
        check(await page.locator('.yn-project-guide a[href$="'+guide.service_path+'"]').count()===1 && (await page.locator('main').innerText()).includes('GROWTH_PRESERVED_PROJECT_SOURCE'),name+': the project links to its actual service and the synthetic unchanged source remains available for '+slug);
        await fit(page,name+' '+slug);
      }
      await context.close();
    }
    const context=await browser.newContext(); await wire(context); const page=await context.newPage();
    for(const query of ['?et_fb=1','?et_pb_preview=1','?preview=true']) {
      await page.goto(base+'/banery/'+query,{waitUntil:'networkidle'});
      check(await page.locator('.yn-service-guide').count()===0,'A builder or preview route retains its original theme: '+query);
    }
    await page.goto(base+'/growth-unlisted/',{waitUntil:'networkidle'});
    check((await page.locator('main').innerText()).includes('GROWTH_PRESERVED_UNLISTED_SOURCE') && await page.locator('.yn-service-guide').count()===0,'An unreviewed page preserves its original source instead of receiving a service guide.');
    await page.goto(base+'/kontakt/',{waitUntil:'networkidle'});
    await page.evaluate(()=>{window.dataLayer=[];window.yoonaGtagCalls=[];window.gtag=(...args)=>window.yoonaGtagCalls.push(args);document.addEventListener('click',event=>event.preventDefault(),true);});
    await page.locator('.yn-location-strip-inner>a:first-child').click();
    check(await page.evaluate(()=>window.yoonaGtagCalls.length===0 && window.dataLayer.length===0),'Unknown analytics consent sends and queues no contact event.');
    const consent=async analytics=>context.addCookies([{name:'cookieyes-consent',value:encodeURIComponent('consent:yes,analytics:'+analytics+',advertisement:no'),url:base}]);
    await consent('yes');
    for(const selector of ['.yn-location-strip-inner>a:first-child','.yn-location-strip-inner>a:last-child','.yn-contact-page-locations .yn-contact-location:first-child a[href^="mailto:"]','.yn-contact-page-locations .yn-contact-location:last-child a[href^="mailto:"]','.yn-header-cta']) await page.locator(selector).click();
    const tracking=await page.evaluate(()=>({events:window.yoonaGtagCalls.map(args=>({event:args[1],...args[2]})),commands:window.yoonaGtagCalls.map(args=>args[0]),queued:window.dataLayer.length}));
    check(tracking.events.length===5 && tracking.commands.every(command=>command==='event') && tracking.events.filter(event=>event.contact_method==='phone').length===2 && tracking.events.filter(event=>event.contact_method==='email').length===2 && tracking.events.filter(event=>event.event==='yoona_quote_click').length===1,'With CookieYes analytics consent, existing Site Kit gtag receives both city telephone/email clicks and one quote-page click.');
    const trackingKeys=['contact_location','contact_method','contact_placement','event','page_path'].sort().join(',');
    check(tracking.queued===0 && tracking.events.every(event=>Object.keys(event).sort().join(',')===trackingKeys && event.page_path==='/kontakt/' && !/791|642|@|mailto:|tel:/.test(JSON.stringify(event))),'Site Kit events contain no telephone/email values or query parameters and are not duplicated into the GTM custom-event queue.');
    check(tracking.events.slice(0,4).filter(event=>/Olsztyn/i.test(event.contact_location)).length===2 && tracking.events.slice(0,4).filter(event=>/Ostróda/i.test(event.contact_location)).length===2,'Both cities receive equal attribution in contact-intent measurement.');
    await consent('no'); await page.locator('.yn-location-strip-inner>a:first-child').click();
    check(await page.evaluate(()=>window.yoonaGtagCalls.length===5 && window.dataLayer.length===0),'CookieYes analytics refusal immediately stops event delivery.');
    await consent('yes'); await page.evaluate(()=>document.dispatchEvent(new CustomEvent('cookieyes_consent_update',{detail:{accepted:[]}})));
    await page.locator('.yn-location-strip-inner>a:first-child').click();
    check(await page.evaluate(()=>window.yoonaGtagCalls.length===5),'Revoking analytics through the CookieYes consent event overrides a stale positive cookie.');
    await page.evaluate(()=>document.dispatchEvent(new CustomEvent('cookieyes_consent_update',{detail:{accepted:['analytics']}})));
    await page.addScriptTag({content:fs.readFileSync(path.join(__dirname,'../plugin/yoona-seo/assets/measurement.js'),'utf8')});
    await page.locator('.yn-location-strip-inner>a:first-child').click();
    check(await page.evaluate(()=>window.yoonaGtagCalls.length===6 && window.dataLayer.length===0),'A repeated script initialization still sends exactly one granted Site Kit event.');
    await page.evaluate(()=>{delete window.gtag;window.dataLayer=[];}); await page.locator('.yn-location-strip-inner>a:first-child').click();
    check(await page.evaluate(()=>window.dataLayer.length===1 && window.dataLayer[0].event==='yoona_contact_click' && window.yoonaGtagCalls.length===6),'An existing GTM dataLayer receives one fallback custom event only when gtag is absent.');
    const anonymous=await page.request.post(base+'/wp-admin/admin-post.php',{form:{action:'yoona_seo_growth_install',_wpnonce:'invalid'},maxRedirects:0});
    check(anonymous.status()===400,'WordPress refuses anonymous service creation before entering the authenticated publishing handler.');
    await page.goto(base+'/wp-login.php'); await page.locator('#user_login').fill('yoona_test_admin'); await page.locator('#user_pass').fill('local-admin-test-only');
    await Promise.all([page.waitForURL('**/wp-admin/**'),page.locator('#wp-submit').click()]);
    for(const action of ['yoona_seo_growth_install','yoona_seo_growth_rollback']) {
      const badNonce=await page.request.post(base+'/wp-admin/admin-post.php',{form:{action,_wpnonce:'invalid'},maxRedirects:0});
      check(badNonce.status()===403,'An authenticated administrator still needs the correct nonce for '+action+'.');
      const get=await page.request.get(base+'/wp-admin/admin-post.php?action='+action,{maxRedirects:0});
      check(get.status()===405,'A state-changing GET is refused for '+action+'.');
    }
    await page.goto(base+'/wp-admin/tools.php?page=yoona-seo',{waitUntil:'networkidle'});
    const form=page.locator('form:has(input[name="action"][value="yoona_seo_growth_install"])');
    check(await form.count()===1 && await form.locator('input[name="_wpnonce"]').count()===1,'The administrator sees a concrete six-page publication form with a valid nonce.');
    const fields=await form.evaluate(element=>Object.fromEntries(new FormData(element)));
    const repeat=await page.request.post(base+'/wp-admin/admin-post.php',{form:fields,maxRedirects:0});
    check(repeat.status()===302 && /tools.php/.test(repeat.headers().location),'The real authenticated POST safely reports a repeated install through the normal admin redirect.');
    const reader=await browser.newContext();const readerPage=await reader.newPage();
    await readerPage.goto(base+'/wp-login.php');await readerPage.locator('#user_login').fill('yoona_design_reader');await readerPage.locator('#user_pass').fill('local-reader-test-only');
    await Promise.all([readerPage.waitForURL('**/wp-admin/**'),readerPage.locator('#wp-submit').click()]);
    for(const action of ['yoona_seo_growth_install','yoona_seo_growth_rollback']) {
      const denied=await readerPage.request.post(base+'/wp-admin/admin-post.php',{form:{action,_wpnonce:'invalid'},maxRedirects:0});
      check(denied.status()===403,'A signed-in subscriber is denied the actual publishing endpoint '+action+'.');
    }
    await reader.close();
    try {
      wp('option','update','yoona_seo_design_enabled','0');
      await page.goto(base+'/markizy-wolnostojace-duo/',{waitUntil:'networkidle'});
      check(await page.locator('.yn-service-guide').count()===0 && (await page.locator('body').innerText()).includes(guides['/markizy-wolnostojace-duo/'].overview),'The design off switch retains complete stored specialist copy in the original theme.');
    } finally {wp('option','delete','yoona_seo_design_enabled');}
    await context.close();
    check(errors.length===0,'There are no uncaught frontend JavaScript errors.');
    console.log('Tested design.css SHA-256: '+cssHash);
    console.log('Success: '+count+' real Chromium growth assertions passed.');
    console.log('Screenshots: '+output+' (media probes are explicitly labelled, not production photographs).');
  } finally {await browser.close();}
})().catch(error=>{console.error(error.stack||error.message);process.exitCode=1;});
