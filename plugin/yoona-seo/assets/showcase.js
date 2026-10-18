/* YOONA: progressive, dependency-free slideshow and scoped image viewer. */
(() => {
 'use strict';
 const boot = () => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  document.querySelectorAll('[data-yn-showcase]').forEach(root => {
   const slides = [...root.querySelectorAll('[data-yn-slide]')];
   const controls = root.querySelector('[data-yn-showcase-controls]');
   if (slides.length < 2 || !controls) return;
   const dots = [...controls.querySelectorAll('[data-yn-go]')];
   const pause = controls.querySelector('[data-yn-pause]');
   const count = controls.querySelector('[data-yn-current]');
   const announcement = controls.querySelector('[data-yn-announcement]');
   let index = 0, timer = 0, paused = reducedMotion.matches, hovered = false, focused = false, visible = true;
   const stop = () => { window.clearTimeout(timer); timer = 0; };
   const schedule = () => {
    stop();
    if (!paused && !hovered && !focused && visible && !document.hidden) timer = window.setTimeout(() => show(index + 1, false), 7000);
   };
   const updatePause = () => {
    pause.setAttribute('aria-label', paused ? 'Włącz automatyczną zmianę slajdów' : 'Wstrzymaj automatyczną zmianę slajdów');
    pause.setAttribute('aria-pressed', String(paused));
    pause.querySelector('span').textContent = paused ? '▷' : 'Ⅱ';
   };
   const show = (next, announce = true) => {
    index = (next + slides.length) % slides.length;
    slides.forEach((slide, i) => {
     slide.classList.toggle('is-active', i === index);
     slide.setAttribute('aria-hidden', String(i !== index));
     slide.inert = i !== index;
    });
    dots.forEach((dot, i) => i === index ? dot.setAttribute('aria-current', 'true') : dot.removeAttribute('aria-current'));
    count.textContent = String(index + 1).padStart(2, '0');
    if (announce) announcement.textContent = slides[index].getAttribute('aria-label');
    schedule();
   };
   slides.forEach(slide => { slide.hidden = false; });
   root.dataset.ynReady = 'true';
   controls.hidden = false;
   controls.querySelector('[data-yn-prev]').addEventListener('click', () => show(index - 1));
   controls.querySelector('[data-yn-next]').addEventListener('click', () => show(index + 1));
   dots.forEach(dot => dot.addEventListener('click', () => show(Number(dot.dataset.ynGo))));
   pause.addEventListener('click', () => { paused = !paused; updatePause(); schedule(); });
   root.addEventListener('keydown', event => {
    if (event.altKey || event.ctrlKey || event.metaKey || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
    if (event.target.matches('input,textarea,select,[contenteditable]')) return;
    event.preventDefault();
    const next = event.key === 'Home' ? 0 : event.key === 'End' ? slides.length - 1 : index + (event.key === 'ArrowRight' ? 1 : -1);
    // Arrow navigation from a slide CTA must not leave focus inside an inert slide.
    if (event.target.closest('[data-yn-slide]')) controls.querySelector(event.key === 'ArrowLeft' ? '[data-yn-prev]' : '[data-yn-next]').focus();
    show(next);
   });
   root.addEventListener('pointerenter', event => { if (event.pointerType === 'mouse') { hovered = true; stop(); } });
   root.addEventListener('pointerleave', event => { if (event.pointerType === 'mouse') { hovered = false; schedule(); } });
   root.addEventListener('focusin', () => { focused = true; stop(); });
   root.addEventListener('focusout', () => { window.setTimeout(() => { focused = root.contains(document.activeElement); schedule(); }, 0); });
   document.addEventListener('visibilitychange', schedule);
   reducedMotion.addEventListener('change', () => { if (reducedMotion.matches) paused = true; updatePause(); schedule(); });
   if ('IntersectionObserver' in window) new IntersectionObserver(entries => { visible = entries[0].isIntersecting; schedule(); }, {threshold: .15}).observe(root);
   updatePause();
   show(0, false);
  });

  // Do not hijack arbitrary image links or downloads. Existing hrefs remain useful without JS.
  if (!window.HTMLDialogElement || !HTMLDialogElement.prototype.showModal) return;
  const scopeSelector = '.yn-project-detail-gallery,.et_pb_gallery,.wp-block-gallery,[data-yn-gallery]';
  const imageURL = value => {
   try {
    const url = new URL(value, window.location.href);
    return /^https?:$/.test(url.protocol) && /\.(?:avif|gif|jpe?g|png|webp)$/i.test(url.pathname) ? url.href : '';
   } catch (_) { return ''; }
  };
  const imageAlt = image => {
   const alt = image ? (image.getAttribute('alt') || '').trim() : '';
   // Camera/file labels are not meaningful captions or alternative descriptions.
   return alt && !/\.(?:avif|gif|jpe?g|png|webp)(?:\s|$)|^(?:IMG|DSC|DJI|PXL)[_\s-]?\d/i.test(alt) ? alt : 'Zdjęcie realizacji YOONA';
  };
  document.querySelectorAll('.yn-project-detail-gallery img').forEach(image => {
   if (image.closest('a,button,picture[data-no-lightbox]')) return;
   const url = imageURL(image.currentSrc || image.getAttribute('src'));
   if (!url) return;
   const link = document.createElement('a');
   link.href = url;
   link.dataset.ynGallery = '';
   const target = image.closest('picture') || image;
   target.replaceWith(link);
   link.append(target);
  });
  const configured = window.yoonaShowcase && typeof window.yoonaShowcase === 'object' ? window.yoonaShowcase : {};
  let contactURL = '';
  try {
   const url = new URL(configured.contactUrl || '', window.location.href);
   if (url.origin === window.location.origin && configured.contactUrl) contactURL = url.href;
  } catch (_) { /* Use the current public footer's route. */ }
  if (!contactURL) {
   const link = document.querySelector('.yn-footer a[href*="/kontakt"],.yn-contact-band a[href*="/kontakt"],.yn-header-cta[href*="/kontakt"]');
   contactURL = link ? link.href : new URL('kontakt/', document.baseURI).href;
  }
  const rawPhones = Array.isArray(configured.phones) ? configured.phones : [...document.querySelectorAll('.yn-contact-location a[href^="tel:"]')].map(a => ({href:a.getAttribute('href'),label:a.textContent.trim()}));
  const phones = rawPhones.filter(phone => phone && typeof phone.href === 'string' && /^tel:\+?[\d ()-]{6,25}$/.test(phone.href) && typeof phone.label === 'string' && phone.label.trim()).filter((phone,index,list) => list.findIndex(item => item.href === phone.href) === index).slice(0,2);
  let dialog, image, stage, previous, next, status, opener, items = [], position = 0;
  const fit = () => {
   if (!dialog || !dialog.open || !image.naturalWidth) return;
   const viewportWidth = window.visualViewport ? window.visualViewport.width : window.innerWidth;
   const viewportHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;
   const maxWidth = Math.min(1280, viewportWidth - (viewportWidth <= 600 ? 24 : 80));
   const ratio = image.naturalWidth / image.naturalHeight;
   let maxHeight = Math.max(60,viewportHeight - 180);
   // Account for the actual phone/CTA layout: a portrait may need two or three
   // contact rows even on a wide screen. Never push the close button offscreen.
   for (let pass=0;pass<3;pass++) {
    const height = Math.min(maxHeight, maxWidth / ratio, image.naturalHeight);
    const width = Math.min(maxWidth, Math.max(Math.min(260,maxWidth), height * ratio));
    stage.style.width = `${Math.round(width)}px`;
    image.style.width = `${Math.round(width)}px`;
    image.style.height = `${Math.round(height)}px`;
    stage.classList.toggle('is-narrow', width < 600);
    stage.classList.toggle('is-tiny', width < 340);
    const chrome = dialog.querySelector('.yn-lightbox-toolbar').offsetHeight + dialog.querySelector('.yn-lightbox-contact').offsetHeight + dialog.querySelector('.yn-lightbox-navigation').offsetHeight + (viewportWidth <= 600 ? 24 : 32);
    maxHeight = Math.max(60,viewportHeight - chrome - 4);
   }
  };
  const setImage = nextPosition => {
   position = (nextPosition + items.length) % items.length;
   const item = items[position];
   image.alt = imageAlt(item.querySelector('img'));
   image.src = item.href;
   status.textContent = `Zdjęcie ${position + 1} z ${items.length}`;
   previous.hidden = next.hidden = items.length < 2;
   // Reserve a useful frame while a full-resolution image is loading.
   if (!image.complete) { image.style.height = '240px'; stage.style.width = `${Math.min(640,window.innerWidth-32)}px`; }
   fit();
  };
  const create = () => {
   dialog = document.createElement('dialog');
   dialog.className = 'yn-lightbox';
   dialog.setAttribute('aria-label', 'Galeria realizacji YOONA');
   const shell = document.createElement('div'); shell.className = 'yn-lightbox-shell';
   const toolbar = document.createElement('div'); toolbar.className = 'yn-lightbox-toolbar';
   status = document.createElement('p'); status.className = 'yn-lightbox-status'; status.setAttribute('aria-live','polite');
   const close = document.createElement('button'); close.type = 'button'; close.className = 'yn-lightbox-close'; close.setAttribute('aria-label','Zamknij galerię'); close.textContent = '×';
   toolbar.append(status,close);
   stage = document.createElement('div'); stage.className = 'yn-lightbox-stage';
   image = document.createElement('img'); image.className = 'yn-lightbox-image'; image.decoding = 'async'; image.addEventListener('load',fit);
   image.addEventListener('error', () => { status.textContent = 'Nie udało się wczytać zdjęcia. Zamknij galerię i spróbuj ponownie.'; });
   const contact = document.createElement('div'); contact.className = 'yn-lightbox-contact';
   phones.forEach(phone => { const a=document.createElement('a');a.className='yn-lightbox-phone';a.href=phone.href;a.textContent=phone.label;contact.append(a); });
   const quote=document.createElement('a');quote.className='yn-lightbox-quote';quote.href=contactURL;quote.textContent='Zapytaj o wycenę';contact.append(quote);
   stage.append(image,contact);
   const navigation=document.createElement('div');navigation.className='yn-lightbox-navigation';
   previous=document.createElement('button');previous.type='button';previous.setAttribute('aria-label','Poprzednie zdjęcie');previous.textContent='←';
   next=document.createElement('button');next.type='button';next.setAttribute('aria-label','Następne zdjęcie');next.textContent='→';
   navigation.append(previous,next);shell.append(toolbar,stage,navigation);dialog.append(shell);document.body.append(dialog);
   close.addEventListener('click',()=>dialog.close());
   previous.addEventListener('click',()=>setImage(position-1));next.addEventListener('click',()=>setImage(position+1));
   dialog.addEventListener('click',event=>{if(event.target===dialog)dialog.close();});
   dialog.addEventListener('keydown',event=>{
    if(event.key==='Tab'){
     const focusable=[...dialog.querySelectorAll('button:not([disabled]),a[href]')].filter(element=>!element.hidden&&element.getClientRects().length);
     const first=focusable[0],last=focusable[focusable.length-1];
     if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
     else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
    }
    if(event.key==='ArrowLeft'||event.key==='ArrowRight'){event.preventDefault();event.stopPropagation();if(items.length>1)setImage(position+(event.key==='ArrowRight'?1:-1));}
   });
   dialog.addEventListener('close',()=>{document.body.classList.remove('yn-lightbox-open');image.removeAttribute('src');if(opener&&opener.isConnected)opener.focus({preventScroll:true});});
   window.addEventListener('resize',fit);
   if(window.visualViewport)window.visualViewport.addEventListener('resize',fit);
  };
  // Capture at the document prevents Divi/MagnificPopup delegated handlers from
  // opening a second viewer. It is limited to the public galleries above.
  document.addEventListener('click',event=>{
   if(event.defaultPrevented||event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
   const link=event.target instanceof Element ? event.target.closest('a[href]') : null;
   if(!link||link.hasAttribute('download')||!link.querySelector('img')||!imageURL(link.href))return;
   const scope=link.closest(scopeSelector);
   if(!scope)return;
   const group=scope.matches('a') ? scope.closest('.yn-project-detail-gallery,.et_pb_gallery,.wp-block-gallery')||scope.parentElement : scope;
   const links=[...group.querySelectorAll('a[href]')].filter(a=>a.querySelector('img')&&!a.hasAttribute('download')&&imageURL(a.href));
   if(!links.includes(link))return;
   event.preventDefault();event.stopImmediatePropagation();
   if(!dialog)create();
   opener=link;items=links;position=links.indexOf(link);setImage(position);
   if(!dialog.open)dialog.showModal();
   document.body.classList.add('yn-lightbox-open');fit();dialog.querySelector('.yn-lightbox-close').focus();
  },true);
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();
