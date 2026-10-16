#!/usr/bin/env python3
"""Exercise real WordPress admin forms, multipart upload, nonces and frontend output."""
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
            if attrs.get("type") != "checkbox":
                self.current[attrs["name"]] = attrs.get("value", "")

    def handle_endtag(self, tag):
        if tag == "form":
            self.current = None

    def action(self, action):
        for form in self.forms:
            if form.get("action") == action:
                return dict(form)
        raise AssertionError(f"Missing admin form: {action}")


base = sys.argv[1].rstrip("/")
native_rank_math = len(sys.argv) > 2 and sys.argv[2] == "rank_math"
opener = urllib.request.build_opener(
    urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())
)
assertions = 0


def check(condition, message):
    global assertions
    if not condition:
        raise AssertionError(message)
    assertions += 1
    print(f"PASS HTTP {message}")


def request(path, data=None, headers=None):
    req = urllib.request.Request(base + path, data=data, headers=headers or {})
    try:
        response = opener.open(req, timeout=30)
    except urllib.error.HTTPError as error:
        response = error
    return response.code, response.read().decode("utf-8", errors="replace")


def post_form(path, fields):
    return request(
        path,
        urllib.parse.urlencode(fields, doseq=True).encode(),
        {"Content-Type": "application/x-www-form-urlencoded"},
    )


def upload(fields, payload):
    boundary = "yoona-local-integration-boundary"
    parts = []
    for key, value in fields.items():
        parts.append(
            f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode()
        )
    parts.append(
        f'--{boundary}\r\nContent-Disposition: form-data; name="yoona_bundle"; filename="test.json"\r\nContent-Type: application/json\r\n\r\n'.encode()
        + payload
        + f"\r\n--{boundary}--\r\n".encode()
    )
    return request(
        "/wp-admin/admin-post.php",
        b"".join(parts),
        {"Content-Type": f"multipart/form-data; boundary={boundary}"},
    )


def frontend_metadata():
    status, source = request("/")
    check(status == 200, "Homepage responds with HTTP 200.")
    title = re.search(r"<title>(.*?)</title>", source, re.I | re.S)
    descriptions = re.findall(
        r'<meta\s+name=[\'"]description[\'"]\s+content=[\'"](.*?)[\'"]\s*/?>',
        source,
        re.I,
    )
    return (
        html.unescape(title.group(1)).strip() if title else None,
        [html.unescape(item) for item in descriptions],
    )


request("/wp-login.php")
status, _ = post_form(
    "/wp-login.php",
    {
        "log": "yoona_test_admin",
        "pwd": "local-admin-test-only",
        "wp-submit": "Log In",
        "redirect_to": base + "/wp-admin/",
        "testcookie": "1",
    },
)
check(status == 200, "Test administrator logs in through WordPress.")
status, page = request("/wp-admin/tools.php?page=yoona-seo")
check(status == 200 and "yoona_seo_import" in page, "Authorized administrator can open the plugin panel.")
before = frontend_metadata()

forms = Forms(page)
import_fields = forms.action("yoona_seo_import")
bundle = {
    "schema_version": 2 if native_rank_math else 1,
    "site_url": base,
    "audit_verified": True,
    "pages": [
        {
            "path": "/",
            "seo_title": "Yoona — HTTP wdrożenie testowe",
            "meta_description": "Opis testowy Yoona & kontakt z zespołem.",
        }
    ],
}
if native_rank_math:
    bundle["seo_storage"] = "rank_math"
status, page = upload(import_fields, json.dumps(bundle, ensure_ascii=False).encode())
check(status == 200 and bundle["pages"][0]["seo_title"] in page, "A real multipart JSON upload appears in the admin preview.")
check(frontend_metadata() == before, "JSON upload leaves the live homepage unchanged.")

