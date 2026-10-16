#!/usr/bin/env python3
"""Analyze a Yoona WordPress diagnostics JSON offline, without rendering or requests.

The source export is private input. Public reports intentionally omit source bodies,
email addresses, phone numbers, and the original file path. No code in it is executed.
"""
from __future__ import annotations

import argparse
from collections import Counter, defaultdict
from datetime import datetime, timezone
import hashlib
from html import unescape
from html.parser import HTMLParser
import json
from pathlib import Path
import re
from urllib.parse import urljoin, urlsplit, urlunsplit

SHORTCODE = re.compile(r'''\[(?P<closing>/)?(?P<tag>[A-Za-z_][\w:-]*)(?P<attrs>(?:[^\]"']|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')*)\]''')
ATTRIBUTE = re.compile(r'''([\w-]+)\s*=\s*(?:"((?:\\.|[^"\\])*)"|'((?:\\.|[^'\\])*)'|([^\s\]]+))''')
NUMBER_PROMISE = re.compile(r"\b(\d{1,2})\s+(?:powod\w*|najlepsz\w*|pomysł\w*|sposob\w*)", re.I)
EMAIL = re.compile(r"[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}")
PHONE = re.compile(r"(?<!\w)(?:\+48[\s-]*)?(?:\d[\s-]*){9}(?!\w)")


def tidy(value: str) -> str:
    return re.sub(r"\s+", " ", value).strip()


def redact(value: str) -> str:
    return PHONE.sub("[telefon pominięty]", EMAIL.sub("[e-mail pominięty]", value))


def internal_url(value: str, base: str) -> str | None:
    try:
        parts = urlsplit(urljoin(base, unescape(value.strip())))
        if parts.scheme not in {"http", "https"} or parts.hostname not in {"yoona.pl", "www.yoona.pl"} or parts.username or parts.password:
            return None
        if parts.port not in {None, 80, 443}:
            return None
        return urlunsplit(("https", "yoona.pl", parts.path or "/", parts.query, ""))
    except ValueError:
        return None


class BodyParser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.text = []
        self.headings = []
        self.links = []
        self.paragraphs = []
        self.list_items = []
        self.images = []
        self._captures = []
        self._hidden = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag in {"script", "style", "noscript", "template"}:
            self._hidden.append(tag)
        if self._hidden:
            return
        if tag in {"h1", "h2", "h3", "h4", "h5", "h6", "p", "li"}:
            self._captures.append([tag, []])
        if tag == "a" and attrs.get("href"):
            self.links.append(attrs["href"])
        if tag == "img":
            self.images.append({"alt_attribute_present": "alt" in attrs, "alt_empty": "alt" in attrs and not tidy(attrs.get("alt") or "")})

    def handle_endtag(self, tag):
        if tag in self._hidden:
            self._hidden.remove(tag)
            return
        for index in range(len(self._captures) - 1, -1, -1):
            name, parts = self._captures[index]
            if name == tag:
                value = tidy(" ".join(parts))
                if name.startswith("h"):
                    self.headings.append({"level": int(name[1:]), "text": value})
                elif name == "p":
                    self.paragraphs.append(value)
                elif name == "li":
                    self.list_items.append(value)
                del self._captures[index]
                break

    def handle_data(self, data):
        if not self._hidden and tidy(data):
            self.text.append(tidy(data))
            for _, parts in self._captures:
                parts.append(data)


