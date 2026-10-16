#!/usr/bin/env python3
"""A small, read-only SEO crawler for yoona.pl. Python 3.10+, standard library only.

The source HTML and extracted text are saved for subsequent editorial review.
TLS verification remains enabled; OpenSSL's SSL_CERT_FILE/SSL_CERT_DIR are honored.
No result here measures search ranking, Core Web Vitals, or rendered JavaScript.
"""
from __future__ import annotations

import argparse
from collections import Counter, deque
from datetime import datetime, timezone
from hashlib import sha256
from html.parser import HTMLParser
import json
from pathlib import Path
import re
import ssl
import time
from typing import Any
from urllib.error import HTTPError, URLError
from urllib.parse import quote, urljoin, urlsplit, urlunsplit
from urllib.request import HTTPRedirectHandler, HTTPSHandler, Request, build_opener
from urllib.robotparser import RobotFileParser
import xml.etree.ElementTree as ET

ORIGIN = "https://yoona.pl/"
HOSTS = {"yoona.pl", "www.yoona.pl"}
USER_AGENT = "YoonaSEOAudit/1.0 (+read-only; user-requested site audit)"
ROBOT_AGENT = "YoonaSEOAudit"
MAX_BYTES = 5 * 1024 * 1024
ASSET_SUFFIXES = re.compile(
    r"\.(?:avif|bmp|css|csv|docx?|eot|gif|gz|ico|jpe?g|js|json|map|mp[34]|ogg|pdf|png|rar|rss|svg|tar|tiff?|txt|web[mp]|woff2?|xml|zip)$",
    re.I,
)


def normalize_url(value: str, base: str = ORIGIN, *, scoped: bool = True, canonicalize_alias: bool = True) -> str | None:
    """Resolve a web URL; canonicalize the two public host aliases and fragments.

    Query strings and trailing slashes remain meaningful. URLs containing credentials,
    nonstandard ports or non-HTTP protocols are never fetched.
    """
    try:
        parts = urlsplit(urljoin(base, value.strip()))
        if parts.scheme.lower() not in {"http", "https"} or parts.username or parts.password:
            return None
        host = (parts.hostname or "").lower()
        if not host or parts.port not in {None, 80, 443}:
            return None
        if scoped and host not in HOSTS:
            return None
        path = quote(parts.path or "/", safe="/%:@!$&'()*+,;=-._~")
        query = quote(parts.query, safe="%/?@!$&'()*+,;=:-._~")
        if host in HOSTS and canonicalize_alias:
            return urlunsplit(("https", "yoona.pl", path, query, ""))
        netloc = parts.netloc.lower()
        return urlunsplit((parts.scheme.lower(), netloc, path, query, ""))
    except (TypeError, ValueError):
        return None


def crawl_candidate(url: str) -> bool:
    parts = urlsplit(url)
    return (
        parts.hostname == "yoona.pl"
        and not parts.query
        and not ASSET_SUFFIXES.search(parts.path)
        and not re.match(r"^/(?:wp-admin|wp-json|wp-login\.php)(?:/|$)", parts.path)
        and not re.search(r"/(?:feed|embed)/?$", parts.path)
    )


def compact(value: str) -> str:
    return re.sub(r"\s+", " ", value).strip()


