#!/usr/bin/env node
'use strict';

// Execute the shipped asset in isolated VM contexts. Browser/WordPress tests
// separately cover real DOM, CookieYes configuration and tag delivery.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const sourcePath = path.join(__dirname, '../plugin/yoona-seo/assets/measurement.js');
const source = fs.readFileSync(sourcePath, 'utf8');
let checks = 0;
const check = {
  equal(actual, expected, message) { ++checks; assert.equal(actual, expected, message); },
  deep(actual, expected, message) { ++checks; assert.deepEqual(JSON.parse(JSON.stringify(actual)), expected, message); },
  ok(value, message) { ++checks; assert.ok(value, message); },
};

class Element {
  constructor(tag, { classes = [], attributes = {}, text = '', parent = null } = {}) {
    this.tag = tag;
    this.classes = new Set(classes);
    this.attributes = attributes;
    this.text = text;
    this.parent = parent;
    this.children = [];
    if (parent) parent.children.push(this);
  }
  getAttribute(name) { return Object.hasOwn(this.attributes, name) ? this.attributes[name] : null; }
  get textContent() { return this.text + this.children.map(child => child.textContent).join(' '); }
  matches(selector) {
    const match = selector.trim().match(/^([a-z]+)?(?:\.([\w-]+))?(?:\[([\w-]+)\])?$/i);
    if (!match) throw new Error('Unsupported selector in test DOM: ' + selector);
    return (!match[1] || match[1] === this.tag) &&
      (!match[2] || this.classes.has(match[2])) &&
      (!match[3] || Object.hasOwn(this.attributes, match[3]));
  }
  closest(selectors) {
    for (let item = this; item; item = item.parent) {
      if (selectors.split(',').some(selector => item.matches(selector))) return item;
    }
    return null;
  }
}

function environment({ cookie = '', gtag = true, dataLayer = false, wpConsent, wpType, wpFallbackType } = {}) {
  let cookieValue = cookie;
  const cookieWrites = [];
  const calls = [];
  const layerCalls = [];
  const listeners = new Map();
  const forbidden = [];
  const forbid = operation => function () {
    forbidden.push(operation);
    throw new Error('Measurement must not perform ' + operation);
  };
  const document = {
    addEventListener(type, callback) {
      if (!listeners.has(type)) listeners.set(type, []);
      listeners.get(type).push(callback);
    },
    createElement: forbid('document.createElement'),
    write: forbid('document.write'),
    head: { appendChild: forbid('head.appendChild') },
    body: { appendChild: forbid('body.appendChild') },
  };
  Object.defineProperty(document, 'cookie', {
    get() { return cookieValue; },
    set(value) { cookieWrites.push(value); throw new Error('Measurement must not write cookies'); },
  });
  const location = { pathname: '/kontakt/' };
  for (const property of ['search', 'hash', 'href']) {
    Object.defineProperty(location, property, { get: forbid('read location.' + property) });
  }
  const window = {
    location,
    fetch: forbid('fetch'),
    XMLHttpRequest: forbid('XMLHttpRequest'),
    Image: forbid('Image'),
    setTimeout: forbid('setTimeout/replay'),
    setInterval: forbid('setInterval/replay'),
    queueMicrotask: forbid('queueMicrotask/replay'),
    navigator: { sendBeacon: forbid('sendBeacon') },
  };
  if (gtag) window.gtag = (...args) => calls.push(args);
  if (dataLayer) window.dataLayer = { push: object => layerCalls.push(object) };
  if (wpConsent !== undefined) window.wp_has_consent = wpConsent;
  if (wpType !== undefined) window.wp_consent_type = wpType;
  if (wpFallbackType !== undefined) window.wp_fallback_consent_type = wpFallbackType;
  const originalGtag = window.gtag;
  const originalLayer = window.dataLayer;
  const context = vm.createContext({ window, document,
    fetch: window.fetch, XMLHttpRequest: window.XMLHttpRequest, Image: window.Image,
    setTimeout: window.setTimeout, setInterval: window.setInterval, queueMicrotask: window.queueMicrotask,
    navigator: window.navigator });
  const run = () => vm.runInContext(source, context, { filename: sourcePath, timeout: 1000 });
  const emit = (type, event = {}) => {
    for (const listener of listeners.get(type) || []) listener(event);
  };
  run();
  return {
    window, document, calls, layerCalls, listeners, run,
    click: target => emit('click', { target }),
    consent: accepted => emit('cookieyes_consent_update', { detail: { accepted } }),
    emit,
    setCookie(value) { cookieValue = value; },
    verifyNoSideEffects() {
      check.deep(cookieWrites, [], 'No cookie writes');
      check.deep(forbidden, [], 'No network/tag creation, location query/hash reads or replay timers');
      check.equal(window.gtag, originalGtag, 'Existing Google tag is retained');
      check.equal(window.dataLayer, originalLayer, 'Existing layer is retained; no new layer is installed');
    },
  };
}