def parse_body(content: str, url: str) -> dict:
    """Extract saved editorial HTML, not Divi's final DOM or theme heading."""
    code_count = len(re.findall(r"\[et_pb_code(?:\s|\])", content))
    content_without_code = re.sub(r"\[et_pb_code(?:\s[^\]]*)?\].*?\[/et_pb_code\]", " ", content, flags=re.S)
    modules, module_headings, module_links = [], [], []
    divi_image_count = 0

    def replace_shortcode(match):
        nonlocal divi_image_count
        tag = match.group("tag")
        if not (tag.startswith("et_pb_") or tag in {"trustindex", "adres"}):
            return match.group(0)
        if match.group("closing"):
            return " "
        attrs = {item.group(1): unescape(next(v for v in item.groups()[1:] if v is not None)) for item in ATTRIBUTE.finditer(match.group("attrs"))}
        modules.append(tag)
        if tag == "et_pb_image":
            divi_image_count += 1
        for key in ("button_url", "url", "link_option_url"):
            if attrs.get(key):
                module_links.append(attrs[key])
        if tag in {"et_pb_toggle", "et_pb_accordion_item", "et_pb_blurb", "et_pb_fullwidth_header"} and attrs.get("title"):
            module_headings.append({"module": tag, "text": attrs["title"], "configured_level": attrs.get("title_level") or attrs.get("header_level")})
        return " "

    html = SHORTCODE.sub(replace_shortcode, content_without_code)
    parser = BodyParser()
    parser.feed(html)
    parser.close()
    text = tidy(" ".join(parser.text))
    links = list(dict.fromkeys(link for raw in parser.links + module_links if (link := internal_url(raw, url))))
    return {
        "text": text,
        "words": re.findall(r"\b[\w-]+\b", text, re.UNICODE),
        "headings": parser.headings,
        "paragraphs": parser.paragraphs,
        "list_items": parser.list_items,
        "internal_links": links,
        "divi_heading_candidates": module_headings,
        "divi_modules": dict(Counter(modules)),
        "divi_image_modules": divi_image_count,
        "html_images": parser.images,
        "code_modules_excluded": code_count,
    }


def metadata(page):
    value = page.get("seo_metadata", {})
    return value if isinstance(value, dict) else {}


def duplicate_groups(items, key):
    grouped = defaultdict(list)
    for item in items:
        value = tidy(str(item.get(key) or ""))
        if value:
            grouped[value.casefold()].append(item)
    return [{"value": redact(group[0][key]), "count": len(group), "urls": [item["url"] for item in group]} for group in grouped.values() if len(group) > 1]


