#!/usr/bin/env python3
"""Add reviewed service-intro hashes without copying private WordPress source.

The private export remains an input only. Every frozen SEO patch is checked
against its exact inner-body SHA-256 before applying it in memory. The shortcode
scanner respects quoted attribute values, including literal square brackets.
This builder is not a WordPress renderer or a replacement for Divi::prepare;
the regression suite independently verifies its variants using that PHP helper.
"""
from __future__ import annotations

import argparse
from collections import Counter
from dataclasses import dataclass
from hashlib import sha256
import json
from pathlib import Path
import re


FROZEN_BUNDLE_SHA256 = "e97c45484286479ed153ab88fc7000231e1607b40f1a5d71df51f9c3401c1f85"
APPEND_PATHS = {"/flagi/", "/totemy/"}
MARKER = re.compile(r"\[(/?)(et_pb_[A-Za-z0-9_]+)(?![A-Za-z0-9_-])")
TEXT_ONLY_INTRO = Counter(et_pb_section=1, et_pb_row=1, et_pb_column=1, et_pb_text=3)


def digest(value: str) -> str:
    return sha256(value.encode("utf-8")).hexdigest()


@dataclass(frozen=True)
class Module:
    name: str
    start: int
    inner_start: int
    inner_end: int
    end: int


def inspect(source: str) -> list[Module]:
    """Find paired Divi byte-preserving ranges with a quote-aware tag scanner."""
    if not isinstance(source, str) or len(source.encode("utf-8")) > 5 * 1024 * 1024:
        raise ValueError("Divi source must be a string of at most 5 MiB.")
    cursor = 0
    stack: list[tuple[str, int, int]] = []
    modules: list[Module] = []
    while match := MARKER.search(source, cursor):
        start = match.start()
        if start and source[start - 1] == "[":
            raise ValueError("Escaped Divi shortcode is not an approved source.")
        name = match[2]
        if name.lower() != name:
            raise ValueError("Mixed-case Divi shortcode is not an approved source.")
        quote = None
        end = match.end()
        while end < len(source):
            char = source[end]
            if quote:
                if char == quote:
                    quote = None
            elif char in ("'", '"'):
                quote = char
            elif char == "]":
                break
            elif char == "[":
                raise ValueError("Unquoted nested bracket in a Divi opening tag.")
            end += 1
        if end == len(source) or quote or source[end + 1:end + 2] == "]":
            raise ValueError("Unterminated or escaped Divi shortcode.")
        token = source[start:end + 1]
        if match[1]:
            if token != f"[/{name}]" or not stack or stack[-1][0] != name:
                raise ValueError("Divi shortcode nesting does not balance.")
            opened_name, opened_start, inner_start = stack.pop()
            modules.append(Module(opened_name, opened_start, inner_start, start, end + 1))
        elif name == "et_pb_line_break_holder" and token == "[et_pb_line_break_holder]":
            pass
        elif token.endswith("/]"):
            if name in ("et_pb_text", "et_pb_toggle"):
                raise ValueError("Self-closing Divi text is not an approved source.")
            modules.append(Module(name, start, end + 1, end + 1, end + 1))
        else:
            if source[match.end():match.end() + 1] not in (" ", "\t", "\r", "\n", "]"):
                raise ValueError("Unexpected characters after the Divi shortcode name.")
            if name in ("et_pb_text", "et_pb_toggle") and any(item[0] == name for item in stack):
                raise ValueError("Nested Divi text is not an approved source.")
            stack.append((name, start, end + 1))
        cursor = end + 1
    if stack:
        raise ValueError("Unclosed Divi shortcode in the source.")
    modules.sort(key=lambda module: module.start)
    return modules


def prepare(source: str, patches: list[dict]) -> str:
    modules = inspect(source)
    by_type = {
        kind: [module for module in modules if module.name == kind]
        for kind in ("et_pb_text", "et_pb_toggle")
    }
    replacements = []
    seen = set()
    for patch in patches:
        if not isinstance(patch, dict) or set(patch) - {"module_type", "module_index", "expected_html_sha256", "html"}:
            raise ValueError("Unexpected frozen patch fields.")
        kind = patch.get("module_type", "et_pb_text")
        index = patch.get("module_index")
        if kind not in by_type or type(index) is not int or index < 0 or index >= len(by_type[kind]):
            raise ValueError("Frozen patch has no uniquely indexed source module.")
        if (kind, index) in seen:
            raise ValueError("Duplicate frozen patch target.")
        seen.add((kind, index))
        module = by_type[kind][index]
        if digest(source[module.inner_start:module.inner_end]) != patch.get("expected_html_sha256"):
            raise ValueError("Frozen patch inner-body SHA-256 does not match.")
        replacement = patch.get("html")
        if not isinstance(replacement, str) or re.search(r"\[/?[^\x00-\x20<>&/\[\]=]+", replacement):
            raise ValueError("Frozen replacement is not plain reviewed HTML.")
        replacements.append((module.inner_start, module.inner_end, replacement))
    for start, end, html in sorted(replacements, reverse=True):
        source = source[:start] + html + source[end:]
    inspect(source)
    return source


