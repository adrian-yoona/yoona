"""Offline tests for SEO audit parsing and crawl safety. No network is used."""
from importlib.util import module_from_spec, spec_from_file_location
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch
from urllib.request import Request
from urllib.error import URLError
from email.message import Message

SPEC = spec_from_file_location("audit_site", Path(__file__).resolve().parents[1] / "tools" / "audit_site.py")
audit = module_from_spec(SPEC)
SPEC.loader.exec_module(audit)


class MetadataTests(unittest.TestCase):
    def parse(self, html, url="https://yoona.pl/oferta/"):
        parser = audit.SEOParser(url)
        parser.feed(html)
        parser.close()
        return parser.result()

    def test_metadata_headings_jsonld_and_hidden_content(self):
        seo = self.parse('''<html lang="pl"><head><title>Yoona &amp; piękno</title>
          <meta name="description" content="Opis oferty."><meta name="viewport" content="width=device-width">
          <meta property="og:title" content="Yoona"><script type="application/ld+json">
          {"@context":"https://schema.org","@graph":[{"@type":["Organization","LocalBusiness"]}]}</script>
          <style>hidden css</style></head><body><h1>Oferta <em>Yoona</em></h1><h1>Drugi</h1><h2>Cennik</h2>
          <img src="a.jpg"><img src="b.jpg" alt=""><img src="c.jpg" alt="Wnętrze">
          <script>hidden script words</script><p>Zdrowa skóra.</p></body></html>''')
        self.assertEqual(seo["title"], "Yoona & piękno")
        self.assertEqual(seo["description"], "Opis oferty.")
        self.assertEqual(seo["language"], "pl")
        self.assertEqual(seo["h1"], ["Oferta Yoona", "Drugi"])
        self.assertEqual(seo["h2"], ["Cennik"])
        self.assertEqual(seo["images_alt_missing"], 1)
        self.assertEqual(seo["images_alt_empty"], 1)
        self.assertNotIn("hidden", seo["text"])
        self.assertEqual(seo["jsonld"], [{"valid": True, "types": ["LocalBusiness", "Organization"]}])
        findings = audit.findings_for({"status": 200, "seo": seo})
        self.assertIn("multiple_h1_review_hierarchy", findings)
        self.assertIn("images_missing_alt_attribute", findings)

    def test_relative_canonical_hreflang_links_and_aliases(self):
        seo = self.parse('''<link rel="canonical" href="../zabiegi/#sekcja">
          <link rel="alternate" hreflang="en" href="https://www.yoona.pl/en/">
          <link rel="https://api.w.org/" href="/wp-json/">
          <a href="/kontakt/#mapa">Kontakt</a><a href="https://example.org/x">Zewnętrzny</a>''')
        self.assertEqual(seo["canonical"], ["https://yoona.pl/zabiegi/"])
        self.assertEqual(seo["hreflang"], [{"lang": "en", "url": "https://www.yoona.pl/en/", "href_raw": "https://www.yoona.pl/en/"}])
        self.assertEqual(seo["internal_links"], ["https://yoona.pl/kontakt/"])
        self.assertEqual(seo["external_links"], ["https://example.org/x"])
        self.assertEqual(seo["wp_rest_api_links"], ["https://yoona.pl/wp-json/"])

    def test_metadata_preserves_observed_host_and_protocol(self):
        seo = self.parse('<link rel="canonical" href="http://www.yoona.pl/oferta/#x">')
        self.assertEqual(seo["canonical_raw"], ["http://www.yoona.pl/oferta/#x"])
        self.assertEqual(seo["canonical"], ["http://www.yoona.pl/oferta/"])
        seo = self.parse('<link rel="canonical" href="../o-nas/">', url="http://www.yoona.pl/oferta/")
        self.assertEqual(seo["canonical"], ["http://www.yoona.pl/o-nas/"])

    def test_noindex_and_invalid_jsonld(self):
        seo = self.parse('<meta name="ROBOTS" content="NOINDEX, follow"><script type="application/ld+json">{broken}</script>')
        self.assertTrue(seo["noindex_meta"])
        self.assertFalse(seo["jsonld"][0]["valid"])
        self.assertFalse(self.parse('<meta name="robots" content="index, follow">')["noindex_meta"])
        self.assertTrue(self.parse('<meta name="googlebot" content="none">')["noindex_meta"])