class SEOParser(HTMLParser):
    def __init__(self, url: str):
        super().__init__(convert_charrefs=True)
        self.url = url
        self.base_url = url
        self.title_parts: list[str] = []
        self.titles: list[str] = []
        self.description: list[str] = []
        self.robots: list[str] = []
        self.canonicals: list[str] = []
        self.canonicals_raw: list[str] = []
        self.headings: dict[str, list[str]] = {"h1": [], "h2": []}
        self.images: list[dict[str, Any]] = []
        self.links: list[str] = []
        self.hreflang: list[dict[str, str | None]] = []
        self.og: dict[str, list[str]] = {}
        self.viewport: list[str] = []
        self.language: str | None = None
        self.rest_api: list[str] = []
        self.jsonld: list[dict[str, Any]] = []
        self.text_parts: list[str] = []
        self._in_title = False
        self._heading: str | None = None
        self._heading_parts: list[str] = []
        self._hidden: list[str] = []
        self._jsonld_parts: list[str] | None = None

    def handle_starttag(self, tag: str, attributes: list[tuple[str, str | None]]) -> None:
        attrs = dict(attributes)
        if tag == "html":
            self.language = attrs.get("lang")
        elif tag == "base" and attrs.get("href"):
            self.base_url = normalize_url(attrs["href"], self.url, scoped=False, canonicalize_alias=False) or self.url
        elif tag == "title":
            self._in_title = True
            self.title_parts = []
        elif tag in self.headings:
            self._finish_heading()
            self._heading, self._heading_parts = tag, []
        elif tag == "meta":
            name = (attrs.get("name") or "").lower()
            prop = (attrs.get("property") or "").lower()
            content = attrs.get("content") or ""
            if name == "description":
                self.description.append(content)
            elif name in {"robots", "googlebot", "bingbot"}:
                self.robots.append(f"{name}: {content}")
            elif name == "viewport":
                self.viewport.append(content)
            if prop.startswith("og:"):
                self.og.setdefault(prop, []).append(content)
        elif tag == "link":
            rel = (attrs.get("rel") or "").lower().split()
            href = attrs.get("href") or ""
            resolved = normalize_url(href, self.base_url, scoped=False, canonicalize_alias=False)
            if "canonical" in rel:
                self.canonicals.append(resolved or href)
                self.canonicals_raw.append(href)
            if "alternate" in rel and attrs.get("hreflang"):
                self.hreflang.append({"lang": attrs["hreflang"], "url": resolved, "href_raw": href})
            if "https://api.w.org/" in rel and resolved:
                self.rest_api.append(resolved)
        elif tag == "a" and attrs.get("href"):
            resolved = normalize_url(attrs["href"], self.base_url, scoped=False)
            if resolved:
                self.links.append(resolved)
        elif tag == "img":
            self.images.append({
                "src": attrs.get("src") or attrs.get("data-src"),
                "alt": attrs.get("alt"),
                "alt_missing": "alt" not in attrs,
                "alt_empty": "alt" in attrs and not (attrs.get("alt") or "").strip(),
            })
        if tag in {"script", "style", "noscript", "template"}:
            self._hidden.append(tag)
        if tag == "script" and (attrs.get("type") or "").lower() == "application/ld+json":
            self._jsonld_parts = []

    def handle_startendtag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        self.handle_starttag(tag, attrs)
        self.handle_endtag(tag)

    def handle_endtag(self, tag: str) -> None:
        if tag == "title":
            self.titles.append(compact(" ".join(self.title_parts)))
            self._in_title = False
        if tag == self._heading:
            self._finish_heading()
        if tag == "script" and self._jsonld_parts is not None:
            raw = "".join(self._jsonld_parts)
            try:
                parsed = json.loads(raw)
                self.jsonld.append({"valid": True, "types": sorted(jsonld_types(parsed))})
            except (ValueError, TypeError, RecursionError) as exc:
                self.jsonld.append({"valid": False, "types": [], "error": str(exc)[:300]})
            self._jsonld_parts = None
        if tag in self._hidden:
            reverse_index = self._hidden[::-1].index(tag)
            del self._hidden[len(self._hidden) - reverse_index - 1:]

    def handle_data(self, data: str) -> None:
        if self._jsonld_parts is not None:
            self._jsonld_parts.append(data)
        if self._in_title:
            self.title_parts.append(data)
        if self._heading and not self._hidden:
            self._heading_parts.append(data)
        if not self._hidden and not self._in_title:
            value = compact(data)
            if value:
                self.text_parts.append(value)

    def _finish_heading(self) -> None:
        if self._heading:
            self.headings[self._heading].append(compact(" ".join(self._heading_parts)))
        self._heading, self._heading_parts = None, []

    def result(self) -> dict[str, Any]:
        self._finish_heading()
        text = compact(" ".join(self.text_parts))
        return {
            "title": self.titles[0] if self.titles else None,
            "titles": self.titles,
            "title_length": len(self.titles[0]) if self.titles else 0,
            "description": self.description[0] if self.description else None,
            "descriptions": self.description,
            "description_length": len(self.description[0]) if self.description else 0,
            "canonical": self.canonicals,
            "canonical_raw": self.canonicals_raw,
            "robots_meta": self.robots,
            "noindex_meta": any(re.search(r"\b(?:noindex|none)\b", item, re.I) for item in self.robots),
            "h1": self.headings["h1"], "h2": self.headings["h2"],
            "word_count": len(re.findall(r"\b\w+\b", text, re.UNICODE)),
            "images": self.images,
            "images_alt_missing": sum(item["alt_missing"] for item in self.images),
            "images_alt_empty": sum(item["alt_empty"] for item in self.images),
            "internal_links": list(dict.fromkeys(link for link in self.links if normalize_url(link))),
            "external_links": list(dict.fromkeys(link for link in self.links if not normalize_url(link))),
            "hreflang": self.hreflang, "jsonld": self.jsonld,
            "open_graph": self.og, "viewport": self.viewport,
            "language": self.language, "wp_rest_api_links": self.rest_api,
            "text": text,
        }