def first_sections(source: str) -> list[Module]:
    sections = [module for module in inspect(source) if module.name == "et_pb_section"]
    if len(sections) < 2 or sections[0].start != 0 or sections[1].start != sections[0].end:
        raise ValueError("The reviewed hero and intro must be adjacent leading sections.")
    return sections[:2]


def build(source_path: Path, design_path: Path, bundle_path: Path) -> tuple[dict, dict]:
    bundle_bytes = bundle_path.read_bytes()
    if sha256(bundle_bytes).hexdigest() != FROZEN_BUNDLE_SHA256:
        raise ValueError("The frozen SEO bundle changed; review it before regenerating service layouts.")
    bundle = json.loads(bundle_bytes)
    source = json.loads(source_path.read_text(encoding="utf-8"))
    if source.get("truncated"):
        raise ValueError("The private export is truncated.")
    records = {record["path"]: record for record in source["pages"]}
    patches = {record["path"]: record for record in bundle["pages"]}
    if len(records) != len(source["pages"]) or len(patches) != len(bundle["pages"]):
        raise ValueError("Duplicate source or bundle path.")
    design = json.loads(design_path.read_text(encoding="utf-8"))
    heroes = design.get("offer_heroes", {})
    if len(heroes) != 41 or not APPEND_PATHS <= heroes.keys():
        raise ValueError("Exactly 41 reviewed offer definitions are required.")
    modes = Counter()
    hash_count = 0
    patch_count = 0
    for path, hero in heroes.items():
        record = records[path]
        original = record["post_content"]
        patch = patches[path]
        if record["post_type"] != "page" or digest(original) != record["content_sha256"] or digest(original) != patch.get("expected_content_sha256"):
            raise ValueError(f"Complete private/frozen source SHA-256 mismatch: {path}")
        patched = prepare(original, patch.get("divi_text", []))
        patch_count += len(patch.get("divi_text", []))
        hashes = []
        mode = "append" if path in APPEND_PATHS else "replace"
        for variant in (original, patched):
            header, intro = first_sections(variant)
            if digest(variant[header.start:header.end]) not in hero.get("section_sha256", []):
                raise ValueError(f"Original or known SEO hero is not already approved: {path}")
            intro_source = variant[intro.start:intro.end]
            counts = Counter(module.name for module in inspect(intro_source))
            if mode == "replace":
                if counts != TEXT_ONLY_INTRO or re.search(r"<(?:img|form|iframe|picture|video|audio)\b", intro_source, re.I):
                    raise ValueError(f"Intro contains protected media or non-text modules: {path}")
            elif counts["et_pb_image"] != (1 if path == "/flagi/" else 3):
                raise ValueError(f"Mixed-media intro no longer matches its reviewed structure: {path}")
            value = digest(intro_source)
            if value not in hashes:
                hashes.append(value)
        hero["service_intro_sha256"] = hashes
        hero["service_intro_mode"] = mode
        modes[mode] += 1
        hash_count += len(hashes)
    if modes != Counter(replace=39, append=2):
        raise ValueError("The expected 39 text-only and two mixed-media intros were not found.")
    return design, {"offers": len(heroes), "modes": dict(modes), "intro_hashes": hash_count, "validated_inner_patches": patch_count}


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--source", type=Path, required=True, help="Private WordPress diagnostic export; never written to the repository.")
    parser.add_argument("--design", type=Path, default=Path("plugin/yoona-seo/assets/design-data.json"))
    parser.add_argument("--bundle", type=Path, default=Path("plugin/yoona-seo/content/changes.json"))
    parser.add_argument("--check", action="store_true", help="Verify the current manifest without changing files.")
    args = parser.parse_args()
    design, summary = build(args.source, args.design, args.bundle)
    output = json.dumps(design, ensure_ascii=False, indent=2) + "\n"
    if args.check:
        if args.design.read_text(encoding="utf-8") != output:
            raise SystemExit("The service layout manifest differs from the reviewed source hashes.")
    else:
        args.design.write_text(output, encoding="utf-8")
    print(json.dumps(summary, ensure_ascii=False))


if __name__ == "__main__":
    main()
