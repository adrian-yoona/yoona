"""Offline evidence, scope and privacy tests for the WordPress export analyzer."""
from importlib.util import module_from_spec, spec_from_file_location
import json
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

SPEC = spec_from_file_location("audit_export", Path(__file__).resolve().parents[1] / "tools" / "audit_export.py")
audit = module_from_spec(SPEC)
SPEC.loader.exec_module(audit)


def page(post_id=1, content="<p>Projekt reklamowy.</p>", meta=None, kind="page"):
    return {"post_id": post_id, "post_type": kind, "url": f"https://yoona.pl/p{post_id}/", "path": f"/p{post_id}/", "post_title": f"Projekt {post_id}", "post_content": content, "seo_metadata": meta if meta is not None else {}, "builders": [], "content_sha256": "sample"}


class ExportAuditTests(unittest.TestCase):
    def test_divi_source_extraction_does_not_execute_code_or_assume_rendered_headings(self):
        parsed = audit.parse_body('''[et_pb_section background_image="asset.jpg"]
          [et_pb_text label="A ] bracket"]<h1>Oferta <em>firmowa</em></h1><h2>Zakres</h2><p>Opis realizacji.</p>[/et_pb_text]
          [et_pb_toggle title="Pytanie o wycenę?" title_level="h3"]Odpowiedź.[/et_pb_toggle]
          [et_pb_button button_url="https://www.yoona.pl/kontakt/#x"][/et_pb_button]
          [et_pb_image src="a.jpg"][/et_pb_image]
          [et_pb_code]<script>secret</script><h1>Code-generated unknown</h1>[/et_pb_code]
          [/et_pb_section]''', "https://yoona.pl/")
        self.assertEqual(parsed["headings"], [{"level": 1, "text": "Oferta firmowa"}, {"level": 2, "text": "Zakres"}])
        self.assertEqual(parsed["divi_heading_candidates"], [{"module": "et_pb_toggle", "text": "Pytanie o wycenę?", "configured_level": "h3"}])
        self.assertEqual(parsed["internal_links"], ["https://yoona.pl/kontakt/"])
        self.assertEqual(parsed["divi_image_modules"], 1)
        self.assertNotIn("secret", parsed["text"])
        self.assertNotIn("Code-generated", parsed["text"])
        self.assertNotIn("background_image", parsed["text"])
        self.assertEqual(parsed["code_modules_excluded"], 1)

    def test_analyzes_all_types_and_stored_metadata_not_final_html(self):
        source = {"pages": [page(1, meta={"rank_math_title": "Kontakt: 5 powodów", "rank_math_description": "Opis"}), page(2, meta=[], kind="post")], "configuration": {"blog_public": "1"}, "truncated": False}
        report, private = audit.analyze(source, "hash")
        self.assertEqual(report["source"]["items"], 2)
        self.assertEqual(report["counts"]["all"]["rank_math_title_not_stored"], 1)
        self.assertEqual(report["counts"]["post"]["rank_math_description_not_stored"], 1)
        self.assertEqual(report["counts"]["page"]["numeric_title_without_numbered_headings_or_li"], 1)
        self.assertEqual(len(private), 1)
        self.assertTrue(any("szablonu Rank Math" in text for text in report["unknowns"]))
        self.assertNotIn("missing_final_html_title", report["items"][1])

    def test_duplicates_and_unverified_internal_targets(self):
        source = {"pages": [page(1, '<a href="/p2/">Znana</a><a href="/category/x/">Archiwum</a><a href="https://external.test">Obca</a>', {"rank_math_title": "  Realizacja   reklamy ", "rank_math_description": "Opis"}), page(2, meta={"rank_math_title": "realizacja reklamy", "rank_math_description": "opis"})]}
        report, _ = audit.analyze(source, "hash")
        self.assertEqual(len(report["duplicate_rank_math_titles_stored"]), 1)
        self.assertEqual(len(report["duplicate_rank_math_descriptions_stored"]), 1)
        self.assertEqual(report["items"][0]["internal_targets_not_in_export_unverified"], ["https://yoona.pl/category/x/"])
        self.assertNotIn("broken_links", report)

    def test_reports_redact_contacts_and_do_not_store_bodies(self):
        source = {"pages": [page(1, '<h1>Kontakt: 123 456 789</h1><p>napisz do a@example.pl</p>', {"rank_math_title": "Kontakt 123 456 789", "rank_math_description": "a@example.pl"}, kind="post")]}
        report, private = audit.analyze(source, "hash")
        serialized = json.dumps(report, ensure_ascii=False)
        self.assertNotIn("123 456 789", serialized)
        self.assertNotIn("a@example.pl", serialized)
        self.assertNotIn("post_content", report["items"][0])
        self.assertNotIn("text_excerpt", report["items"][0])
        self.assertNotIn("a@example.pl", private[0]["text_excerpt"])

    def test_cli_writes_reports_and_private_summary_offline(self):
        with tempfile.TemporaryDirectory() as directory:
            folder = Path(directory)
            source = folder / "private-source.json"
            source.write_text(json.dumps({"pages": [page(kind="post")], "configuration": {}, "truncated": False}))
            output = folder / "audit"
            private = folder / "private" / "posts.json"
            with patch("sys.argv", ["audit_export.py", str(source), "--output-dir", str(output), "--private-posts-summary", str(private)]), patch("builtins.print"):
                audit.main()
            self.assertTrue((output / "export-audit.json").is_file())
            self.assertTrue((output / "AUDYT-SEO-YOONA.md").is_file())
            self.assertEqual(private.stat().st_mode & 0o777, 0o600)
            self.assertEqual(len(json.loads(private.read_text())), 1)


if __name__ == "__main__":
    unittest.main()
