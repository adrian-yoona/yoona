#!/usr/bin/env python3
"""Analyze private GA4/GSC/brand exports locally; never upload or extract ZIPs.

Only the Python standard library is required. Inputs and output must remain
outside any Git working tree. The output contains commercially sensitive data.
"""
from __future__ import annotations

import argparse
import csv
import datetime as dt
import hashlib
import io
import json
from pathlib import Path
import re
import zipfile


PRIORITIES = (
    ("agencja reklamowa olsztyn", "/", "P1", "Oferta produkcyjna i dwa punkty kontaktu"),
    ("agencja reklamowa ostróda", "/", "P1", "Równorzędny kontakt z Ostródą; bez kopii strony miasta"),
    ("reklama świetlna olsztyn", "/reklamy-swietlne/", "P1", "Porównanie technologii i zdjęcia po zmroku"),
    ("kasetony reklamowe olsztyn", "/kasetony-swietlne/", "P1", "Lico, obudowa, podświetlenie, warunki montażu"),
    ("oklejanie samochodów olsztyn", "/oklejanie-samochodow/", "P1", "Rozdzielenie reklamy na aucie od zmiany koloru"),
    ("oklejanie szyb olsztyn", "/oklejanie-szyb/", "P1", "Folia reklamowa, szroniona i OWV według potrzeb"),
    ("banery reklamowe olsztyn", "/banery/", "P2", "Materiał, wykończenie, plik, wymiary i montaż"),
    ("litery 3d olsztyn", "/litery-3d/", "P2", "Podświetlenie, materiał i rzeczywiste realizacje"),
    ("frezowanie cnc olsztyn", "/frezowanie-cnc/", "P2", "Plexi/dibond, dokumentacja i ocena wykonalności"),
    ("markizy olsztyn", "/markizy-z-logo/", "P2", "Ścienne i DUO, znakowanie, klienci indywidualni i gastronomia"),
)
CLICK, IMPRESSION, POSITION = "Kliknięcia", "Wyświetlenia", "Pozycja"
MAX_CSV_BYTES = 8 * 1024 * 1024


def private_path(path: Path, label: str) -> Path:
    path = path.expanduser().resolve()
    for parent in (path, *path.parents):
        metadata = parent / ".git"
        # Some managed workspaces expose an empty, non-repository .git mount.
        if metadata.is_file() or (metadata.is_dir() and any(metadata.iterdir())):
            raise ValueError(f"{label} must be outside a Git checkout: {path}")
    return path


def csv_rows(data: bytes) -> list[dict[str, str]]:
    if len(data) > MAX_CSV_BYTES:
        raise ValueError("CSV exceeds the local analysis size limit")
    return list(csv.DictReader(io.StringIO(data.decode("utf-8-sig"))))


def zip_tables(path: Path) -> dict[str, list[dict[str, str]]]:
    result = {}
    with zipfile.ZipFile(path) as archive:
        if sum(i.file_size for i in archive.infolist()) > MAX_CSV_BYTES * 4:
            raise ValueError("ZIP exceeds the local analysis size limit")
        for item in archive.infolist():
            # ZIP members are read directly. No member name becomes a file path.
            if not item.is_dir() and item.filename.lower().endswith(".csv"):
                if item.file_size > MAX_CSV_BYTES:
                    raise ValueError("ZIP member exceeds the local analysis size limit")
                if item.filename in result:
                    raise ValueError("Duplicate CSV member name")
                result[item.filename] = csv_rows(archive.read(item))
    return result


def totals(rows: list[dict[str, str]]) -> dict:
    clicks = sum(int(row[CLICK]) for row in rows)
    impressions = sum(int(row[IMPRESSION]) for row in rows)
    weighted = sum(float(row[POSITION]) * int(row[IMPRESSION]) for row in rows)
    return {"rows": len(rows), "clicks": clicks, "impressions": impressions,
            "ctr_percent": 100 * clicks / impressions if impressions else 0,
            "position_impression_weighted_approx": weighted / impressions if impressions else None}


def typed_metric(row: dict[str, str]) -> dict:
    return {"clicks": int(row[CLICK]), "impressions": int(row[IMPRESSION]),
            "ctr_percent": 100 * int(row[CLICK]) / int(row[IMPRESSION]) if int(row[IMPRESSION]) else 0,
            "position": float(row[POSITION])}


