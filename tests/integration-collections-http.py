#!/usr/bin/env python3
"""Read-only responses from disposable WordPress with actual Rank Math."""
import json
import re
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
    print('PASS Collections HTTP ' + label)

class Document(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.tags = []
        self.feed(html)
    def handle_starttag(self, tag, attrs):
        self.tags.append((tag, dict(attrs)))
    def find(self, tag=None, **attributes):
        return [(name, attrs) for name, attrs in self.tags if (not tag or tag == name) and all(attrs.get(key) == value for key, value in attributes.items())]
    def classes(self, token):
        return [(tag, attrs) for tag, attrs in self.tags if token in attrs.get('class', '').split()]

def get(path):
    with urlopen(base + path) as response:
        check(response.status == 200, path + ' responds successfully.')
        html = response.read().decode()
    return html, Document(html)

data = json.loads((Path(__file__).resolve().parent.parent / 'plugin/yoona-seo/assets/design-data.json').read_text())
for path in data['removed_contact_sections']:
    html, doc = get(path)
    if path == '/pelna-oferta/':
        check(len(doc.classes('yn-catalog-card')) == 41, 'The full catalogue displays exactly 41 public services.')
        check(len(doc.classes('yn-offer-catalog')) == 1 and not doc.find(id='collection-preserved-before'), 'The dedicated catalogue replaces the former repeated full-offer body.')
    else:
        check(bool(doc.find(id='collection-preserved-before')) and bool(doc.find(id='collection-preserved-after')), path + ' retains both surrounding content fixtures.')
    text = re.sub('<[^>]+>', ' ', html)
    check(not re.search(r'MASZ\s+PYTANIE', text, re.I), path + ' omits the reviewed repeated contact section.')
    check(len(doc.find('title')) == 1 and 'KATALOG TEST SEO' in html, path + ' retains its native Rank Math title.')
    descriptions = doc.find('meta', name='description')
    check(len(descriptions) == 1 and descriptions[0][1]['content'] == 'Natywny opis SEO katalogu i oferty.', path + ' retains one exact native Rank Math description.')
    canonical = doc.find('link', rel='canonical')
    check(len(canonical) == 1 and canonical[0][1]['href'] == base + path, path + ' retains its canonical URL.')
    check(html.count('yoona-design-head-probe') == 1 and html.count('yoona-design-body-probe') == 1 and html.count('yoona-design-footer-probe') == 1, path + ' executes normal WordPress document hooks once.')
    check('[et_pb_' not in html, path + ' contains rendered content rather than raw shortcodes.')

first, first_doc = get('/nasze-realizacje/')
second, second_doc = get('/nasze-realizacje/page/2/')
check(len(first_doc.classes('yn-project-gallery-card')) == 30, 'The first native projects page displays exactly 30 projects.')
check(len(second_doc.classes('yn-project-gallery-card')) == 10, 'The next native projects page displays the remaining ten projects.')
check(len(first_doc.find('h1')) == 1 and len(second_doc.find('h1')) == 1, 'Both projects pages have a single semantic H1.')
check('REALIZACJE NATYWNE SEO' in first and len(first_doc.find('title')) == 1, 'The projects page retains the actual Rank Math title.')
check(first_doc.find('link', rel='canonical')[0][1]['href'] == base + '/nasze-realizacje/' and second_doc.find('link', rel='canonical')[0][1]['href'] == base + '/nasze-realizacje/page/2/', 'Native pagination retains the correct separate canonical URLs.')
check(not any(value in first + second for value in ['FORBIDDEN_EXCERPT', 'FORBIDDEN_BODY', 'FORBIDDEN_PROJECT']), 'Project cards expose no excerpt, body or excluded records.')
check(len(first_doc.classes('yn-project-image')) == 30 and len(second_doc.classes('yn-project-image')) == 10, 'Every visible project has a real resolved image.')
check(len(first_doc.classes('yn-archive-excerpt')) == 0 and len(first_doc.find('time')) == 0, 'Project presentation omits the legacy excerpt and date layout.')
_, protected = get('/collection-protected/')
check(bool(protected.classes('post-password-form')), 'Password-protected source remains protected.')
unlisted, _ = get('/collection-unlisted/')
check(not re.search(r'MASZ\s+PYTANIE', re.sub('<[^>]+>', ' ', unlisted), re.I) and 'UNLISTED SECRET BODY' in unlisted, 'A newly created public route removes the repeated block and retains unrelated content.')
print(f'Success: {assertions} real WordPress/Rank Math collection HTTP assertions passed.')
