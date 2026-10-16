#!/usr/bin/env python3
"""Exercise actual visual templates and protected admin actions through HTTP."""
import html
import http.cookiejar
import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser


class Forms(HTMLParser):
    def __init__(self, source):
        super().__init__()
        self.forms = []
        self.current = None
        self.feed(source)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "form":
            self.current = {}
            self.forms.append(self.current)
        elif tag == "input" and self.current is not None and attrs.get("name"):
            self.current[attrs["name"]] = attrs.get("value", "")

    def handle_endtag(self, tag):
        if tag == "form":
            self.current = None

    def action(self, mode):
        for form in self.forms:
            if form.get("action") == "yoona_seo_design" and form.get("mode") == mode:
                return dict(form)
        raise AssertionError(f"Missing design {mode} admin form")


base = sys.argv[1].rstrip("/")
phase = sys.argv[2] if len(sys.argv) > 2 else "standalone"
assertions = 0


def check(condition, message):
    global assertions
    if not condition:
        raise AssertionError(message)
    assertions += 1
    print(f"PASS Design HTTP {message}")


def client():
    return urllib.request.build_opener(
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())
    )


anonymous = client()
admin = client()


def request(path, fields=None, opener=None):
    data = None if fields is None else urllib.parse.urlencode(fields, doseq=True).encode()
    req = urllib.request.Request(base + path, data=data)
    try:
        response = (opener or anonymous).open(req, timeout=30)
    except urllib.error.HTTPError as error:
        response = error
    return response.code, response.read().decode("utf-8", errors="replace"), response.headers


def login(opener, user, password):
    request("/wp-login.php", opener=opener)
    return request("/wp-login.php", {
        "log": user, "pwd": password, "wp-submit": "Log In",
        "redirect_to": base + "/wp-admin/", "testcookie": "1",
    }, opener)[0]


def designed(source):
    body = re.search(r"<body\b[^>]*\bclass=[\"']([^\"']*)", source, re.I)
    return bool(body and "yn-design" in body.group(1).split())


def home_enabled(expected=True):
    status, source, _ = request("/")
    check(status == 200 and designed(source) is expected,
          "The public homepage uses the requested design state.")
    return source


if phase == "warm-original":
    # Block themes lazily create their core wp_navigation record on first render.
    # Initialise that theme feature before the strict visual-only DB baseline.
    for path in ("/", "/design-fixture/"):
        status, source, _ = request(path)
        check(status == 200 and not designed(source),
              "The original theme is warmed before the visual-only database baseline.")
    print(f"Success: {assertions} original-theme warm-up HTTP assertions passed.")
    sys.exit(0)

if phase == "protected-home":
    status, source, _ = request("/")
    check(status == 200 and "post-password-form" in source,
          "A protected front page renders the WordPress password form.")
    check('class="yn-hero' not in source and 'id="yn-hero-title"' not in source,
          "A protected front page never exposes the marketing homepage template.")
    print(f"Success: {assertions} protected-front-page HTTP assertions passed.")
    sys.exit(0)


source = home_enabled()
check('id="yn-hero-title"' in source and "widoczne." in source,
      "The new homepage hero is rendered by the real frontend.")
check("design.css" in source, "The selected template enqueues the visual stylesheet.")
stylesheet = re.search(r'<link\b[^>]*\bhref=[\'"]([^\'"]*design\.css[^\'"]*)', source, re.I)
check(stylesheet is not None, "The design stylesheet is registered through a normal WordPress link.")
asset_url = urllib.parse.urlsplit(html.unescape(stylesheet.group(1)))
asset_path = asset_url.path + ("?" + asset_url.query if asset_url.query else "")
style_status, style_source, style_headers = request(asset_path)
check(style_status == 200 and "text/css" in style_headers.get("Content-Type", "") and len(style_source) > 1000,
      "The actual bundled design CSS is served successfully by WordPress.")
check(len(re.findall(r"<title\b", source, re.I)) == 1,
      "The homepage contains exactly one SEO title.")
check(len(re.findall(r'<meta\s+name=[\'"]description[\'"]', source, re.I)) <= 1,
      "The homepage does not duplicate the SEO description.")
for marker in ("yoona-design-head-probe", "yoona-design-body-probe", "yoona-design-footer-probe"):
    check(source.count(marker) == 1, f"The standard WordPress hook {marker} executes once.")
check('href="#main-content"' in source, "The branded layout offers a skip link to its main content.")

status, secondary, _ = request("/design-fixture/")
check(status == 200 and designed(secondary), "Existing public pages receive the branded template.")
check("YOONA_EXISTING_DIVI_BODY_581" in secondary and "design-shortcode-probe" in secondary,
      "Existing Divi content still passes through WordPress the_content and shortcode callbacks.")
check("[et_pb_section" not in secondary and "https://example.invalid/preserved" in secondary,
      "The secondary template keeps rendered existing content and its links.")
titles = re.findall(r"<title>(.*?)</title>", secondary, re.I | re.S)
descriptions = re.findall(r'<meta\s+name=[\'"]description[\'"]\s+content=[\'"](.*?)[\'"]', secondary, re.I)
check([html.unescape(value).strip() for value in titles] == ["YOONA design SEO fixture"],
      "The branded secondary template renders exactly the existing SEO title.")