def jsonld_types(value: Any) -> set[str]:
    found: set[str] = set()
    if isinstance(value, dict):
        types = value.get("@type", [])
        if isinstance(types, str):
            found.add(types)
        elif isinstance(types, list):
            found.update(item for item in types if isinstance(item, str))
        for child in value.values():
            found.update(jsonld_types(child))
    elif isinstance(value, list):
        for child in value:
            found.update(jsonld_types(child))
    return found


def parse_robots(body: str, url: str = ORIGIN + "robots.txt") -> tuple[RobotFileParser, list[str]]:
    robot = RobotFileParser(url)
    robot.parse(body.splitlines())
    sitemaps = []
    for line in body.splitlines():
        key, sep, value = line.partition(":")
        if sep and key.strip().lower() == "sitemap":
            scoped_url = normalize_url(value.split("#", 1)[0].strip(), url)
            if scoped_url and scoped_url not in sitemaps:
                sitemaps.append(scoped_url)
    return robot, sitemaps


def parse_sitemap(body: str, base: str, limit: int = 30) -> dict[str, list[str]]:
    """Parse standard XML (including namespaces), rejecting off-site URLs."""
    result: dict[str, list[str]] = {"urls": [], "sitemaps": []}
    root = ET.fromstring(body)
    kind = root.tag.rsplit("}", 1)[-1]
    if kind not in {"urlset", "sitemapindex"}:
        raise ValueError("Unsupported sitemap XML root: " + kind)
    target = result["sitemaps" if kind == "sitemapindex" else "urls"]
    expected = "sitemap" if kind == "sitemapindex" else "url"
    for entry in root:
        if entry.tag.rsplit("}", 1)[-1] != expected:
            continue
        for node in entry:
            if node.tag.rsplit("}", 1)[-1] == "loc" and node.text:
                url = normalize_url(node.text.strip(), base)
                if url and url not in target:
                    target.append(url)
                break
        if len(target) >= max(0, limit):
            break
    return result


class ScopedRedirectHandler(HTTPRedirectHandler):
    def __init__(self):
        self.redirects: list[dict[str, Any]] = []
        self.robots_allowed = None

    def redirect_request(self, req: Any, fp: Any, code: int, msg: str, headers: Any, newurl: str) -> Any:
        self.redirects.append({"from": req.full_url, "status": code, "to": newurl})
        # The actual redirect URL is preserved for audit evidence. Only public aliases
        # are followed, so a compromised page cannot redirect this tool to another host.
        if not normalize_url(newurl):
            return None
        if self.robots_allowed is not None and not self.robots_allowed(newurl):
            self.redirects[-1]["not_followed"] = "robots rule or unavailable robots.txt"
            return None
        return super().redirect_request(req, fp, code, msg, headers, newurl)


