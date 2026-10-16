#!/usr/bin/env python3
"""Assemble reviewed copy against a private WordPress export; never copy that export."""
from __future__ import annotations

import argparse
from collections import Counter
from hashlib import sha256
import json
from pathlib import Path
import re


def digest(value: str) -> str:
    return sha256(value.encode("utf-8")).hexdigest()


def build(source_path: Path, copy_dir: Path) -> tuple[dict, dict]:
    source = json.loads(source_path.read_text(encoding="utf-8"))
    if source.get("truncated"):
        raise ValueError("The source export is truncated; full coverage cannot be claimed.")
    original = {page["path"]: page for page in source["pages"]}
    if len(original) != len(source["pages"]) or None in original:
        raise ValueError("Source paths must be unique supported paths.")
    changes = []
    seen = set()
    patch_count = Counter()
    body_pages = 0
    for filename in ("pages-a.json", "pages-b.json", "posts.json"):
        entries = json.loads((copy_dir / filename).read_text(encoding="utf-8"))
        if not isinstance(entries, list):
            raise ValueError(f"Expected an array: {filename}")
        for page in entries:
            path = page["path"]
            if path in seen or path not in original:
                raise ValueError(f"Unknown or duplicate path: {path}")
            seen.add(path)
            before = original[path]
            for key in ("seo_title", "meta_description"):
                if not isinstance(page.get(key), str) or not page[key].strip() or "\n" in page[key]:
                    raise ValueError(f"Invalid {key}: {path}")
            if page.get("divi_text") or "content" in page:
                if page.get("expected_content_sha256") != digest(before["post_content"]):
                    raise ValueError(f"Full source hash mismatch: {path}")
                body_pages += 1
            targets = set()
            modules = {}
            for patch in page.get("divi_text", []):
                kind = patch.get("module_type", "et_pb_text")
                if kind not in ("et_pb_text", "et_pb_toggle"):
                    raise ValueError(f"Unsupported module type: {kind}")
                index = patch["module_index"]
                if not isinstance(index, int) or isinstance(index, bool) or index < 0:
                    raise ValueError(f"Invalid module index: {path}")
                target = (kind, index)
                if target in targets:
                    raise ValueError(f"Duplicate module patch: {path} {target}")
                targets.add(target)
                if kind not in modules:
                    pattern = rf"\[{kind}\b[^\]]*\]([\s\S]*?)\[/{kind}\]"
                    modules[kind] = list(re.finditer(pattern, before["post_content"]))
                if index >= len(modules[kind]):
                    raise ValueError(f"Missing source module: {path} {target}")
                inner = modules[kind][index].group(1)
                if digest(inner) != patch["expected_html_sha256"]:
                    raise ValueError(f"Module source hash mismatch: {path} {target}")
                if not isinstance(patch.get("html"), str) or re.search(r"\[/?et_pb_", patch["html"]):
                    raise ValueError(f"Invalid replacement HTML: {path} {target}")
                patch_count[kind] += 1
            # Only reviewed fields and replacement HTML are published, never raw source bodies.
            changes.append(page)
    if seen != set(original):
        raise ValueError(f"Missing paths: {sorted(set(original) - seen)}")
    for field in ("seo_title", "meta_description"):
        duplicates = [value for value, count in Counter(p[field].casefold().strip() for p in changes).items() if count > 1]
        if duplicates:
            raise ValueError(f"Duplicate {field}: {duplicates}")
    order = {page["path"]: index for index, page in enumerate(source["pages"])}
    changes.sort(key=lambda page: order[page["path"]])
    bundle = {
        "schema_version": 2,
        "seo_storage": "rank_math",
        "site_url": source["site_url"].rstrip("/"),
        "audit_verified": True,
        "source_exported_at": source.get("exported_at"),
        "pages": changes,
    }
    summary = {
        "items": len(changes),
        "page_types": dict(Counter(original[page["path"]]["post_type"] for page in changes)),
        "body_items": body_pages,
        "body_types": dict(Counter(original[page["path"]]["post_type"] for page in changes if page.get("divi_text") or "content" in page)),
        "module_patches": dict(patch_count),
        "body_paths": [page["path"] for page in changes if page.get("divi_text") or "content" in page],
        "metadata_only_paths": [page["path"] for page in changes if not page.get("divi_text") and "content" not in page],
    }
    return bundle, summary


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--source", required=True, type=Path)
    parser.add_argument("--copy-dir", type=Path, default=Path("copy"))
    parser.add_argument("--output", type=Path, default=Path("plugin/yoona-seo/content/changes.json"))
    parser.add_argument("--summary", type=Path, default=Path("audit/zakres-poprawek.json"))
    args = parser.parse_args()
    bundle, summary = build(args.source, args.copy_dir)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(bundle, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    args.summary.parent.mkdir(parents=True, exist_ok=True)
    args.summary.write_text(json.dumps(summary, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps({key: value for key, value in summary.items() if not key.endswith("paths")}, ensure_ascii=False))
    print(f"Bundle bytes: {args.output.stat().st_size}")


if __name__ == "__main__":
    main()