function fixture() {
  const main = new Element('main');
  const header = new Element('header', { classes: ['yn-header'], parent: main });
  const strip = new Element('div', { classes: ['yn-location-strip'], parent: header });
  const place = new Element('div', { attributes: { 'data-yn-location': 'Olsztyn' }, parent: strip });
  const phone = new Element('a', { attributes: { href: 'tel:+48600123456' }, text: 'Zadzwoń +48 600 123 456', parent: place });
  const phoneChild = new Element('span', { text: 'Telefon', parent: phone });
  const quote = new Element('a', { attributes: { href: '/kontakt/?email=private-person@example.invalid#secret' }, text: 'Zapytaj o wycenę', parent: header });
  const footer = new Element('footer', { parent: main });
  const office = new Element('div', { classes: ['yn-contact-location'], text: 'Ostróda ', parent: footer });
  const email = new Element('a', { attributes: { href: 'mailto:private-person@example.invalid?body=private-message' }, text: 'private-person@example.invalid', parent: office });
  const content = new Element('section', { parent: main });
  const contentPhone = new Element('a', { attributes: { href: 'TEL:+48600123456' }, text: 'Telefon', parent: content });
  const project = new Element('a', { attributes: { href: '/kontakt/' }, text: 'Prześlij projekt', parent: content });
  const ordinary = new Element('a', { attributes: { href: '/nasze-realizacje/' }, text: 'Realizacje', parent: content });
  const emptyHref = new Element('a', { text: 'Wyceń', parent: content });
  return { phone, phoneChild, quote, email, contentPhone, project, ordinary, emptyHref, content };
}

const links = fixture();
const yes = 'cookieyes-consent=' + encodeURIComponent('consentid:fixture,necessary:yes,analytics:yes,advertisement:no');
const no = 'cookieyes-consent=' + encodeURIComponent('consentid:fixture,analytics:no,necessary:yes');

// No positive consent means no send, no queued replay, and no manufactured layer.
{
  const test = environment({ dataLayer: true });
  test.click(links.phone);
  test.click(links.quote);
  check.equal(test.calls.length, 0, 'Unknown consent does not call existing gtag');
  check.equal(test.layerCalls.length, 0, 'Unknown consent does not enqueue events in GTM');
  test.consent(['necessary', 'analytics']);
  check.equal(test.calls.length, 0, 'Giving consent does not replay preceding clicks');
  check.equal(test.layerCalls.length, 0, 'Giving consent does not replay into GTM');
  test.click(links.phone);
  check.equal(test.calls.length, 1, 'A fresh click after consent is measurable');
  test.verifyNoSideEffects();
}

