/* Reuse Site Kit's Google tag, or an existing GTM. Never install another tag. */
(function () {
  'use strict';
  if (window.yoonaContactEventsInstalled) return;
  window.yoonaContactEventsInstalled = true;
  var cookieYesChoice = null;
  function readCookie(name) {
    var prefix = name + '=';
    var cookie = document.cookie.split(';').map(function (part) { return part.trim(); }).filter(function (part) { return part.indexOf(prefix) === 0; })[0];
    if (!cookie) return '';
    try { return decodeURIComponent(cookie.slice(prefix.length)); } catch (error) { return ''; }
  }
  function analyticsAllowed() {
    var choices = [];
    if (cookieYesChoice !== null) choices.push(cookieYesChoice);
    var cookie = readCookie('cookieyes-consent');
    var match = cookie.match(/(?:^|,)\s*analytics\s*:\s*(yes|no)\s*(?:,|$)/i);
    if (match) choices.push(match[1].toLowerCase() === 'yes');
    var legacy = readCookie('cookielawinfo-checkbox-analytics');
    if (readCookie('viewed_cookie_policy') === 'yes' && /^(yes|no)$/i.test(legacy)) choices.push(legacy.toLowerCase() === 'yes');
    if (typeof window.wp_has_consent === 'function' && (window.wp_consent_type || window.wp_fallback_consent_type)) {
      try { choices.push(window.wp_has_consent('statistics') === true); } catch (error) { choices.push(false); }
    }
    return choices.length > 0 && choices.indexOf(false) === -1 && choices.indexOf(true) !== -1;
  }
  document.addEventListener('cookieyes_consent_update', function (event) {
    if (event.detail && Array.isArray(event.detail.accepted)) cookieYesChoice = event.detail.accepted.indexOf('analytics') !== -1;
  });
  document.addEventListener('click', function (event) {
    var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
    if (!link || !analyticsAllowed()) return;
    var href = link.getAttribute('href') || '';
    var method = /^tel:/i.test(href) ? 'phone' : (/^mailto:/i.test(href) ? 'email' : '');
    var label = (link.textContent || '').replace(/\s+/g, ' ').trim();
    var quote = !method && /(?:wycen|prześlij projekt)/i.test(label);
    if (!method && !quote) return;
    var place = link.closest('[data-yn-location],.yn-contact-location,.yn-location-strip-inner');
    var location = place && place.getAttribute('data-yn-location') || '';
    if (!location && place) {
      var city = ((link.closest('.yn-contact-location') || link).textContent || '').match(/Olsztyn|Ostróda/i);
      if (city) location = city[0];
    }
    var name = method ? 'yoona_contact_click' : 'yoona_quote_click';
    var parameters = {
      contact_method: method || 'quote_page',
      contact_location: /^(?:Olsztyn|Ostróda)$/i.test(location) ? location : 'not_specified',
      contact_placement: link.closest('.yn-header,.yn-location-strip') ? 'header' : (link.closest('footer,.yn-contact') ? 'footer' : 'content'),
      page_path: window.location.pathname
    };
    if (typeof window.gtag === 'function') {
      // Site Kit supplies gtag and its destinations/Consent Mode configuration.
      window.gtag('event', name, parameters);
    } else if (window.dataLayer && typeof window.dataLayer.push === 'function') {
      // Existing GTM requires the custom-event mapping described in the docs.
      window.dataLayer.push(Object.assign({event: name}, parameters));
    }
  });
}());