def analyze(export: dict, source_hash: str) -> tuple[dict, list[dict]]:
    pages = export.get("pages")
    if not isinstance(pages, list) or not all(isinstance(page, dict) for page in pages):
        raise ValueError("Export must contain a list of page objects")
    rows, private_posts = [], []
    repeated = defaultdict(set)
    for page in pages:
        url = page.get("url") or urljoin(export.get("site_url", "https://yoona.pl/"), page.get("path", "/"))
        content = page.get("post_content") or ""
        parsed = parse_body(content, url)
        seo = metadata(page)
        title = seo.get("rank_math_title") or ""
        description = seo.get("rank_math_description") or ""
        promises = [{"count": int(match.group(1)), "phrase": match.group(0)} for match in NUMBER_PROMISE.finditer(title)]
        numbered_headings = [item for item in parsed["headings"] if re.match(r"^\s*\d+[.)]\s", item["text"])]
        phrase_counts = {}
        folded = parsed["text"].casefold()
        for phrase in ("agencja reklamowa", "olsztyn", "reklama olsztyn", "oklejanie samochodów", "neony", "zaufaj nam", "masz pytanie"):
            phrase_counts[phrase] = len(re.findall(r"(?<!\w)" + re.escape(phrase) + r"(?!\w)", folded))
        for paragraph in parsed["paragraphs"]:
            if len(paragraph) >= 80:
                repeated[paragraph.casefold()].add(url)
        row = {
            "post_id": page.get("post_id"), "post_type": page.get("post_type"), "url": url,
            "path": page.get("path"), "post_title": redact(page.get("post_title") or ""),
            "rank_math_title_stored": redact(title), "rank_math_description_stored": redact(description),
            "rank_math_robots_stored": seo.get("rank_math_robots"),
            "rank_math_canonical_stored": seo.get("rank_math_canonical_url"),
            "yoast_title_stored_present": bool(seo.get("_yoast_wpseo_title")),
            "yoast_description_stored_present": bool(seo.get("_yoast_wpseo_metadesc")),
            "word_count_saved_body": len(parsed["words"]),
            "headings_saved_body": [{"level": item["level"], "text": redact(item["text"])} for item in parsed["headings"]],
            "h1_count_saved_body": sum(item["level"] == 1 for item in parsed["headings"]),
            "empty_h1_count_saved_body": sum(item["level"] == 1 and not item["text"] for item in parsed["headings"]),
            "h2_count_saved_body": sum(item["level"] == 2 for item in parsed["headings"]),
            "divi_heading_candidates": [{**item, "text": redact(item["text"])} for item in parsed["divi_heading_candidates"]],
            "divi_shortcode_present": bool(re.search(r"\[et_pb_", content)),
            "divi_image_modules": parsed["divi_image_modules"],
            "html_images": parsed["html_images"], "code_modules_excluded": parsed["code_modules_excluded"],
            "numeric_title_promises": promises,
            "numbered_headings_saved_body": len(numbered_headings),
            "list_items_saved_body": len(parsed["list_items"]),
            "numeric_promise_review": bool(promises),
            "numeric_promise_without_numbered_headings_or_li": bool(promises and not numbered_headings and not parsed["list_items"]),
            "internal_links_saved_body": parsed["internal_links"],
            "phrase_counts_saved_body": phrase_counts,
        }
        rows.append(row)
        if page.get("post_type") == "post":
            private_posts.append({
                "post_id": page.get("post_id"), "post_type": "post", "url": url, "path": page.get("path"),
                "post_title": page.get("post_title"), "content_sha256": page.get("content_sha256"),
                "seo_metadata": seo, "word_count": len(parsed["words"]),
                "headings": parsed["headings"], "text_excerpt": redact(parsed["text"][:3600]),
                "internal_links": parsed["internal_links"],
            })
    known = {internal_url(item["url"], "https://yoona.pl/") for item in rows}
    for row in rows:
        row["internal_targets_not_in_export_unverified"] = [link for link in row["internal_links_saved_body"] if link not in known and "/wp-content/" not in link]
    counts = Counter(row["post_type"] for row in rows)
    meta_counts = {}
    for kind in ("all", "page", "post"):
        subset = rows if kind == "all" else [row for row in rows if row["post_type"] == kind]
        meta_counts[kind] = {
            "items": len(subset),
            "rank_math_title_stored": sum(bool(row["rank_math_title_stored"]) for row in subset),
            "rank_math_description_stored": sum(bool(row["rank_math_description_stored"]) for row in subset),
            "rank_math_title_not_stored": sum(not row["rank_math_title_stored"] for row in subset),
            "rank_math_description_not_stored": sum(not row["rank_math_description_stored"] for row in subset),
            "no_h1_in_saved_body": sum(row["h1_count_saved_body"] == 0 for row in subset),
            "multiple_h1_in_saved_body": sum(row["h1_count_saved_body"] > 1 for row in subset),
            "empty_h1_in_saved_body": sum(row["empty_h1_count_saved_body"] > 0 for row in subset),
            "no_internal_link_in_saved_body": sum(not row["internal_links_saved_body"] for row in subset),
            "numeric_title_promises": sum(row["numeric_promise_review"] for row in subset),
            "numeric_title_without_numbered_headings_or_li": sum(row["numeric_promise_without_numbered_headings_or_li"] for row in subset),
        }
    frequent = [{"paragraph": redact(text), "count": len(urls), "urls": sorted(urls)} for text, urls in repeated.items() if len(urls) >= 10]
    frequent.sort(key=lambda item: (-item["count"], item["paragraph"]))
    report = {
        "schema_version": 1, "generated_at": datetime.now(timezone.utc).isoformat(),
        "source": {"exported_at": export.get("exported_at"), "sha256": source_hash, "truncated": export.get("truncated"), "items": len(rows), "types": dict(counts)},
        "scope": "Offline analiza zapisanych post_content i wybranych pól metadanych z eksportu. Rank Math potwierdzony przez właściciela; HTML wynikowy, globalne szablony, ustawienia Rank Math, menu, widgety i media alt nie zostały wyeksportowane.",
        "configuration_observed": export.get("configuration", {}),
        "counts": meta_counts,
        "divi_shortcode_items": sum(row["divi_shortcode_present"] for row in rows),
        "divi_builder_marker_items": sum("Divi" in (page.get("builders") or []) for page in pages),
        "duplicate_rank_math_titles_stored": duplicate_groups(rows, "rank_math_title_stored"),
        "duplicate_rank_math_descriptions_stored": duplicate_groups(rows, "rank_math_description_stored"),
        "duplicate_post_titles": duplicate_groups(rows, "post_title"),
        "repeated_saved_paragraphs_at_least_10_urls": frequent[:20],
        "observations_requiring_editorial_review": editorial_examples(rows),
        "unknowns": [
            "Nie sprawdzono końcowych tagów title, meta description, canonical i robots w odpowiedzi HTML; brak pola w bazie może korzystać z szablonu Rank Math.",
            "Nie sprawdzono kodów HTTP, przekierowań, robots.txt, map XML, statusu indeksacji i poprawności końcowego schema/hreflang/OG.",
            "Nie zmierzono Core Web Vitals, Lighthouse, czasu ładowania, rozmiaru zasobów ani działania mobilnego; Divi i rozmiar eksportu nie są pomiarem szybkości.",
            "Nie uzyskano Google Search Console ani Analytics; brak danych o pozycjach, kliknięciach, wyświetleniach, CTR, ruchu i konwersji.",
            "H1/H2 i linki dotyczą tylko zapisanej treści; motyw/Divi może dodać nagłówki, menu, stopkę i linki. Brak linku w post_content nie dowodzi osierocenia strony.",
            "Nie oceniono alt obrazów generowanych przez media WordPress/Divi. Wyeksportowane identyfikatory galerii nie zawierają opisów mediów.",
            "Tekst shortcode modułów dynamicznych/kodu jest wyłączony z liczby słów; nie uruchamiano PHP, shortcode, HTML ani JavaScript.",
            "Nie wykonywano oceny prawnej dokumentów ani weryfikacji cen, terminów i danych kontaktowych; raport pomija telefony i e-maile.",
        ],
        "items": rows,
    }
    return report, private_posts