def ga4_sections(path: Path) -> dict:
    text = path.read_text(encoding="utf-8-sig")
    sections = []
    periods = set()
    for block in re.split(r"\n\s*\n", text):
        start = re.search(r"# Data rozpoczęcia:\s*(\d{8})", block)
        end = re.search(r"# Data zakończenia:\s*(\d{8})", block)
        if start and end:
            periods.add((start[1], end[1]))
        rows = [row for row in csv.reader(io.StringIO(block)) if row and not row[0].startswith("#")]
        if rows:
            header, *values = rows
            if any(len(row) != len(header) for row in values):
                raise ValueError("GA4 section has inconsistent field counts")
            sections.append({"headers": header, "rows": [dict(zip(header, row)) for row in values]})
    lookup = {section["headers"][0]: section["rows"] for section in sections}
    expected = ("Aktywni użytkownicy", "Tytuł strony i klasa ekranu", "Sesja – źródło/medium")
    if any(key not in lookup for key in expected):
        raise ValueError("Unsupported GA4 overview export: required sections are missing")
    summary = lookup["Aktywni użytkownicy"][0]
    pages = lookup["Tytuł strony i klasa ekranu"]
    sessions = lookup["Sesja – źródło/medium"]
    days = lookup.get("N-ty dzień", [])
    start_date = dt.datetime.strptime(sorted(periods)[0][0], "%Y%m%d").date() if len(periods) == 1 else None
    spikes = sorted(days, key=lambda row: int(row.get("new", 0)), reverse=True)[:5]
    for row in spikes:
        if start_date:
            row["date"] = (start_date + dt.timedelta(days=int(row["N-ty dzień"]))).isoformat()
    return {
        "periods": [list(period) for period in sorted(periods)],
        "summary": {"active_users": int(summary["Aktywni użytkownicy"]),
                    "new_users": int(summary["Nowi użytkownicy"]),
                    "average_engagement_seconds": float(summary["Średni czas zaangażowania aktywnego użytkownika online"]),
                    "events": int(summary["Liczba zdarzeń"])},
        "page_title_rows": len(pages),
        "sum_exported_page_views": sum(int(row["Wyświetlenia"]) for row in pages),
        "session_source_rows": sessions,
        "first_user_source_rows": lookup.get("Pierwsze źródło/medium użytkownika", []),
        "key_events_by_platform": lookup.get("Platforma", []),
        "suspicious_page_titles": [row for row in pages if "trafficheap.cc" in row["Tytuł strony i klasa ekranu"].casefold()],
        "suspicious_referral_sessions": [row for row in sessions if "trafficheap.cc" in row["Sesja – źródło/medium"].casefold()],
        "highest_new_user_days": spikes,
        "top_page_titles": pages[:12],
        "top_cities": lookup.get("Miasto", [])[:12],
    }


