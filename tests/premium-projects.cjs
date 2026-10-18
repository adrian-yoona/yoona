#!/usr/bin/env node
'use strict';
// Real WordPress/Rank Math with public synthetic native/Divi source layouts.
// All photo requests use a labelled SVG; licensed Divi/real photos unavailable.
const assert=require('node:assert/strict'), fs=require('node:fs'), path=require('node:path'), crypto=require('node:crypto');
const {chromium}=require('playwright');
(async()=>{
 const base=process.argv[2];assert(/^http:\/\/127\.0\.0\.1:\d+$/.test(base),'Disposable loopback WordPress URL required.');
 const out=process.env.YOONA_TEST_SCREENSHOTS||'/tmp/yoona-premium-projects35-screenshots';fs.mkdirSync(out,{recursive:true});
 const sha=b=>crypto.createHash('sha256').update(b).digest('hex');
 const css=fs.readFileSync(path.join(__dirname,'../plugin/yoona-seo/assets/projects.css'));
 const routes=[{path:'/yoona35-project-detail-single/',photos:1,native:false},{path:'/yoona35-project-detail-gallery3/',photos:3,native:true}];
 const widths=[320,390,620,760,1024,1440,1920,2560],report={inventory:'Two public synthetic unknown/manual posts, complete native/Divi source. No source hash overrides.',cssSha256:sha(css),views:[]};
 let assertions=0;const check=(condition,label)=>{assert(condition,label);++assertions;};const errors=[];
 const svg='<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1100"><defs><linearGradient id="g"><stop stop-color="#667460"/><stop offset="1" stop-color="#b4ac9e"/></linearGradient></defs><rect width="1600" height="1100" fill="url(#g)"/><path d="M0 680L1600 420V1100H0Z" fill="#24251f"/><text x="50" y="960" fill="#ff9000" font-size="44" font-family="sans-serif">PUBLIC SYNTHETIC PROJECT — NOT A CUSTOMER PHOTO</text></svg>';
 const browser=await chromium.launch({executablePath:process.env.YOONA_TEST_CHROMIUM||'/usr/bin/chromium',headless:true,args:['--no-sandbox']});
 try{
 for(const width of widths){
  const context=await browser.newContext({viewport:{width,height:980},deviceScaleFactor:1});
  await context.route('**/*',route=>route.request().resourceType()==='image'?route.fulfill({status:200,contentType:'image/svg+xml',body:svg}):route.continue());
  const page=await context.newPage();page.on('pageerror',error=>errors.push(error.message));
  for(const fixture of routes){
   const response=await page.goto(base+fixture.path,{waitUntil:'load',timeout:45000});check(response.status()===200,'HTTP 200 '+fixture.path);
   await page.evaluate(()=>document.fonts.ready);
   const served=await context.request.get(base+'/wp-content/plugins/yoona-seo/assets/projects.css');check(sha(await served.body())===sha(css),'Browser serves exact current projects.css');
   const v=await page.evaluate(()=>{
    const article=document.querySelector('.yn-project-detail'), original=article.querySelector('.yn-project-detail-gallery'), rect=e=>e.getBoundingClientRect().toJSON();
    const clipped=[];for(const e of article.querySelectorAll('h1,h2,h3,h4,h5,p,figcaption,a,summary,button')){if(e.closest('[hidden]')||!e.getBoundingClientRect().height)continue;const r=rect(e);const range=document.createRange();range.selectNodeContents(e);if(e.scrollWidth>e.clientWidth+2||[...range.getClientRects()].some(x=>x.width>0&&(x.left<r.left-2||x.right>r.right+2)))clipped.push({tag:e.tagName,text:e.textContent.trim().slice(0,100),width:r.width,scroll:e.scrollWidth});}
    const gallery=original.querySelector('.wp-block-gallery');
    return{viewport:innerWidth,documentWidth:document.documentElement.scrollWidth,article:rect(article),source:rect(original),heading:rect(article.querySelector('.yn-project-heading')),title:rect(article.querySelector('.yn-project-title')),h1:article.querySelectorAll('h1').length,photoCount:original.querySelectorAll('img').length,photos:[...original.querySelectorAll('img')].map(e=>({box:rect(e),alt:e.alt,fit:getComputedStyle(e).objectFit,ratio:getComputedStyle(e).aspectRatio})),gallery:gallery?{columns:getComputedStyle(gallery).gridTemplateColumns.split(' ').length,box:rect(gallery)}:null,clipped,curated:article.querySelectorAll('.yn-project-summary,.yn-project-planning').length,captionCount:original.querySelectorAll('figcaption').length,sourceLinks:[...original.querySelectorAll('a')].map(e=>e.getAttribute('href')),cta:[...article.querySelectorAll('.yn-project-next a')].map(e=>({box:rect(e),text:e.textContent.trim()})),sourceCopy:original.textContent};
   });
   report.views.push({path:fixture.path,width,...v});
   check(v.documentWidth<=width+1,'No horizontal page overflow '+width+' '+fixture.path);
   check(v.clipped.length===0,'No clipped visible Polish text '+width+' '+JSON.stringify(v.clipped));
   check(v.photoCount===fixture.photos,'Every source photo remains once '+width);
   check(v.h1===1,'Exactly one visible source or template H1 '+width);
   check(v.curated===0,'Unknown/manual source receives no stale factual guide '+width);
   check(v.photos.every(p=>p.alt&&p.box.width>100&&p.box.height>70),'All source photographs remain visible with original alt text');
   check(v.photos.every(p=>p.box.left>=v.source.left-1&&p.box.right<=v.source.right+1),'Photos remain inside premium margins');
   check(v.cta.every(c=>c.box.height>=44),'Next action links meet 44px target height');
   check(v.heading.height<280,'Project header remains compact '+width);
   check(v.sourceLinks.includes('/kontakt/'),'Original non-image destination link preserved');
   if(fixture.native){const columns=width<=620?1:width<=1100?2:3;check(v.gallery.columns===columns,'Responsive native gallery has '+columns+' columns at '+width);check(v.captionCount===3,'Every original native caption preserved');check(v.sourceCopy.includes('PUBLIC ORIGINAL NATIVE ANSWER'),'Original native accordion content preserved');}
   else{check(v.sourceCopy.includes('PUBLIC ORIGINAL ANSWER')&&v.sourceCopy.includes('PUBLIC SINGLE SOURCE COPY'),'Original Divi copy and FAQ remain together');if(width<=760)check(v.photos[0].fit==='contain','Single photo is not cropped on phone');}
   if([320,390,1440].includes(width))await page.screenshot({path:path.join(out,width+'-'+(fixture.native?'gallery3':'single')+'.png'),fullPage:true});
   console.log('PASS Project detail '+width+' '+fixture.path);
  }
  await context.close();
 }
 check(errors.length===0,'No project page JavaScript errors: '+JSON.stringify(errors));
 fs.writeFileSync(path.join(out,'geometry.json'),JSON.stringify(report,null,2));
 console.log('PASS '+assertions+' browser project assertions across '+report.views.length+' responsive views. Labelled public synthetic photographs; licensed Divi unavailable.');
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1);});