apply_fields = Forms(page).action("yoona_seo_apply")
missing_nonce = dict(apply_fields)
missing_nonce.pop("_wpnonce", None)
missing_nonce["selected_paths[]"] = "/"
status, _ = post_form("/wp-admin/admin-post.php", missing_nonce)
check(status == 403, "An apply request without a valid nonce is rejected with HTTP 403.")
check(frontend_metadata() == before, "A rejected request cannot change frontend metadata.")

stale = dict(apply_fields)
stale["bundle_hash"] = "0" * 64
stale["selected_paths[]"] = "/"
post_form("/wp-admin/admin-post.php", stale)
check(frontend_metadata() == before, "A stale preview bundle hash cannot deploy changes.")

json_missing_nonce = dict(missing_nonce)
json_missing_nonce["yoona_json"] = "1"
status, _ = post_form("/wp-admin/admin-post.php", json_missing_nonce)
check(status == 403, "The JSON deployment endpoint also rejects a missing nonce.")
json_stale = dict(stale)
json_stale["yoona_json"] = "1"
status, body = post_form("/wp-admin/admin-post.php", json_stale)
check(status == 409 and json.loads(body)["success"] is False, "A stale JSON deployment receives an explicit HTTP 409 error.")
too_many = dict(apply_fields)
too_many["yoona_json"] = "1"
too_many["selected_paths[]"] = ["/"] * 11
status, body = post_form("/wp-admin/admin-post.php", too_many)
check(status == 409 and json.loads(body)["success"] is False, "An eleven-item direct request is blocked before applying any changes.")
check(frontend_metadata() == before, "Rejected JSON requests leave the homepage unchanged.")

apply_fields["selected_paths[]"] = "/"
apply_fields["yoona_json"] = "1"
status, response_body = post_form("/wp-admin/admin-post.php", apply_fields)
response_json = json.loads(response_body)
check(status == 200 and response_json["success"] and response_json["data"]["status"] == "success", "A valid one-page JSON admin request deploys changes.")
status, page = request("/wp-admin/tools.php?page=yoona-seo")
deployed = frontend_metadata()
check(deployed[0] == bundle["pages"][0]["seo_title"], "The actual homepage renders the deployed SEO title.")
check(deployed[1] == [bundle["pages"][0]["meta_description"]], "The actual homepage renders exactly one correct meta description.")
if native_rank_math:
    status, rank_source = request("/")
    check("Rank Math" in rank_source, "The real Rank Math plugin renders the homepage SEO output.")

rollback_fields = Forms(page).action("yoona_seo_rollback")
status, _ = post_form("/wp-admin/admin-post.php", rollback_fields)
check(status == 200 and frontend_metadata() == before, "The admin rollback form restores the previous frontend metadata.")

status, page = request("/wp-admin/tools.php?page=yoona-seo")
status, page = upload(Forms(page).action("yoona_seo_import"), b"not valid json")
check(status == 200 and "Plik nie zawiera poprawnej paczki JSON" in page, "A malformed JSON upload is rejected with a readable error.")
check(frontend_metadata() == before, "A malformed import leaves frontend metadata unchanged.")

status, page = request("/wp-admin/tools.php?page=yoona-seo")
diagnostics_fields = Forms(page).action("yoona_seo_diagnostics")
status, diagnostic_body = post_form("/wp-admin/admin-post.php", diagnostics_fields)
diagnostic = json.loads(diagnostic_body)
check(status == 200 and isinstance(diagnostic, dict), "The authenticated diagnostics button downloads real JSON.")
check("user_pass" not in diagnostic_body and "local-admin-test-only" not in diagnostic_body, "The diagnostics download contains no password fields or test admin password.")

status, page = request("/wp-admin/tools.php?page=yoona-seo")
bundled_fields = Forms(page).action("yoona_seo_bundled")
status, bundled_page = post_form("/wp-admin/admin-post.php", bundled_fields)
check(status == 200 and "Wybrano pakiet dołączony do wtyczki" in bundled_page, "The bundled-package button resets the imported preview package.")
check(frontend_metadata() == before, "Selecting the bundled package never applies content or metadata.")

print(f"Success: {assertions} real HTTP integration assertions passed.")