def analyze(gsc_path: Path, ga_path: Path, brand_path: Path, bundle_path: Path | None) -> dict:
    tables = zip_tables(gsc_path)
    required = {"Wykres.csv", "Zapytania.csv", "Strony.csv", "Urządzenia.csv", "Filtry.csv"}
    if not required.issubset(tables):
        raise ValueError("Unsupported Search Console export: required tables are missing")
    days = tables["Wykres.csv"]
    for row in days:
        dt.date.fromisoformat(row["Data"])
    last = max(dt.date.fromisoformat(row["Data"]) for row in days)
    queries = tables["Zapytania.csv"]
    lookup = {row["Najczęstsze zapytania"].casefold(): row for row in queries}
    # "yoona" is also a personal name. This is lexical grouping, not inferred intent.
    brand = [row for row in queries if re.search(r"\byoona\b", row["Najczęstsze zapytania"], re.I)]
    ambiguous = [row for row in queries if row not in brand and re.search(r"yoon|yona", row["Najczęstsze zapytania"], re.I)]
    other = [row for row in queries if row not in brand and row not in ambiguous]
    bundle = json.loads(bundle_path.read_text()) if bundle_path else {}
    content = {page["path"]: page for page in bundle.get("pages", [])}
    priorities = []
    for query, path, phase, action in PRIORITIES:
        row = lookup.get(query)
        priorities.append({"query": query, "path": path, "phase": phase, "action": action,
                           "observed_in_query_export": row is not None,
                           "metrics": typed_metric(row) if row else None,
                           "prepared_seo_title": content.get(path, {}).get("seo_title"),
                           "prepared_body_patch_count": len(content.get(path, {}).get("divi_text", [])) if path in content else None})
    candidates = [row for row in other if int(row[IMPRESSION]) >= 20 and 5 <= float(row[POSITION]) <= 30
                  and int(row[CLICK]) / int(row[IMPRESSION]) <= .01
                  and any(word in row["Najczęstsze zapytania"] for word in ("olsztyn", "ostróda", "ostroda"))]
    brand_tables = zip_tables(brand_path)
    brand_data = {}
    for name, rows in brand_tables.items():
        if name == "Lokalizacja.csv":
            brand_data["country_reach_and_frequency"] = rows
        elif name.startswith("Urządzenia/") and "Unikalni" in name:
            brand_data["device_unique_reach_not_additive"] = rows
        elif name.startswith("Urządzenia/"):
            brand_data["device_average_frequency"] = rows
        else:
            brand_data["weekly_frequency_buckets"] = rows
    return {
        "schema_version": 1,
        "privacy": "PRIVATE — local exports analysis; do not commit this output or the inputs.",
        "input_sha256": {"gsc_zip": hashlib.sha256(gsc_path.read_bytes()).hexdigest(),
                         "ga4_csv": hashlib.sha256(ga_path.read_bytes()).hexdigest(),
                         "brand_zip": hashlib.sha256(brand_path.read_bytes()).hexdigest()},
        "gsc": {
            "filters": tables["Filtry.csv"],
            "actual_chart_period": [min(row["Data"] for row in days), last.isoformat()],
            "chart_totals": totals(days),
            "query_table_totals_partial": totals(queries),
            "query_table_click_coverage_percent": 100 * totals(queries)["clicks"] / totals(days)["clicks"] if totals(days)["clicks"] else 0,
            "url_table_sums_not_property_totals": totals(tables["Strony.csv"]),
            "brand_token_observed_queries_only": totals(brand),
            "ambiguous_brand_spelling_observed_queries_only": totals(ambiguous),
            "other_observed_queries_only": totals(other),
            "devices": [{"device": row["Urządzenie"], **typed_metric(row)} for row in tables["Urządzenia.csv"]],
            "last_28_day_period": [(last - dt.timedelta(days=27)).isoformat(), last.isoformat()],
            "previous_28_day_period": [(last - dt.timedelta(days=55)).isoformat(), (last - dt.timedelta(days=28)).isoformat()],
            "last_28_days": totals([row for row in days if last - dt.timedelta(days=27) <= dt.date.fromisoformat(row["Data"])]),
            "previous_28_days": totals([row for row in days if last - dt.timedelta(days=55) <= dt.date.fromisoformat(row["Data"]) <= last - dt.timedelta(days=28)]),
            "priority_queries": priorities,
            "additional_candidates": [{"query": row["Najczęstsze zapytania"], **typed_metric(row)} for row in sorted(candidates, key=lambda row: -int(row[IMPRESSION]))[:30]],
            "landing_pages": [{"url": row["Najpopularniejsze strony"], **typed_metric(row)} for row in tables["Strony.csv"]],
        },
        "ga4": ga4_sections(ga_path),
        "brand_reach_export": brand_data,
        "limitations": [
            "No account changes were made; this is a local export analysis, not live Search Console, GA4 or Ads access.",
            "Daily chart totals are property aggregates. URL totals are a different aggregation and must not be added to them.",
            "Query rows omit anonymized/other rows; unclassified clicks must not be called non-brand.",
            "CTR is clicks / impressions; average positions are impression weighted from rounded source values and are approximate.",
            "GA4 user dimensions are not additive. First user acquisition and session acquisition have different scopes.",
            "Page titles can change for a single URL. Multiple titles are not evidence of duplicate URLs.",
            "The brand export has no campaign IDs, costs, clicks, lead definitions, revenue or attributable conversions; it cannot support CPL/CPA/ROAS.",
            "Unique reach across devices overlaps; do not sum device reach into the country reach or estimate official impressions from rounded values.",
            "Key event count is not a verified lead count. Event names and marking settings are absent.",
            "Suspicious referral/title indicates a measurement quality issue; the export does not prove a site compromise.",
            "No robots, canonical, sitemap, indexing, live performance, competitors or business profile changes were verified by these exports.",
        ],
    }