class Fetcher:
    def __init__(self, timeout: float = 20, delay: float = 0.3):
        self.timeout = min(20.0, max(1.0, timeout))
        self.delay = max(0.3, delay)
        self.last_request = 0.0
        self.redirect_handler = ScopedRedirectHandler()
        self.opener = build_opener(self.redirect_handler, HTTPSHandler(context=ssl.create_default_context()))

    def fetch(self, url: str) -> dict[str, Any]:
        time.sleep(max(0.0, self.delay - (time.monotonic() - self.last_request)))
        self.last_request = time.monotonic()
        self.redirect_handler.redirects = []
        result: dict[str, Any] = {"url": url, "status": None, "final_url": None, "headers": {}, "body": "", "error": None, "environment_blocked": False}
        started = time.monotonic()
        response = None
        try:
            try:
                response = self.opener.open(Request(url, headers={"User-Agent": USER_AGENT, "Accept": "text/html,application/xml,text/xml,text/plain;q=0.9,*/*;q=0.1"}), timeout=self.timeout)
            except HTTPError as exc:
                response = exc
            with response:
                result["status"] = response.code
                result["final_url"] = response.geturl()
                # Keep public response metadata, never cookies or authentication headers.
                keep = {"content-type", "content-length", "location", "x-robots-tag", "link", "server", "x-mitmproxy-blocked-reason", "x-denied-reason"}
                result["headers"] = {key.lower(): value for key, value in response.headers.items() if key.lower() in keep}
                raw = response.read(MAX_BYTES + 1)
                result["truncated"] = len(raw) > MAX_BYTES
                raw = raw[:MAX_BYTES]
                charset = response.headers.get_content_charset() or "utf-8"
                try:
                    result["body"] = raw.decode(charset, errors="replace")
                except LookupError:
                    result["body"] = raw.decode("utf-8", errors="replace")
                reasons = [result["headers"].get(name, "") for name in ("x-mitmproxy-blocked-reason", "x-denied-reason")]
                proxy_evidence = re.search(r"(?:proxy|egress|network access).{0,80}(?:blocked|denied|not allowed)|(?:domain|host).{0,50}not (?:in|on).{0,30}allowlist", result["body"][:20000], re.I | re.S)
                result["environment_blocked"] = bool(any(reasons) or (result["status"] in {403, 451, 502} and proxy_evidence))
                if result["environment_blocked"]:
                    result["error"] = "Access blocked by the execution environment/proxy; this is not evidence of a site SEO defect."
        except (URLError, TimeoutError, OSError, ValueError) as exc:
            result["error"] = str(exc)
            result["access_failure"] = True
            # A CONNECT tunnel rejection happens at the configured proxy before
            # receiving a response from the audited site. Keep its original text.
            if re.search(r"\bTunnel connection failed:\s*(?:403|451)\b", str(exc), re.I):
                result["environment_blocked"] = True
        result["redirects"] = list(self.redirect_handler.redirects)
        result["request_elapsed_seconds"] = round(time.monotonic() - started, 3)
        return result


def save_raw(result: dict[str, Any], raw_dir: Path, extension: str) -> str:
    key = sha256(result["url"].encode()).hexdigest()[:16]
    target = raw_dir / (key + extension)
    target.write_text(result["body"], encoding="utf-8")
    return "raw/" + target.name


def without_body(result: dict[str, Any]) -> dict[str, Any]:
    return {key: value for key, value in result.items() if key != "body"}


def findings_for(page: dict[str, Any]) -> list[str]:
    if page.get("environment_blocked") or page.get("error") or page.get("status") in {401, 403, 429, 451} or page.get("truncated"):
        return []
    if not page.get("seo"):
        return ["http_error"] if page.get("status") and page["status"] >= 400 else []
    seo = page["seo"]
    findings = []
    if not seo["title"]:
        findings.append("missing_title")
    if len(seo["titles"]) > 1:
        findings.append("multiple_titles")
    if not seo["description"]:
        findings.append("missing_meta_description")
    if not seo["h1"]:
        findings.append("missing_h1")
    if len(seo["h1"]) > 1:
        findings.append("multiple_h1_review_hierarchy")
    if not seo["canonical"]:
        findings.append("missing_canonical_review")
    if len(seo["canonical"]) > 1:
        findings.append("multiple_canonical_tags")
    if page.get("noindex"):
        findings.append("noindex_review_intent")
    if seo["images_alt_missing"]:
        findings.append("images_missing_alt_attribute")
    if any(not item["valid"] for item in seo["jsonld"]):
        findings.append("invalid_jsonld")
    if not seo["language"]:
        findings.append("missing_html_lang")
    if not seo["viewport"]:
        findings.append("missing_viewport")
    return findings