check([html.unescape(value) for value in descriptions] == ["YOONA design description fixture."],
      "The branded secondary template renders one exact existing SEO description.")
status, legacy_theme, _ = request("/design-fixture/?yoona_test_no_title_support=1")
legacy_titles = re.findall(r"<title>(.*?)</title>", legacy_theme, re.I | re.S)
check(status == 200 and [html.unescape(value).strip() for value in legacy_titles] == ["YOONA design SEO fixture"],
      "A theme without title-tag support still receives exactly one correct SEO title.")
if phase == "rank_math":
    check("Rank Math" in secondary, "The visual template retains actual Rank Math frontend output.")

status, post_source, _ = request("/design-post/")
check(status == 200 and designed(post_source) and "YOONA_EXISTING_POST_BODY_624" in post_source,
      "Public posts retain their original body inside the branded layout.")
status, protected, _ = request("/design-protected/")
check(status == 200 and designed(protected) and "post-password-form" in protected,
      "A protected public page keeps the standard WordPress password form.")
check("YOONA_PASSWORD_SECRET_CONTENT_913" not in protected,
      "The protected page never exposes its stored content to an anonymous visitor.")
for path, label in (("/?cat=1", "category archive"), ("/?s=design", "search results")):
    status, archive, _ = request(path)
    check(status == 200 and designed(archive), f"The {label} receives the branded layout.")
status, missing, _ = request("/?p=999999999")
check(status == 404 and designed(missing), "Missing public content keeps HTTP 404 inside the branded layout.")
status, feed, headers = request("/?feed=rss2")
check(status == 200 and "<rss" in feed and not designed(feed) and "design.css" not in feed,
      "The normal RSS feed is preserved and excludes visual templates.")
status, embedded, _ = request("/design-fixture/embed/")
check(status == 200 and not designed(embedded) and "design.css" not in embedded,
      "The native embed response excludes the visual template and stylesheet.")
status, api_source, headers = request("/wp-json/wp/v2/pages?slug=design-fixture")
api = json.loads(api_source)
check(status == 200 and isinstance(api, list) and len(api) == 1 and not designed(api_source),
      "The REST endpoint returns ordinary JSON without the visual template.")
for builder_key in ("et_fb", "et_bfb", "et_pb_preview", "elementor-preview"):
    status, builder_source, _ = request("/?" + builder_key + "=1")
    check(status == 200 and not designed(builder_source) and "design.css" not in builder_source,
          f"The {builder_key} editor request keeps its original theme template and stylesheet.")

status, _, _ = request("/wp-admin/admin-post.php", {"action": "yoona_seo_design", "mode": "disable"})
check(status in (400, 403), "An unauthenticated visitor cannot change the visual setting.")
home_enabled()
check(login(admin, "yoona_test_admin", "local-admin-test-only") == 200,
      "The real WordPress test administrator logs in.")
status, panel, _ = request("/wp-admin/tools.php?page=yoona-seo", opener=admin)
check(status == 200 and "yoona_seo_design" in panel, "The plugin panel exposes its visual design controls.")
disable = Forms(panel).action("disable")
missing_nonce = dict(disable)
missing_nonce.pop("_wpnonce", None)
status, _, _ = request("/wp-admin/admin-post.php", missing_nonce, admin)
check(status == 403, "The design action rejects a missing nonce with HTTP 403.")
home_enabled()
status, _, _ = request("/wp-admin/admin-post.php?" + urllib.parse.urlencode(disable), opener=admin)
check(status in (403, 405), "A GET request cannot change the visual setting even with a valid nonce.")
home_enabled()
invalid = dict(disable, mode="invalid-mode")
status, _, _ = request("/wp-admin/admin-post.php", invalid, admin)
check(status in (200, 400, 403), "An invalid visual mode is rejected safely.")
home_enabled()
reader = client()
check(login(reader, "yoona_design_reader", "local-reader-test-only") == 200,
      "The limited subscriber account logs in through WordPress.")
status, _, _ = request("/wp-admin/admin-post.php", disable, reader)
check(status == 403, "A logged-in subscriber cannot use the administrator visual action.")
home_enabled()
status, disabled_panel, _ = request("/wp-admin/admin-post.php", disable, admin)
check(status == 200, "A valid administrator POST disables the visual layer.")
disabled_source = home_enabled(False)
check('id="yn-hero-title"' not in disabled_source and "design.css" not in disabled_source,
      "Disabling the design restores the original theme frontend without its visual stylesheet.")
status, original_secondary, _ = request("/design-fixture/")
check(status == 200 and not designed(original_secondary) and "YOONA_EXISTING_DIVI_BODY_581" in original_secondary,
      "The original theme still displays unchanged secondary content when the design is disabled.")
enable = Forms(disabled_panel).action("enable")
status, enabled_panel, _ = request("/wp-admin/admin-post.php", enable, admin)
check(status == 200, "A valid administrator POST re-enables the visual layer.")
home_enabled()
refresh = Forms(enabled_panel).action("refresh")
status, _, _ = request("/wp-admin/admin-post.php", refresh, admin)
check(status == 200, "The authenticated refresh action completes without changing the selected design state.")
home_enabled()
print(f"Success: {assertions} real visual design HTTP assertions passed ({phase}).")