def report_markdown(data: dict) -> str:
    gsc, ga = data["gsc"], data["ga4"]
    total = gsc["chart_totals"]
    lines = ["# Prywatna analiza eksportów YOONA", "", "Nie publikować tego raportu ani plików wejściowych w repozytorium.", "",
             "## Wnioski", "",
             "- Najpierw uporządkować pomiar: podejrzany referral, definicje najważniejszych zdarzeń, duplikaty tagów i rozróżnienie kontaktu od leada.",
             "- Wzmocnić istniejące strony usług; lista priorytetów łączy faktyczne zapytania z poleceniem właściciela dotyczącym CNC i markiz.",
             "- Nie tworzyć seryjnych kopii pod miasta i województwa. Olsztyn i Ostróda mają być widoczne w równorzędnych kontaktach i prawdziwych realizacjach.",
             "- Okres eksportu należy porównywać z datą wdrożenia. Dane sprzed aktualizacji nie mierzą jej skutków.", "",
             "## Search Console — rzeczywisty okres i zakres", "",
             f"Zakres wykresu: **{gsc['actual_chart_period'][0]}–{gsc['actual_chart_period'][1]}**, {total['rows']} dni. Nazwa ZIP-a jest datą eksportu, nie ostatnim dniem danych.", "",
             f"Wykres: **{total['clicks']} kliknięć**, **{total['impressions']} wyświetleń**, CTR **{total['ctr_percent']:.2f}%**, średnia pozycja około **{total['position_impression_weighted_approx']:.2f}**.", "",
             f"Tabela zapytań: {gsc['query_table_totals_partial']['rows']} wierszy, {gsc['query_table_totals_partial']['clicks']} kliknięcia; pokrywa tylko {gsc['query_table_click_coverage_percent']:.2f}% kliknięć wykresu. Pozostałe kliknięcia są niezaklasyfikowane.",
             f"W widocznej części tabeli token „yoona” występuje przy {gsc['brand_token_observed_queries_only']['clicks']} kliknięciach; pozostałe jednoznacznie inne zapytania mają {gsc['other_observed_queries_only']['clicks']} kliknięcia. Nie jest to podział całego ruchu na markowy i niemarkowy; samo „yoona” może też oznaczać osobę.",
             "Tabela stron jest agregowana po URL. Jej sumy nie są sumami całej usługi: kilka URL może wystąpić przy tym samym wyświetleniu wyników.", "",
             "### Urządzenia", "", "| Urządzenie | Kliknięcia | Wyświetlenia | CTR | Śr. pozycja |", "|---|---:|---:|---:|---:|"]
    for row in gsc["devices"]:
        lines.append(f"| {row['device']} | {row['clicks']} | {row['impressions']} | {row['ctr_percent']:.2f}% | {row['position']:.2f} |")
    lines += ["", "Mobilny CTR jest wyższy, ale pozycje i zestaw zapytań są inne. Samo porównanie nie dowodzi, że wygląd desktopu powoduje słabszy CTR.", "",
              "### Ostatnie i poprzednie 28 dni", "", "| Okres względny wobec końca eksportu | Kliknięcia | Wyświetlenia | CTR | Śr. pozycja |", "|---|---:|---:|---:|---:|"]
    for key, label in (("previous_28_days", "Poprzednie 28 dni"), ("last_28_days", "Ostatnie 28 dni")):
        row = gsc[key]
        period = gsc['previous_28_day_period' if key == 'previous_28_days' else 'last_28_day_period']
        position = f"{row['position_impression_weighted_approx']:.2f}" if row['position_impression_weighted_approx'] is not None else "—"
        lines.append(f"| {label}: {'–'.join(period)} | {row['clicks']} | {row['impressions']} | {row['ctr_percent']:.2f}% | {position} |")
    lines += ["", "Porównanie opisowe, bez przypisania przyczyny. Datę końca eksportu należy zestawić z datą wdrożenia.", "",
              "### Dziesięć priorytetowych fraz", "", "P1/P2 oznacza kolejność prac redakcyjnych, a nie prognozę sprzedaży lub pozycję w Google.", "",
              "| Priorytet | Zapytanie | Kliknięcia | Wyświetlenia | CTR | Śr. poz. | Strona docelowa |", "|---|---|---:|---:|---:|---:|---|"]
    for item in gsc["priority_queries"]:
        row = item["metrics"]
        if row:
            lines.append(f"| {item['phase']} | {item['query']} | {row['clicks']} | {row['impressions']} | {row['ctr_percent']:.2f}% | {row['position']:.2f} | {item['path']} |")
        else:
            lines.append(f"| {item['phase']} | {item['query']} | — | — | — | — | {item['path']} |")
    lines += ["", "Mapowanie to zamierzona strona usługi; oddzielne tabele zapytań i URL nie dowodzą, który URL wyświetlał się dla danej frazy. Do diagnozy kanibalizacji potrzebny eksport zapytanie × strona.", "",
              "### Strony docelowe", "", "| URL | Kliknięcia | Wyświetlenia | CTR | Śr. poz. |", "|---|---:|---:|---:|---:|"]
    wanted = {item["path"] for item in gsc["priority_queries"]} | {"/beczki-reklamowe/", "/plotki-ogrodki-piwne/", "/reklama-zewnetrzna/"}
    for row in gsc["landing_pages"]:
        if row["url"].removeprefix("https://yoona.pl") in wanted:
            lines.append(f"| {row['url']} | {row['clicks']} | {row['impressions']} | {row['ctr_percent']:.2f}% | {row['position']:.2f} |")
    lines += ["", "Beczki i płotki już zdobywają wejścia — zachować URL, zdjęcia i istotne informacje. Strony usług o dużej liczbie wyświetleń i niskim CTR wymagają wspólnie treści, trafności zapytania i analizy pozycji; zmiana samego tytułu nie gwarantuje poprawy.", "",
              "## GA4 — jakość pomiaru", "", f"Okresy w pliku: {ga['periods']}.", "",
              f"{ga['summary']['active_users']} aktywnych użytkowników, {ga['summary']['new_users']} nowych, średnie zaangażowanie {ga['summary']['average_engagement_seconds']:.1f} s, {ga['summary']['events']} zdarzeń.", "",
              "| Źródło / medium sesji | Sesje |", "|---|---:|"]
    for row in ga["session_source_rows"][:10]:
        lines.append(f"| {row['Sesja – źródło/medium']} | {row['Sesje']} |")
    lines += ["", f"Najważniejsze wydarzenia według platformy: {ga['key_events_by_platform']}. Brak nazw i definicji — nie jest to potwierdzona liczba zapytań ofertowych.", "",
              f"Podejrzane tytuły: {ga['suspicious_page_titles']}", f"Podejrzane sesje referral: {ga['suspicious_referral_sessions']}",
              f"Największy dzienny pik nowych użytkowników: {ga['highest_new_user_days'][:1]}.", "",
              "Do wykonania w GA4/GTM: sprawdzić page_location i hostname, wykluczyć rozpoznany spam z analiz przez porównanie/eksplorację, zbadać duplikaty gtag/GTM i oznaczenia kluczowych zdarzeń. Nie usuwać historycznych danych i nie włączać destrukcyjnych filtrów bez trybu testowego.",
              "Kliknięcie telefonu lub e-maila jest mikrozdarzeniem. Lead to potwierdzone poprawne wysłanie formularza lub rzeczywisty kontakt rozliczany osobno. Tytuły stron nie pozwalają ustalić duplikatów URL; lokalizacja użytkownika nie dowodzi jego intencji zakupowej.", "",
              "## Google Ads — co rzeczywiście zawiera ZIP", "",
              "Eksport dotyczy zasięgu i częstotliwości, nie efektywności sprzedaży. Brak kosztów, kliknięć, nazw kampanii, zapytań wyszukiwanych, leadów, wartości sprzedaży i definicji konwersji.", "",
              "```json", json.dumps(data["brand_reach_export"], ensure_ascii=False, indent=2), "```", "",
              "Unikalnych odbiorców urządzeń nie sumować: jedna osoba może występować na kilku urządzeniach. Etykiety tygodniowe nie podają pełnego zakresu kampanii. GA4 google/cpc nie jest liczbą kliknięć z Ads i pochodzi z innego okresu.", "",
              "Najpierw poprawnie mierzone konwersje, potem ocena zapytań, lokalizacji, sieci i budżetów. Do podziału Ads: osobno marka, lokalne usługi reklamowe, CNC oraz markizy; zasięg krajowy markiz rozdzielić od lokalnych usług montażowych. To propozycja struktury, nie zmiana kampanii.", "",
              "## Konkurencja i kontrola produkcji — zakres nieweryfikowany", "",
              "Analizator nie łączy się z Internetem i nie wykonuje zmian na kontach. Dostarczone eksporty nie zawierają SERP-ów, aktualnych pozycji konkurentów, danych administracyjnych profili Google, canonicali, mapy strony ani parametrów Core Web Vitals. Status prób sieciowych należy dokumentować osobno.", "",
              "| Miejsce w porównaniu | Sposób wyboru | Nazwa / URL | Oferta / realizacje / lokalność / pomiar |", "|---|---|---|---|",
              "| 1 | Wyniki organiczne: agencja reklamowa Olsztyn | Niezweryfikowane | Niezweryfikowane |",
              "| 2 | Mapy: reklama świetlna Olsztyn | Niezweryfikowane | Niezweryfikowane |",
              "| 3 | Wyniki organiczne: agencja reklamowa Ostróda | Niezweryfikowane | Niezweryfikowane |",
              "| 4 | Wyniki organiczne: frezowanie CNC Olsztyn | Niezweryfikowane | Niezweryfikowane |",
              "| 5 | Wyniki organiczne: markizy Olsztyn | Niezweryfikowane | Niezweryfikowane |", "",
              "Po uzyskaniu dostępu: odnotować datę, urządzenie i lokalizację wyszukiwania; wybrać 3–5 rzeczywistych firm, zapisać URL oferty i dowody własnych realizacji, nie utożsamiać jednego spersonalizowanego wyniku z rankingiem całego regionu.", "",
              "## Ograniczenia", ""]
    lines += [f"- {item}" for item in data["limitations"]]
    lines += ["", "## Odtworzenie", "", "Ten raport wygenerował `tools/analyze_growth_exports.py`. Plik JSON obok zawiera sumy, wybrane wiersze i SHA-256 wejść, bez treści prywatnego eksportu WordPressa. Wejścia oraz wyniki pozostają poza repozytorium.", ""]
    return "\n".join(lines)


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--gsc-zip", required=True, type=Path)
    parser.add_argument("--ga4-csv", required=True, type=Path)
    parser.add_argument("--brand-zip", required=True, type=Path)
    parser.add_argument("--content-bundle", type=Path, help="Optional public prepared content bundle; not evidence of deployment")
    parser.add_argument("--output-dir", required=True, type=Path)
    args = parser.parse_args()
    try:
        paths = [private_path(getattr(args, name), name) for name in ("gsc_zip", "ga4_csv", "brand_zip")]
        output = private_path(args.output_dir, "output_dir")
        data = analyze(*paths, args.content_bundle)
        output.mkdir(parents=True, exist_ok=True, mode=0o700)
        if output.stat().st_mode & 0o077:
            raise ValueError("Private output directory must not be readable by other users; use a new dedicated directory")
        (output / "analysis.json").write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        (output / "RAPORT-SEO-PRYWATNY.md").write_text(report_markdown(data), encoding="utf-8")
        with (output / "priority-queries.csv").open("w", newline="", encoding="utf-8-sig") as handle:
            writer = csv.writer(handle)
            writer.writerow(("Priorytet", "Zapytanie", "Strona", "Kliknięcia", "Wyświetlenia", "CTR (%)", "Średnia pozycja", "Działanie"))
            for item in data["gsc"]["priority_queries"]:
                row = item["metrics"] or {}
                writer.writerow((item["phase"], item["query"], item["path"], row.get("clicks", ""), row.get("impressions", ""), row.get("ctr_percent", ""), row.get("position", ""), item["action"]))
        for name in ("analysis.json", "RAPORT-SEO-PRYWATNY.md", "priority-queries.csv"):
            (output / name).chmod(0o600)
        print(f"Private analysis written to {output}. No upload or account changes performed.")
    except (OSError, ValueError, KeyError, zipfile.BadZipFile) as error:
        parser.exit(2, f"Analysis failed: {error}\n")


if __name__ == "__main__":
    main()