class ScopeAndDiscoveryTests(unittest.TestCase):
    def test_normalization_scope_and_crawl_candidates(self):
        self.assertEqual(audit.normalize_url("http://WWW.YOONA.PL/a/?id=1#x"), "https://yoona.pl/a/?id=1")
        self.assertEqual(audit.normalize_url("/zabieg skóry/"), "https://yoona.pl/zabieg%20sk%C3%B3ry/")
        for value in ("https://evil.test/", "https://yoona.pl.evil.test/", "https://user:password@yoona.pl/", "https://yoona.pl:8080/", "file:///etc/passwd", "javascript:alert(1)"):
            self.assertIsNone(audit.normalize_url(value), value)
        self.assertFalse(audit.crawl_candidate("https://yoona.pl/p.jpg"))
        self.assertFalse(audit.crawl_candidate("https://yoona.pl/?s=x"))
        self.assertFalse(audit.crawl_candidate("https://yoona.pl/wp-json/wp/v2/pages"))
        self.assertTrue(audit.crawl_candidate("https://yoona.pl/oferta/"))

    def test_redirects_do_not_escape_scope_or_robots(self):
        handler = audit.ScopedRedirectHandler()
        request = Request(audit.ORIGIN)
        headers = Message()
        self.assertIsNone(handler.redirect_request(request, None, 302, "Found", headers, "https://evil.test/"))
        handler.robots_allowed = lambda url: "/blocked/" not in url
        self.assertIsNone(handler.redirect_request(request, None, 302, "Found", headers, "https://yoona.pl/blocked/"))
        self.assertIn("robots", handler.redirects[-1]["not_followed"])
        redirected = handler.redirect_request(request, None, 301, "Moved", headers, "https://www.yoona.pl/oferta/")
        self.assertEqual(redirected.full_url, "https://www.yoona.pl/oferta/")

    def test_robots_scope_and_declared_sitemap(self):
        robot, sitemaps = audit.parse_robots('''User-agent: *
Disallow: /prywatne/
Allow: /prywatne/publiczne/
Crawl-delay: 2
Sitemap: https://www.yoona.pl/sitemap.xml
Sitemap: https://evil.test/sitemap.xml
''')
        self.assertFalse(robot.can_fetch(audit.ROBOT_AGENT, "https://yoona.pl/prywatne/"))
        self.assertTrue(robot.can_fetch(audit.ROBOT_AGENT, "https://yoona.pl/oferta/"))
        self.assertEqual(robot.crawl_delay(audit.ROBOT_AGENT), 2)
        self.assertEqual(sitemaps, ["https://yoona.pl/sitemap.xml"])

    def test_sitemap_namespaces_scoping_and_limits(self):
        parsed = audit.parse_sitemap('''<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
          <url><loc>https://evil.test/p</loc></url><url><loc>https://www.yoona.pl/a/</loc></url>
          <url><loc>https://yoona.pl/a/</loc></url><url><loc>/b/</loc></url><url><loc>/c/</loc></url></urlset>''', audit.ORIGIN, limit=2)
        self.assertEqual(parsed, {"urls": ["https://yoona.pl/a/", "https://yoona.pl/b/"], "sitemaps": []})
        parsed = audit.parse_sitemap('<sitemapindex><sitemap><loc>/pages.xml</loc></sitemap></sitemapindex>', audit.ORIGIN)
        self.assertEqual(parsed["sitemaps"], ["https://yoona.pl/pages.xml"])
        with self.assertRaises(ValueError):
            audit.parse_sitemap('<html><body>Not a sitemap</body></html>', audit.ORIGIN)

    def test_crawl_honors_robots_page_limit_and_never_calls_network(self):
        requests = []
        fixtures = {
            "robots.txt": (200, "text/plain", "User-agent: *\nDisallow: /blocked/\n"),
            "sitemap_index.xml": (200, "application/xml", '<sitemapindex><sitemap><loc>/child.xml</loc></sitemap></sitemapindex>'),
            "child.xml": (200, "application/xml", '<urlset><url><loc>/blocked/</loc></url><url><loc>/first/</loc></url><url><loc>/second/</loc></url></urlset>'),
            "": (200, "text/html", '<html lang="pl"><title>Home</title><h1>Home</h1><a href="/extra/">link</a></html>'),
            "first/": (200, "text/html", '<html><title>First</title><h1>First</h1></html>'),
        }
        def fake_fetch(self, url):
            requests.append(url)
            status, content_type, body = fixtures.get(url.removeprefix(audit.ORIGIN), (404, "text/plain", "Not found"))
            return {"url": url, "status": status, "final_url": url, "headers": {"content-type": content_type}, "body": body, "error": None, "environment_blocked": False, "redirects": [], "truncated": False}
        with tempfile.TemporaryDirectory() as directory, patch.object(audit.Fetcher, "fetch", fake_fetch):
            report = audit.run_audit(Path(directory), max_pages=2, max_sitemaps=4)
            self.assertEqual(report["summary"]["html_pages_analyzed"], 2)
            self.assertIn("https://yoona.pl/blocked/", report["robots_skipped"])
            self.assertNotIn("https://yoona.pl/blocked/", requests)
            self.assertNotIn("https://yoona.pl/second/", requests)
            self.assertTrue(report["page_limit_reached"])
            self.assertEqual(len(report["resources"]), 5)  # robots plus four sitemap endpoints
            self.assertTrue((Path(directory) / "report.json").is_file())
            self.assertTrue((Path(directory) / "report.md").is_file())
            self.assertTrue((Path(directory) / report["pages"][0]["text_file"]).is_file())

    def test_environment_block_is_not_site_finding(self):
        self.assertEqual(audit.findings_for({"status": 403, "environment_blocked": True}), [])
        self.assertEqual(audit.findings_for({"status": 403}), [])
        def blocked(self, url):
            return {"url": url, "status": 403, "final_url": url, "headers": {}, "body": "Proxy denied", "error": "Access blocked by execution environment", "environment_blocked": True, "truncated": False}
        with tempfile.TemporaryDirectory() as directory, patch.object(audit.Fetcher, "fetch", blocked):
            report = audit.run_audit(Path(directory))
            self.assertEqual(report["summary"]["status"], "blocked")
            self.assertEqual(report["summary"]["html_pages_analyzed"], 0)
            self.assertEqual(report["summary"]["findings_counts"], {})

    def test_proxy_connect_denial_classified_without_losing_error(self):
        fetcher = audit.Fetcher()
        error = URLError(OSError("Tunnel connection failed: 403 Forbidden"))
        with patch.object(fetcher.opener, "open", side_effect=error):
            result = fetcher.fetch(audit.ORIGIN)
        self.assertTrue(result["environment_blocked"])
        self.assertTrue(result["access_failure"])
        self.assertIsNone(result["status"])  # Site did not send an HTTP response.
        self.assertEqual(result["error"], str(error))

    def test_generic_access_error_is_not_assumed_to_be_proxy_denial(self):
        fetcher = audit.Fetcher()
        error = URLError(OSError("403 Forbidden"))
        with patch.object(fetcher.opener, "open", side_effect=error):
            result = fetcher.fetch(audit.ORIGIN)
        self.assertFalse(result["environment_blocked"])
        self.assertEqual(result["error"], str(error))


if __name__ == "__main__":
    unittest.main()