def editorial_examples(rows):
    by_id = {row["post_id"]: row for row in rows}
    examples = []
    for post_id, issue, action in (
        (189, "Tytuł kontaktu obiecuje 5 powodów współpracy, podczas gdy zapisany korpus jest stroną kontaktową.", "Ustawić opisowy tytuł kontaktu i zaproszenie do przesłania danych potrzebnych do wyceny."),
        (5670, "Opis tablic unijnych mówi o wzmocnieniu marki. Treść strony opisuje obowiązki informacyjne projektów dofinansowanych i wymagania programu.", "Opisać wykonanie tablic według dokumentacji konkretnego projektu; bez obietnicy uniwersalnej zgodności prawnej."),
        (5575, "W źródłowej treści strony CNC występują nagłówki dotyczące neonów; to widoczny ślad kopiowania szablonu.", "Zastąpić te nagłówki tematem CNC w tych samych modułach, zachowując strukturę Divi."),
        (490, "Zapisany title i description zawierają literówkę „rekalmy”.", "Usunąć literówkę i obietnicę liczbową; nazwać reklamę na rusztowaniach."),
        (153, "Title i description powtarzają dokładną frazę „litery 3d olsztyn” w nienaturalnej odmianie.", "Zastosować poprawną polszczyznę i jedną, naturalną lokalizację usługi."),
        (5501, "Strona obiecuje informację o cenie oklejania; nie ma podstaw do dodawania zmyślonego cennika.", "Wyjaśnić czynniki wyceny i dane potrzebne do zapytania; cenę podać dopiero po potwierdzeniu przez firmę."),
    ):
        if post_id in by_id:
            row = by_id[post_id]
            examples.append({"post_id": post_id, "url": row["url"], "observed_stored_title": row["rank_math_title_stored"], "observed_stored_description": row["rank_math_description_stored"], "issue": issue, "action": action})
    return examples


