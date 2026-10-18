#!/usr/bin/env python3
"""Validate public SEO copy, internal references and six distinct service images.

This reads only committed, public plugin data. It does not crawl the live site,
load private exports, contact Google, or modify WordPress or plugin files.
"""
from collections import defaultdict
import json
from pathlib import Path
import re
import sys
from urllib.parse import urlsplit


ROOT = Path(__file__).resolve().parents[1]
CONTENT = ROOT / "plugin/yoona-seo/content"
CHILDREN = (
    "/frezowanie-plexi/",
    "/frezowanie-dibondu/",
    "/frezowanie-elementow-reklamowych/",
    "/markizy-tarasowe/",
    "/markizy-wolnostojace-duo/",
    "/znakowanie-markiz/",
)


def read(path):
    return json.loads(path.read_text(encoding="utf-8"))


def main():
    bundle = read(CONTENT / "changes.json")
    guides = read(CONTENT / "service-guides.json")
    projects = read(CONTENT / "project-guides.json")
    design = read(ROOT / "plugin/yoona-seo/assets/design-data.json")
    errors = []
    findings = {"scope": "public_local_data_only", "errors": errors}
    pages = bundle.get("pages", [])
    paths = [page.get("path", "") for page in pages]
    if len(paths) != len(set(paths)):
        errors.append("Repeated deployment path in changes.json")
    fields = {}
    for field in ("seo_title", "meta_description"):
        by_value = defaultdict(list)
        for page in pages:
            value = page.get(field)
            if not isinstance(value, str) or not value.strip():
                errors.append(f"Missing {field}: {page.get('path')}")
                continue
            if value != value.strip() or "\n" in value or re.search(r"<[^>]*>", value):
                errors.append(f"Non-plain {field}: {page.get('path')}")
            by_value[value].append(page["path"])
        duplicates = [refs for refs in by_value.values() if len(refs) > 1]
        for refs in duplicates:
            errors.append(f"Repeated {field}: {', '.join(refs)}")
        lengths = [len(value) for value in by_value]
        fields[field] = {
            "populated": sum(len(refs) for refs in by_value.values()),
            "duplicate_groups": len(duplicates),
            "min_characters": min(lengths, default=0),
            "max_characters": max(lengths, default=0),
        }
    findings["deployment"] = {
        "pages": len(pages),
        "storage": bundle.get("seo_storage"),
        "fields": fields,
    }

    reference_count = 0
    for path, guide in guides.items():
        for field in ("title", "lead", "overview", "applications", "materials", "process", "pricing", "quote", "faq"):
            if not guide.get(field):
                errors.append(f"Empty service {field}: {path}")
        for related in guide.get("related", []) + guide.get("children", []):
            reference_count += 1
            if related not in guides:
                errors.append(f"Unknown related service: {path} -> {related}")
    navigation_paths = {
        item["path"] for item in design.get("navigation", {}).values()
        if isinstance(item, dict) and isinstance(item.get("path"), str)
    }
    project_references = 0
    for path, guide in projects.items():
        related = [guide.get("service_path", "")] + guide.get("related_paths", [])
        for destination in filter(None, related):
            project_references += 1
            if destination not in guides and destination not in navigation_paths:
                errors.append(f"Unknown project destination: {path} -> {destination}")
    findings["references"] = {
        "services": len(guides),
        "service_links": reference_count,
        "projects": len(projects),
        "project_links": project_references,
        "note": "Definitions only; published status and HTTP responses require WordPress checks.",
    }

    images = []
    for path in CHILDREN:
        guide = guides.get(path, {})
        parent = design.get("offer_heroes", {}).get(guide.get("image_source_path"), {})
        for field in ("hero", "card"):
            image = guide.get(field + "_image", "")
            alt = guide.get(field + "_alt", "")
            parsed = urlsplit(image)
            if (
                parsed.scheme != "https"
                or parsed.netloc != "yoona.pl"
                or not parsed.path.startswith("/wp-content/uploads/")
                or not re.search(r"\.(?:jpg|jpeg|png|webp|avif)$", parsed.path, re.I)
                or parsed.query or parsed.fragment
                or "/../" in parsed.path
            ):
                errors.append(f"Invalid dedicated {field} image: {path}")
            if not isinstance(alt, str) or not alt.strip():
                errors.append(f"Missing dedicated {field} alt: {path}")
            if re.search(r"\b(?:SELT|DUO)\b", alt, re.I):
                errors.append(f"Unverified model claim in {field} alt: {path}")
            if image == parent.get("image"):
                errors.append(f"Parent photograph reused for dedicated {field}: {path}")
            images.append(image)
    if len(set(images)) != len(images):
        errors.append("A photograph is repeated across the six dedicated heroes/cards")
    findings["dedicated_service_images"] = {
        "services": len(CHILDREN), "images": len(images), "distinct_images": len(set(images)),
        "note": "URLs/labels only. This does not confirm live image delivery, crop, or current model identity.",
    }
    findings["passed"] = not errors
    print(json.dumps(findings, ensure_ascii=False, indent=2))
    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main())
