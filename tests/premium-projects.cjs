#!/usr/bin/env node
'use strict';
// Real WordPress/Rank Math with public synthetic native/Divi source layouts.
// All photo requests use a labelled SVG; licensed Divi/real photos unavailable.
const assert=require('node:assert/strict'), fs=require('node:fs'), path=require('node:path'), crypto=require('node:crypto');
const {chromium}=require('playwright');
(async()=>{
 const base=process.argv[2];assert(/^http:\/\/127\.0\.0\.1:\d+$/.test(base),'Disposable loopback WordPress URL required.');
 const out=process.env.YOONA_TEST_SCREENSHOTS||'/tmp/yoona-premium-projects351-screenshots';fs.mkdirSync(out,{recursive:true});
 const sha=b=>crypto.createHash('sha256').update(b).digest('hex');
 const css=fs.readFileSync(path.join(__dirname,'../plugin/yoona-seo/assets/projects.css'));
 const sources=[{path:'/yoona35-project-detail-single/',photos:1,native:false},{path:'/yoona35-project-detail-gallery3/',photos:3,native:true}];
 const routes=sources.flatMap(source=>[source,{...source,path:source.path+'?yoona_project_fixture=known',known:true}]);
 const widths=[320,390,620,760,1024,1440,1920,2560],report={inventory:'Two public synthetic native/Divi posts, each with unknown/manual and HTTP-only known-guide views. Known-guide fixture uses public synthetic content and in-memory hashes, never customer source. Legacy grid/font probes are inserted only in the browser after checking the original page.',cssSha256:sha(css),views:[]};
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
    const typography=e=>({font:getComputedStyle(e).fontFamily,weight:getComputedStyle(e).fontWeight,size:parseFloat(getComputedStyle(e).fontSize),line:getComputedStyle(e).lineHeight});
    return{viewport:innerWidth,documentWidth:document.documentElement.scrollWidth,article:rect(article),source:rect(original),heading:rect(article.querySelector('.yn-project-heading')),title:rect(article.querySelector('.yn-project-title')),titleType:typography(article.querySelector('.yn-project-title')),sourceHeadingTypes:[...original.querySelectorAll('h1,h2,h3,h4')].map(typography),sourceParagraphTypes:[...original.querySelectorAll('p')].map(typography),h1:article.querySelectorAll('h1').length,photoCount:original.querySelectorAll('img').length,photos:[...original.querySelectorAll('img')].map(e=>({box:rect(e),alt:e.alt,fit:getComputedStyle(e).objectFit,ratio:getComputedStyle(e).aspectRatio})),gallery:gallery?{columns:getComputedStyle(gallery).gridTemplateColumns.split(' ').length,box:rect(gallery)}:null,clipped,curated:article.querySelectorAll('.yn-project-summary,.yn-project-planning').length,captionCount:original.querySelectorAll('figcaption').length,sourceLinks:[...original.querySelectorAll('a')].map(e=>e.getAttribute('href')),cta:[...article.querySelectorAll('.yn-project-next a')].map(e=>({box:rect(e),text:e.textContent.trim()})),sourceCopy:original.textContent,articleCopy:article.textContent};
   });
   report.views.push({path:fixture.path,width,...v});
   check(v.documentWidth<=width+1,'No horizontal page overflow '+width+' '+fixture.path);
   check(v.clipped.length===0,'No clipped visible Polish text '+width+' '+JSON.stringify(v.clipped));
   check(v.photoCount===fixture.photos,'Every source photo remains once '+width);
   check(v.h1===1,'Exactly one visible source or template H1 '+width);
   check(v.curated===(fixture.known?2:0),'Known source receives summary/planning; manual source receives no stale factual guide '+width);
   check(v.titleType.font.includes('YN Archivo')&&v.titleType.weight==='400','Project title matches homepage Archivo Black 400');
   check(v.sourceHeadingTypes.every(t=>t.font.includes('YN Archivo')&&t.weight==='400'),'All original source headings match homepage display type');
   check(v.sourceParagraphTypes.every(t=>t.font.includes('YN Manrope')),'All original paragraphs match homepage Manrope body type');
   check(v.source.left>=15&&width-v.source.right>=15,'Consistent full-width gallery retains safe responsive margins');
   if(fixture.known){
    check(v.articleCopy.includes('PUBLIC SYNTHETIC SUMMARY')&&v.articleCopy.includes('PUBLIC SYNTHETIC PLANNING'),'Reviewed summary and service planning are both visible');
    const arrows=await page.locator('.yn-project-service-links a>span[aria-hidden="true"]').evaluateAll(elements=>elements.map(e=>({size:getComputedStyle(e).fontSize,line:getComputedStyle(e,'::before').height,head:getComputedStyle(e,'::after').borderTopWidth})));
    check(arrows.length>0&&arrows.every(a=>a.size==='0px'&&a.line==='1px'&&a.head==='1px'),'Service arrows are drawn in CSS rather than platform-specific emoji');
   }
   check(v.photos.every(p=>p.alt&&p.box.width>100&&p.box.height>70),'All source photographs remain visible with original alt text');
   check(v.photos.every(p=>p.box.left>=v.source.left-1&&p.box.right<=v.source.right+1),'Photos remain inside premium margins');
   check(v.cta.every(c=>c.box.height>=44),'Next action links meet 44px target height');
   check(v.heading.height<280,'Project header remains compact '+width);
   check(v.sourceLinks.includes('/kontakt/'),'Original non-image destination link preserved');
   if(fixture.native){const columns=width<=620?1:width<=1100?2:3;check(v.gallery.columns===columns,'Responsive native gallery has '+columns+' columns at '+width);check(v.captionCount===3,'Every original native caption preserved');check(v.sourceCopy.includes('PUBLIC ORIGINAL NATIVE ANSWER'),'Original native accordion content preserved');}
   else{check(v.sourceCopy.includes('PUBLIC ORIGINAL ANSWER')&&v.sourceCopy.includes('PUBLIC SINGLE SOURCE COPY'),'Original Divi copy and FAQ remain together');if(width<=760)check(v.photos[0].fit==='contain','Single photo is not cropped on phone');}
   if([320,390,1440].includes(width))await page.screenshot({path:path.join(out,width+'-'+(fixture.native?'gallery3':'single')+(fixture.known?'-known':'')+'.png'),fullPage:true});
   if(fixture.native&&!fixture.known){
    const regression=await page.evaluate(()=>{
     const original=document.querySelector('.yn-project-detail-gallery'), probe=document.createElement('div');probe.className='yn-project-css-regression-fixture';
     const image=original.querySelector('img').outerHTML;
     probe.innerHTML='<div class="et_pb_text"><h2 style="font-family:Arial;font-weight:900;font-size:80px"><strong style="font-family:Arial;font-weight:900;color:red">Czytelny nagłówek realizacji</strong></h2><p><span style="font-family:serif;font-size:40px;line-height:3;color:red">Czytelny tekst o realizacji.</span></p></div><div class="gallery">'+[1,2,3].map(i=>'<dl class="gallery-item" style="float:left;width:30%;margin:20px"><dt class="gallery-icon"><a href="/synthetic-photo-'+i+'.jpg">'+image+'</a></dt><dd class="gallery-caption">Oryginalny podpis '+i+'</dd></dl>').join('')+'</div><div class="et_pb_gallery et_pb_gallery_grid"><div class="et_pb_gallery_items">'+[1,2,3].map(i=>'<div class="et_pb_gallery_item" style="float:left;width:30%;margin:20px"><div class="et_pb_gallery_image"><a href="/synthetic-divi-photo-'+i+'.jpg">'+image+'</a></div><p class="et_pb_gallery_caption">Oryginalny podpis Divi '+i+'</p></div>').join('')+'</div></div><div class="et_pb_gallery et_pb_gallery_fullwidth"><div class="et_pb_gallery_items" style="display:block;width:73%;margin:9px"><div class="et_pb_gallery_item">Zachowana scena slidera</div></div></div>';
     original.append(probe);
     const type=e=>({font:getComputedStyle(e).fontFamily,weight:getComputedStyle(e).fontWeight,size:parseFloat(getComputedStyle(e).fontSize),color:getComputedStyle(e).color});
     const grid=e=>({display:getComputedStyle(e).display,columns:getComputedStyle(e).gridTemplateColumns.split(' ').length,width:e.getBoundingClientRect().width,itemWidths:[...e.children].map(c=>c.getBoundingClientRect().width),items:[...e.children].map(c=>({left:c.getBoundingClientRect().left,right:c.getBoundingClientRect().right})),count:e.querySelectorAll('img').length});
     const slider=probe.querySelector('.et_pb_gallery_fullwidth .et_pb_gallery_items');
     return{heading:type(probe.querySelector('h2')),strong:type(probe.querySelector('strong')),paragraph:type(probe.querySelector('p span')),classic:grid(probe.querySelector('.gallery')),divi:grid(probe.querySelector('.et_pb_gallery_grid .et_pb_gallery_items')),slider:{display:getComputedStyle(slider).display,width:slider.style.width,margin:getComputedStyle(slider).marginTop},source:{left:original.getBoundingClientRect().left,right:original.getBoundingClientRect().right},documentWidth:document.documentElement.scrollWidth};
    });
    report.views[report.views.length-1].legacyCssProbe=regression;
    check(regression.heading.font.includes('YN Archivo')&&regression.heading.weight==='400'&&regression.heading.size<=29,'Legacy inline heading font/weight/size cannot break display typography');
    check(regression.strong.font===regression.heading.font&&regression.strong.weight==='400'&&regression.strong.color===regression.heading.color,'Nested bold heading inherits the same Archivo face and ink');
    check(regression.paragraph.font.includes('YN Manrope')&&regression.paragraph.size===16&&regression.paragraph.color!=='rgb(255, 0, 0)','Legacy inline body font/size/color cannot break readable copy');
    const columns=width<=620?1:width<=1100?2:3;
    for(const kind of ['classic','divi']){const grid=regression[kind];check(grid.display==='grid'&&grid.columns===columns,'Legacy '+kind+' gallery follows shared '+columns+'-column rhythm');check(grid.count===3&&grid.items.every(r=>r.left>=regression.source.left-1&&r.right<=regression.source.right+1),'Every '+kind+' photo remains within margins');check(grid.itemWidths.every(w=>w>100),'Legacy '+kind+' gallery has no collapsed column');}
    check(regression.slider.display==='block'&&regression.slider.width==='73%'&&regression.slider.margin==='9px','Divi slider stage is preserved independently of grid styling');
    check(regression.documentWidth<=width+1,'No overflow after legacy grid and inline typography probes');
   }
   console.log('PASS Project detail '+width+' '+fixture.path);
  }
  await context.close();
 }
 check(errors.length===0,'No project page JavaScript errors: '+JSON.stringify(errors));
 fs.writeFileSync(path.join(out,'geometry.json'),JSON.stringify(report,null,2));
 console.log('PASS '+assertions+' browser project assertions across '+report.views.length+' responsive views. Labelled public synthetic photographs and browser-only legacy layout probes; licensed Divi unavailable.');
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1);});
