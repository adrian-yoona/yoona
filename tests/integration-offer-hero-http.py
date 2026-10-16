#!/usr/bin/env python3
"""Read-only HTTP assertions against disposable WordPress with real Rank Math."""
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import urlopen

base = sys.argv[1].rstrip('/')
assertions = 0

def check(condition, label):
    global assertions
    if not condition:
        raise AssertionError(label)
    assertions += 1
    print('PASS Offer HTTP ' + label)

class Document(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.tags = []
        self.feed(html)
    def handle_starttag(self, tag, attrs):
        self.tags.append((tag, dict(attrs)))
    def find(self, tag=None, **attributes):
        return [(name, attrs) for name, attrs in self.tags if (not tag or tag == name) and all(attrs.get(key) == value for key, value in attributes.items())]

configuration = json.loads((Path(__file__).resolve().parent.parent / 'plugin/yoona-seo/assets/design-data.json').read_text())['offer_heroes']
for index, slug in enumerate(['plotki-ogrodki-piwne', 'oklejanie-przyczep-gastronomicznych', 'tabliczki-informacyjne'], 1):
    path = '/' + slug + '/'
    with urlopen(base + path) as response:
        check(response.status == 200, path + ' serves successfully.')
        html = response.read().decode()
    doc = Document(html)
    check(len(doc.find('header', **{'class': 'yn-offer-hero', 'aria-labelledby': 'yn-offer-title'})) == 1, path + ' contains one accessible premium header.')
    check(not doc.find('header', **{'class': 'yn-page-heading'}), path + ' omits the former separate black title strip.')
    check(len(doc.find('h1')) == 1, path + ' has exactly one H1.')
    check(bool(doc.find('p' if index == 1 else 'h1', id='yn-offer-title')), path + ' preserves the existing body H1 when present.')
    check(len(doc.find('title')) == 1 and 'OFERTA TEST SEO ' + str(index) in html, path + ' retains the native Rank Math title.')
    descriptions = doc.find('meta', name='description')
    check(len(descriptions) == 1 and descriptions[0][1]['content'] == 'Opis SEO testowej oferty numer ' + str(index) + '.', path + ' retains one exact native Rank Math description.')
    canonical = doc.find('link', rel='canonical')
    check(len(canonical) == 1 and canonical[0][1]['href'] == base + path, path + ' retains its canonical URL.')
    image = doc.find('img', **{'class': 'yn-offer-image'})
    check(len(image) == 1 and image[0][1]['src'] == base + '/' + configuration[path]['image'].split('yoona.pl/', 1)[1], path + ' uses its own reviewed photograph URL.')
    check(bool(image[0][1]['alt']) and image[0][1].get('fetchpriority') == 'high' and image[0][1].get('loading') == 'eager', path + ' has an accessible prioritized image.')
    check(bool(doc.find(id='offer-preserved-body')) and bool(doc.find(id='yn-offer-content')), path + ' retains the following body and working content target.')
    check(bool(doc.find(**{'data-module': 'et_pb_section', 'data-counter': '1'})) and not doc.find(**{'data-module': 'et_pb_section', 'data-counter': '0'}), path + ' keeps Divi-like section counters aligned while removing only banner output.')
    check(html.count('yoona-design-head-probe') == 1 and html.count('yoona-design-body-probe') == 1 and html.count('yoona-design-footer-probe') == 1, path + ' executes the normal WordPress head, body and footer hooks once.')
    check('[et_pb_' not in html, path + ' renders shortcode content rather than exposing raw builder source.')

with urlopen(base + '/offer-protected/') as response:
    html = response.read().decode()
check('post-password-form' in html and 'yn-offer-hero' not in html, 'Password protection stays active without a premium offer hero.')
with urlopen(base + '/plotki-ogrodki-piwne/?yoona_offer_mutate=1') as response:
    html = response.read().decode()
check('class="yn-page-heading"' in html and 'class="yn-offer-hero"' not in html and 'data-module="et_pb_section" data-counter="0"' in html, 'Unknown runtime banner edits keep both fallback title and original rendered banner.')
print(f'Success: {assertions} focused real WordPress/Rank Math offer HTTP assertions passed.')