def markdown(report):
    c = report["counts"]
    lines = [
        "# Audyt SEO i treści yoona.pl", "",
        "Najpilniejsza poprawka to dopasowanie tytułów i opisów do tego, co użytkownik rzeczywiście znajdzie na stronie. Eksport pokazuje masowo stosowane obietnice typu „5 powodów” i „10 najlepszych”, sztuczne powtarzanie fraz oraz teksty skopiowane z innych usług.", "",
        f"Źródło: eksport WordPress z {report['source']['exported_at']}; przeanalizowano wszystkie {report['source']['items']} elementów: {report['source']['types'].get('page', 0)} stron i {report['source']['types'].get('post', 0)} wpisów. Eksport oznaczono jako nieucięty: {report['source']['truncated'] is False}. Właściciel potwierdził korzystanie z Rank Math.", "",
        "To audyt danych i treści z eksportu, nie pomiar działającej witryny. Ograniczenie dostępu do strony w środowisku uniemożliwiło kontrolę odpowiedzi HTTP i wynikowego HTML. Nie przypisujemy tej blokady witrynie.", "",
        "## Co potwierdzono w danych", "",
        "| Zapisane pole / obserwacja | Wszystkie | Strony | Wpisy |", "| --- | ---: | ---: | ---: |",
    ]
    for key, label in (
        ("items", "Liczba elementów"),
        ("rank_math_title_stored", "Własny title Rank Math zapisany w eksporcie"),
        ("rank_math_title_not_stored", "Brak zapisanego własnego title Rank Math"),
        ("rank_math_description_stored", "Własny opis Rank Math zapisany w eksporcie"),
        ("rank_math_description_not_stored", "Brak zapisanego własnego opisu Rank Math"),
        ("numeric_title_promises", "Zapisany title z obietnicą liczbową"),
        ("no_h1_in_saved_body", "Brak H1 w samym zapisanym korpusie"),
        ("multiple_h1_in_saved_body", "Więcej niż jeden H1 w zapisanym korpusie"),
        ("no_internal_link_in_saved_body", "Brak linku wewnętrznego w zapisanym korpusie"),
    ):
        lines.append(f"| {label} | {c['all'][key]} | {c['page'][key]} | {c['post'][key]} |")
    lines += ["", "Brak zapisanego własnego title/opisu nie dowodzi braku tagu na stronie: Rank Math może używać szablonu. Nagłówki i linki wyżej dotyczą samego `post_content`; nie obejmują nagłówka strony, menu, stopki ani dynamicznych modułów. Nie nazywamy tych wpisów osieroconymi bez crawla.", "", f"Znacznik buildera Divi występuje w {report['divi_builder_marker_items']} elementach, a shortcode et_pb w {report['divi_shortcode_items']}. Zapisane tytuły Rank Math: {len(report['duplicate_rank_math_titles_stored'])} grup ścisłych duplikatów; opisy: {len(report['duplicate_rank_math_descriptions_stored'])} grup. Porównanie ignoruje wielkość liter i wielokrotne spacje, nie porównuje podobieństwa znaczeniowego.", "", "## P1 — poprawić w pierwszej kolejności", "", "### 1. Usunąć obietnice list i dopasować metadane do intencji", "", f"W {c['all']['numeric_title_promises']} zapisanych title pojawiają się wzorce „N powodów / najlepszych / pomysłów / sposobów”. W {c['all']['numeric_title_without_numbered_headings_or_li']} z nich w zapisanym korpusie nie znaleziono ani ponumerowanych nagłówków, ani elementów listy li. To sygnał do przeglądu, nie dowód spamu ani kary Google; listy mogą też znajdować się w modułach dynamicznych lub zwykłych akapitach. Dla stron ofertowych bez takiej listy lepszy jest konkretny tytuł usługi, lokalizacja i marka.", ""]
    for example in report["observations_requiring_editorial_review"]:
        lines += [f"- **{example['url']}** — {example['issue']} {example['action']}", f"  - Zapisany title: „{example['observed_stored_title'] or '[nie zapisano]'}”."]
    lines += ["", "### 2. Naprawić język i pozostałości szablonu w głównych usługach", "", "Na stronie liter 3D zapisano: „Litery 3D Olsztyn – produkcja i montaż liter 3d olsztyn”. W opisie tablic występuje końcówka „tablice reklamowe olsztyn szyldy”, a w opisie autobusów „z oklejanie autobusów olsztyn”. To konkretne błędy naturalności języka. Zalecenie: pisać o produkcie, zakresie prac i kolejnym kroku klienta, bez doklejania nieodmienionej frazy.", "", "Na `/frezowanie-cnc/` należy przejrzeć wszystkie podpisy odziedziczone po neonach. Na `/tablice-unijne/` opisać przeznaczenie tablic informacyjnych oraz pracę według wytycznych beneficjenta. Nie zastępować tych informacji ogólnym tekstem o budowaniu marki.", "", "### 3. Zachować układ Divi i sprawdzić wdrożenie na kopii", "", "Teksty edytować w konkretnych modułach. Zastąpienie całego post_content zwykłym HTML usunęłoby konfigurację układu, galerie i przyciski. Najpierw kopia treści/metadanych, podgląd zmian, potem zapis. Nie zmieniać adresów URL automatycznie, np. `/reklama-na-rusztowaniu-2/`, bez planu przekierowań.", "", "## P2 — dopracować strukturę i portfolio", "", "### 4. Uporządkować własne tytuły i opisy, zwłaszcza realizacji", "", f"{c['post']['rank_math_title_not_stored']} z {c['post']['items']} realizacji nie ma własnego title Rank Math w eksporcie; tyle samo nie ma własnego opisu. Najpierw sprawdzić fallback aktualnego HTML. Przygotować konkretne metadane o wykonanej realizacji, kliencie i miejscowości na podstawie treści. Nie dopisywać technologii, parametrów ani wyników sprzedażowych, których źródło nie potwierdza.", "", "### 5. Dodać użyteczne przejścia z realizacji do usługi", "", f"W {c['post']['no_internal_link_in_saved_body']} zapisanych korpusach wpisów brak wewnętrznego linku. To okazja do ręcznego powiązania realizacji z odpowiednią usługą, np. oklejaniem samochodów, literami 3D lub kasetonami. Menu i motyw mogą już zapewniać część tych połączeń. Linków spoza eksportu nie oznaczono jako uszkodzone: nie wykonano pomiaru HTTP.", "", "### 6. Ograniczyć powtarzalny tekst i opisać proces", "", "Zamiast kolejnych ogólnych zdań o „wysokiej jakości” podać informacje przydatne przy zamówieniu: zastosowanie, możliwe materiały, przygotowanie plików, zakres montażu, sposób wyceny. W realizacjach opisać faktycznie wykonany zakres. Nie ma uniwersalnego wymogu minimalnej liczby słów.", ""]
    for para in report["repeated_saved_paragraphs_at_least_10_urls"][:4]:
        lines.append(f"- Ten sam akapit występuje w {para['count']} korpusach, np. {para['urls'][0]}: „{para['paragraph'][:260]}{'…' if len(para['paragraph']) > 260 else ''}”. Powtarzalna stopka lub CTA nie jest sama w sobie błędem; nie powinna zastępować opisu usługi.")
    lines += ["", "### Nagłówki: uprościć hierarchię i usunąć puste elementy", "", f"W {c['all']['multiple_h1_in_saved_body']} zapisanych korpusach występuje więcej niż jeden H1, w tym w {c['page']['multiple_h1_in_saved_body']} stronach. Na stronie głównej zapisano H1 „— agencja reklamowa olsztyn | ostróda” oraz drugi H1 „Agencja reklamowa Olsztyn – Producent reklam dla firm”. Warto pozostawić opisowy nagłówek główny, a etykietę obszaru działania nadać zwykłym tekstem. Na `/frezowanie-cnc/` i `/tablice-unijne/` występują też puste H1. Sama liczba H1 nie jest podstawą do twierdzenia o karze Google; celem jest czytelny dokument i dostępność. Po zmianie sprawdzić DOM wygenerowany przez Divi oraz wygląd tekstu na telefonie.", ""]
    lines += ["", "## P3 — kontrola techniczna po uzyskaniu dostępu", "", "### 7. Sprawdzić finalny HTML, indeksację i wyniki", "", "Po wdrożeniu sprawdzić w przeglądarce title/opis/canonical/robots, strukturę nagłówków wygenerowaną przez Divi i mobile. Zweryfikować robots.txt, mapę XML w Search Console, przekierowania i linki. Dopiero wtedy zmierzyć Lighthouse/Core Web Vitals i ustalić działania wydajnościowe. Skuteczność treści oceniać w Search Console i zapytaniach klientów; ten eksport nie zawiera danych o ruchu ani pozycjach.", "", "### 8. Ustawienia — co wiemy i czego nie zmieniać automatycznie", ""]
    config = report["configuration_observed"]
    lines += [f"- `blog_public`: `{config.get('blog_public')}` — WordPress w chwili eksportu nie miał włączonej globalnej prośby o nieindeksowanie. To nie potwierdza indeksacji Google ani finalnych nagłówków robots.", f"- Linki bezpośrednie: `{config.get('permalink_structure')}`; strona główna ma ID `{config.get('page_on_front')}`, strona wpisów ID `{config.get('page_for_posts')}`. Strona wpisów może korzystać z szablonu archiwum zamiast własnego korpusu.", "- Obecność starych pól Yoast oraz nowych pól Rank Math w bazie nie dowodzi jednoczesnego działania obu wtyczek. Właściciel wskazał Rank Math; nie usuwać dawnych pól bez osobnej decyzji.", "- Nie dodawać globalnego canonical, noindex ani drugiego generatora schema bez przeglądu aktualnego HTML i ustawień Rank Math.", "", "## Ograniczenia i niewykonane pomiary", ""]
    lines += [f"- {item}" for item in report["unknowns"]]
    lines += ["", "## Artefakty i kontrola jakości", "", "`export-audit.json` zawiera wyniki dla wszystkich 200 elementów, policzone pola, nagłówki zapisanej treści i listę linków bez pomiaru HTTP. Raport celowo nie zawiera surowego eksportu, całych tekstów, telefonów ani adresów e-mail. Surowy eksport należy przechowywać prywatnie.", "", "Po imporcie: otworzyć stronę główną, Kontakt, ofertę i 2–3 realizacje na komputerze i telefonie; sprawdzić Divi, przyciski, formularze, galerie i faktyczne metadane. W razie błędu przywrócić zapisane kopie. Żadnego wzrostu pozycji ani ruchu nie można zagwarantować na podstawie eksportu.", ""]
    return "\n".join(lines)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("export", type=Path)
    parser.add_argument("--output-dir", type=Path, default=Path("audit"))
    parser.add_argument("--private-posts-summary", type=Path, default=Path("/tmp/yoona-posts-summary.json"))
    args = parser.parse_args()
    raw = args.export.read_bytes()
    report, private = analyze(json.loads(raw), hashlib.sha256(raw).hexdigest())
    args.output_dir.mkdir(parents=True, exist_ok=True)
    (args.output_dir / "export-audit.json").write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    (args.output_dir / "AUDYT-SEO-YOONA.md").write_text(markdown(report), encoding="utf-8")
    args.private_posts_summary.parent.mkdir(parents=True, exist_ok=True)
    args.private_posts_summary.write_text(json.dumps(private, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    args.private_posts_summary.chmod(0o600)
    print(json.dumps({"items": report["source"]["items"], "counts": report["counts"], "output_dir": str(args.output_dir), "private_posts": len(private)}, ensure_ascii=False))


if __name__ == "__main__":
    main()