def run_audit(output_dir: Path, max_pages: int = 30, max_sitemaps: int = 30, timeout: float = 20, delay: float = 0.3) -> dict[str, Any]:
    output_dir.mkdir(parents=True, exist_ok=True)
    raw_dir = output_dir / "raw"
    raw_dir.mkdir(exist_ok=True)
    max_pages, max_sitemaps = max(1, min(30, max_pages)), max(1, min(30, max_sitemaps))
    fetcher = Fetcher(timeout, delay)
    report: dict[str, Any] = {
        "site": ORIGIN, "generated_at": datetime.now(timezone.utc).isoformat(),
        "limits": {"html_pages": max_pages, "sitemaps": max_sitemaps, "timeout_seconds": fetcher.timeout, "min_delay_seconds": fetcher.delay},
        "scope": "Public HTTP response and source HTML; same host only, www alias normalized, no login, no writes to site.",
        "not_checked": ["Core Web Vitals and browser performance", "JavaScript-rendered DOM", "Google rankings, traffic and indexing (requires Search Console)", "Server and WordPress configuration", "External backlinks", "Image visual meaning and suitability of existing alt text", "Whether an empty alt intentionally marks a decorative image", "Whether noindex matches editorial intent", "Hreflang reciprocal validity across unvisited pages", "Structured data eligibility and Google rich-result rules"],
        "resources": [], "pages": [], "robots_skipped": [], "access_limitations": [],
    }
    robots = fetcher.fetch(ORIGIN + "robots.txt")
    robots["raw_file"] = save_raw(robots, raw_dir, ".txt")
    robot_parser = None
    declared_sitemaps: list[str] = []
    if robots["status"] == 200 and not robots["environment_blocked"] and not robots["truncated"]:
        robot_parser, declared_sitemaps = parse_robots(robots["body"])
        report["robots_status"] = "read"
    elif robots["status"] in {404, 410} and not robots["environment_blocked"]:
        robot_parser, _ = parse_robots("")
        report["robots_status"] = "absent; crawl allowed"
    else:
        report["robots_status"] = "unavailable; only explicitly requested homepage and sitemap endpoints checked"
    report["resources"].append(without_body(robots))
    crawl_delay = robot_parser.crawl_delay(ROBOT_AGENT) if robot_parser else None
    if crawl_delay is not None:
        fetcher.delay = max(fetcher.delay, float(crawl_delay))
        report["limits"]["min_delay_seconds"] = fetcher.delay
    sitemap_queue = deque(dict.fromkeys(declared_sitemaps + [ORIGIN + "sitemap_index.xml", ORIGIN + "wp-sitemap.xml", ORIGIN + "sitemap.xml"]))
    sitemap_seen: set[str] = set()
    page_queue = deque([ORIGIN])
    sitemap_pages: list[str] = []
    while sitemap_queue and len(sitemap_seen) < max_sitemaps:
        url = sitemap_queue.popleft()
        if url in sitemap_seen:
            continue
        sitemap_seen.add(url)
        result = fetcher.fetch(url)
        result["raw_file"] = save_raw(result, raw_dir, ".xml")
        if result["status"] == 200 and not result["environment_blocked"] and not result["truncated"]:
            try:
                entries = parse_sitemap(result["body"], result["final_url"] or url, limit=max_pages)
                result["sitemap_entries"] = entries
                for child in entries["sitemaps"]:
                    if child not in sitemap_seen and child not in sitemap_queue:
                        sitemap_queue.append(child)
                for page in entries["urls"]:
                    if page not in sitemap_pages:
                        sitemap_pages.append(page)
                    if crawl_candidate(page) and page not in page_queue:
                        page_queue.append(page)
            except (ET.ParseError, ValueError) as exc:
                result["sitemap_parse_error"] = str(exc)[:300]
        report["resources"].append(without_body(result))
    report["sitemap_limit_reached"] = bool(sitemap_queue)
    report["sitemap_urls_discovered"] = sitemap_pages
    seen: set[str] = set()
    fetcher.redirect_handler.robots_allowed = lambda target: bool(robot_parser and robot_parser.can_fetch(ROBOT_AGENT, target))
    while page_queue and len(report["pages"]) < max_pages:
        url = page_queue.popleft()
        if url in seen:
            continue
        seen.add(url)
        # Homepage is an explicitly requested audit target; other pages obey robots.
        if url != ORIGIN and (robot_parser is None or not robot_parser.can_fetch(ROBOT_AGENT, url)):
            report["robots_skipped"].append(url)
            continue
        result = fetcher.fetch(url)
        body = result["body"]
        content_type = result["headers"].get("content-type", "").lower()
        if result["status"] == 200 and not result["environment_blocked"] and ("html" in content_type or re.search(r"<!doctype\s+html|<html\b", body[:1000], re.I)):
            result["raw_file"] = save_raw(result, raw_dir, ".html")
            parser = SEOParser(result["final_url"] or url)
            try:
                parser.feed(body)
                parser.close()
                seo = parser.result()
                visible_text = seo.pop("text")
                text_file = Path(result["raw_file"]).with_suffix(".txt")
                (output_dir / text_file).write_text(visible_text + "\n", encoding="utf-8")
                result["text_file"] = str(text_file)
                result["seo"] = seo
                xrobots = result["headers"].get("x-robots-tag", "")
                result["noindex"] = seo["noindex_meta"] or bool(re.search(r"\b(?:noindex|none)\b", xrobots, re.I))
                result["wp_rest_api_hint"] = bool(seo["wp_rest_api_links"] or 'https://api.w.org/' in result["headers"].get("link", ""))
                if not result.get("truncated"):
                    for link in seo["internal_links"]:
                        link = normalize_url(link)
                        if link and crawl_candidate(link) and link not in seen and link not in page_queue:
                            page_queue.append(link)
            except (ValueError, RecursionError) as exc:
                result["html_parse_error"] = str(exc)[:300]
        else:
            result["raw_file"] = save_raw(result, raw_dir, ".txt")
        result["findings"] = findings_for(result)
        report["pages"].append(without_body(result))
    report["page_limit_reached"] = bool(page_queue)
    successful = [page for page in report["pages"] if page.get("seo")]
    report["duplicate_titles"] = duplicate_values(successful, "title")
    report["duplicate_descriptions"] = duplicate_values(successful, "description")
    status_map = {page["url"]: page for page in report["pages"]}
    report["broken_internal_links_checked"] = [
        {"source": page["url"], "target": link, "status": status_map[link]["status"]}
        for page in successful for link in page["seo"]["internal_links"]
        if link in status_map and status_map[link]["status"] in {400, 404, 410, 500, 502, 503, 504}
        and not status_map[link].get("environment_blocked") and not status_map[link].get("error")
    ]
    report["internal_link_check_scope"] = "Only visited HTML candidates; assets, query URLs and links beyond crawl limit were not checked. 401/403 indicate access restrictions, not established broken pages."
    for item in report["resources"] + report["pages"]:
        if item.get("error") or item.get("status") in {401, 403, 429, 451}:
            report["access_limitations"].append({"url": item["url"], "status": item["status"], "environment_blocked": item.get("environment_blocked", False), "detail": item.get("error") or "Access restricted; cause requires verification. Not classified as SEO defect."})
    report["summary"] = {
        "html_pages_analyzed": len(successful), "requests": len(report["resources"]) + len(report["pages"]),
        "findings_counts": dict(Counter(finding for page in report["pages"] for finding in page["findings"])),
        "status": "blocked" if not successful and report["access_limitations"] else ("partial" if report["access_limitations"] or report["page_limit_reached"] or report["sitemap_limit_reached"] or robot_parser is None or any(item.get("truncated") or item.get("html_parse_error") for item in report["pages"]) else "completed within scope"),
    }
    (output_dir / "report.json").write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    (output_dir / "report.md").write_text(render_markdown(report), encoding="utf-8")
    return report