// CookieYes v3, preferred Site Kit path, nested click target, and exact metadata.
{
  const test = environment({ cookie: yes, dataLayer: true });
  test.click(links.phoneChild);
  check.deep(test.calls, [['event', 'yoona_contact_click', {
    contact_method: 'phone', contact_location: 'Olsztyn', contact_placement: 'header', page_path: '/kontakt/',
  }]], 'Existing gtag receives one contact event with useful public metadata');
  check.equal(test.layerCalls.length, 0, 'Do not send the same event through GTM when gtag exists');
  test.click(links.email);
  check.deep(test.calls[1], ['event', 'yoona_contact_click', {
    contact_method: 'email', contact_location: 'Ostróda', contact_placement: 'footer', page_path: '/kontakt/',
  }], 'Email remains contact intent; footer office text identifies Ostróda');
  test.click(links.quote);
  check.deep(test.calls[2], ['event', 'yoona_quote_click', {
    contact_method: 'quote_page', contact_location: 'not_specified', contact_placement: 'header', page_path: '/kontakt/',
  }], 'Header quote CTA is correctly placed and does not invent an office');
  test.click(links.project);
  check.equal(test.calls[3][1], 'yoona_quote_click', 'Prześlij projekt is a quote intent');
  check.equal(test.calls[3][2].contact_placement, 'content', 'Content quote placement');
  test.click(links.contentPhone);
  check.equal(test.calls[4][2].contact_method, 'phone', 'Phone scheme matching is case insensitive');
  check.equal(test.calls[4][2].contact_location, 'not_specified', 'Unattributed contact does not acquire a city');
  test.click(links.ordinary);
  test.click(links.emptyHref);
  test.click(links.content);
  test.click(null);
  check.equal(test.calls.length, 5, 'Ordinary navigation, anchors without href and non-elements are ignored');
  check.ok(!/private-person|private-message|600123456|600 123 456|secret|email=|generate_lead|send_to|consent.*granted/.test(JSON.stringify(test.calls)),
    'Do not leak contact addresses/numbers, message, query/hash or manufacture lead/destination/consent fields');
  check.deep(Object.keys(test.calls[0][2]).sort(), ['contact_location', 'contact_method', 'contact_placement', 'page_path'], 'Only four allowed parameters');
  test.verifyNoSideEffects();
}

// When only an existing GTM layer is present, emit one custom object for mapping.
{
  const test = environment({ cookie: yes, gtag: false, dataLayer: true });
  test.click(links.phone);
  check.equal(test.calls.length, 0, 'GTM-only path does not install or invoke a new gtag');
  check.deep(test.layerCalls, [{ event: 'yoona_contact_click', contact_method: 'phone',
    contact_location: 'Olsztyn', contact_placement: 'header', page_path: '/kontakt/' }], 'One GTM custom event');
  test.click(links.quote);
  check.equal(test.layerCalls[1].event, 'yoona_quote_click', 'Quote has its own fallback event');
  test.verifyNoSideEffects();
}

// Consent is evaluated at click time. Revocation and conflicting sources deny.
{
  const test = environment({ cookie: yes });
  test.click(links.phone);
  check.equal(test.calls.length, 1, 'Initial CookieYes approval');
  test.setCookie(no);
  test.consent(['necessary']);
  test.click(links.email);
  check.equal(test.calls.length, 1, 'Revoked consent stops subsequent events');
  test.setCookie(yes);
  test.click(links.email);
  check.equal(test.calls.length, 1, 'A stale denial event is not overruled by a contradictory cookie');
  test.consent(['necessary', 'analytics']);
  check.equal(test.calls.length, 1, 'Regranting consent does not replay skipped email clicks');
  test.click(links.email);
  check.equal(test.calls.length, 2, 'Fresh click after a consistent regrant');
  test.verifyNoSideEffects();
}

