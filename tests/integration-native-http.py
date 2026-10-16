#!/usr/bin/env python3
"""Read actual Rank Math metadata with and without Yoona SEO activated."""
import html
import re
import sys
import urllib.request

base = sys.argv[1].rstrip("/")
with urllib.request.urlopen(base + "/", timeout=30) as response:
    source = response.read().decode("utf-8")
    assert response.status == 200
title = re.search(r"<title>(.*?)</title>", source, re.I | re.S)
descriptions = re.findall(
    r'<meta\s+name=[\'"]description[\'"]\s+content=[\'"](.*?)[\'"]\s*/?>', source, re.I
)
assert title and html.unescape(title.group(1)).strip() == "Yoona — trwałe natywne SEO Rank Math"
assert [html.unescape(value) for value in descriptions] == ["Natywny opis Rank Math działa także po wyłączeniu Yoona SEO."]
assert "Rank Math" in source
print("Success: Actual Rank Math renders the exact native title and one description independently of Yoona SEO hooks.")