def duplicate_values(pages: list[dict[str, Any]], key: str) -> list[dict[str, Any]]:
    groups: dict[str, list[str]] = {}
    for page in pages:
        value = compact(page["seo"].get(key) or "")
        if value:
            groups.setdefault(value, []).append(page["url"])
    return [{"value": value, "urls": urls} for value, urls in groups.items() if len(urls) > 1]


def md(value: Any) -> str:
    return str(value if value is not None else "—").replace("|", "\\|").replace("\n", " ").replace("\r", " ")


def render_markdown(report: dict[str, Any]) -> str:
    lines = ["# Audyt techniczny SEO: yoona.pl", "", f"Wygenerowano (UTC): {report['generated_at']}", "", f"Status: **{report['summary']['status']}**. Przeanalizowane strony HTML: **{report['summary']['html_pages_analyzed']}**.", "", "To obserwacje odpowiedzi HTTP i kodu źródłowego, bez wykonywania JavaScript. Lista sygnałów do sprawdzenia nie oznacza automatycznie błędów ani gwarancji wzrostu pozycji.", "", f"Robots.txt: {report['robots_status']}.", ""]
    for change in report.get("postprocessing", []):
        lines += [f"Korekta klasyfikacji (UTC): {change['at']}. {change['note']}", ""]
    if report["access_limitations"]:
        lines += ["## Ograniczenia dostępu", "", "Blokada proxy/środowiska nie jest dowodem błędu SEO witryny. Dla innych odmów dostępu przyczyna pozostaje nieustalona.", ""]
        for item in report["access_limitations"]:
            lines.append(f"- {md(item['url'])}: HTTP {md(item['status'])}; {md(item['detail'])}")
        lines.append("")
    lines += ["## Strony", "", "| URL | HTTP | Title | Opis | H1 | Słowa | Brak alt | Sygnały do weryfikacji |", "| --- | --- | --- | --- | --- | --- | --- | --- |"]
    for page in report["pages"]:
        seo = page.get("seo", {})
        lines.append("| " + " | ".join(md(value) for value in [page["url"], page["status"], seo.get("title"), seo.get("description"), "; ".join(seo.get("h1", [])) or None, seo.get("word_count"), seo.get("images_alt_missing"), ", ".join(page["findings"]) or "—"]) + " |")
    lines += ["", "## Zduplikowane metadane", ""]
    for key, label in (("duplicate_titles", "Title"), ("duplicate_descriptions", "Opis")):
        lines.append(f"- {label}: {len(report[key])} grup w zbadanej próbie.")
        for group in report[key]:
            lines.append(f"  - {md(group['value'])}: {', '.join(group['urls'])}")
    lines += ["", "## Linki i mapy witryny", "", f"Potwierdzone niesprawne linki w odwiedzonej próbie: {len(report['broken_internal_links_checked'])}.", "", report["internal_link_check_scope"], "", f"Pominięte przez robots lub niedostępne robots: {len(report['robots_skipped'])}. Limit stron osiągnięty: {report['page_limit_reached']}. Limit map: {report['sitemap_limit_reached']}.", "", "## Nie sprawdzono", ""]
    lines += [f"- {value}" for value in report["not_checked"]]
    lines += ["", "## Pliki źródłowe", "", "`report.json` zawiera pełne metadane, przekierowania, nagłówki, linki, hreflang, JSON-LD i listy alt. W `raw/` zapisano pobrane HTML/XML/robots oraz tekst do przeglądu redakcyjnego. Nazwy plików i URL-e łączy pole `raw_file` w JSON.", "", "Długości title/opisu to dane pomocnicze; nie użyto sztywnych progów jako kryterium rankingu. Wielokrotne H1, brak canonical oraz noindex wymagają oceny przeznaczenia strony. Puste alt mogą być poprawne przy dekoracjach.", ""]
    return "\n".join(lines)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output-dir", type=Path, default=Path("audit/live"))
    parser.add_argument("--max-pages", type=int, default=30)
    parser.add_argument("--max-sitemaps", type=int, default=30)
    parser.add_argument("--timeout", type=float, default=20)
    parser.add_argument("--delay", type=float, default=0.3)
    args = parser.parse_args()
    report = run_audit(args.output_dir, args.max_pages, args.max_sitemaps, args.timeout, args.delay)
    print(json.dumps({"status": report["summary"]["status"], "html_pages_analyzed": report["summary"]["html_pages_analyzed"], "report_json": str(args.output_dir / "report.json"), "report_markdown": str(args.output_dir / "report.md")}, ensure_ascii=False))
    return 2 if report["summary"]["status"] == "blocked" else 0


if __name__ == "__main__":
    raise SystemExit(main())