// Event-based CookieYes updates also work without a readable consent cookie.
{
  const test = environment();
  test.emit('cookieyes_consent_update', {});
  test.emit('cookieyes_consent_update', { detail: { accepted: 'analytics' } });
  test.click(links.phone);
  check.equal(test.calls.length, 0, 'Malformed/missing consent details do not grant consent');
  test.consent(['analytics']);
  test.click(links.phone);
  check.equal(test.calls.length, 1, 'Recognized CookieYes event grants the next click');
  test.consent([]);
  test.click(links.phone);
  check.equal(test.calls.length, 1, 'An empty accepted array revokes analytics');
  test.verifyNoSideEffects();
}

// Public WordPress Consent API must have a declared consent model.
{
  const asked = [];
  let allowed = true;
  const test = environment({ wpType: 'optin', wpConsent: category => { asked.push(category); return allowed; } });
  test.click(links.phone);
  check.equal(test.calls.length, 1, 'WP Consent API statistics approval allows event');
  check.deep(asked, ['statistics'], 'Query analytics statistics, not functional/marketing consent');
  allowed = false;
  test.click(links.phone);
  check.equal(test.calls.length, 1, 'WP Consent API revocation is respected');
  allowed = true;
  test.setCookie(no);
  test.click(links.phone);
  check.equal(test.calls.length, 1, 'CookieYes denial wins over contradictory WP approval');
  test.verifyNoSideEffects();
}
{
  const undeclared = environment({ wpConsent: () => true });
  undeclared.click(links.phone);
  check.equal(undeclared.calls.length, 0, 'API default without consent type cannot silently grant');
  undeclared.verifyNoSideEffects();
  const fallback = environment({ wpFallbackType: 'optin', wpConsent: () => true });
  fallback.click(links.phone);
  check.equal(fallback.calls.length, 1, 'Declared WP fallback consent type is supported');
  fallback.verifyNoSideEffects();
  const broken = environment({ cookie: yes, wpType: 'optin', wpConsent() { throw new Error('fixture API error'); } });
  broken.click(links.phone);
  check.equal(broken.calls.length, 0, 'Consent API error fails closed despite positive cookie');
  broken.verifyNoSideEffects();
}

// Legacy CookieYes needs both a saved policy choice and analytics approval.
{
  const test = environment({ cookie: 'cookielawinfo-checkbox-analytics=yes' });
  test.click(links.phone);
  check.equal(test.calls.length, 0, 'Legacy checkbox without saved policy does not grant');
  test.setCookie('viewed_cookie_policy=yes; cookielawinfo-checkbox-analytics=yes');
  test.click(links.phone);
  check.equal(test.calls.length, 1, 'Saved legacy analytics approval works');
  test.setCookie('viewed_cookie_policy=yes; cookielawinfo-checkbox-analytics=no');
  test.click(links.phone);
  check.equal(test.calls.length, 1, 'Legacy analytics denial blocks');
  test.setCookie('cookieyes-consent=%E0%A4%A');
  test.click(links.phone);
  check.equal(test.calls.length, 1, 'Malformed encoded cookie cannot grant consent or crash clicks');
  test.verifyNoSideEffects();
}

// Re-running the asset must neither duplicate listeners nor create tracking.
{
  const test = environment({ cookie: yes });
  test.run();
  check.equal(test.listeners.get('click').length, 1, 'Double initialization installs one delegated click listener');
  check.equal(test.listeners.get('cookieyes_consent_update').length, 1, 'Double initialization installs one consent listener');
  test.click(links.phone);
  check.equal(test.calls.length, 1, 'Double initialization still records one event');
  test.verifyNoSideEffects();
  const absent = environment({ cookie: yes, gtag: false, dataLayer: false });
  absent.click(links.phone);
  check.equal(absent.calls.length + absent.layerCalls.length, 0, 'No existing tracking means no new tracker or queue');
  check.equal(absent.window.dataLayer, undefined, 'Do not create dataLayer when absent');
  absent.verifyNoSideEffects();
}

console.log(`PASS: ${checks} assertions — shipped contact measurement, Site Kit/GTM routing, consent, privacy and single initialization.`);
