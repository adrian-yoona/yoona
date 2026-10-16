#!/usr/bin/env python3
"""Create and verify the WordPress ZIP from the reviewed plugin files only."""
from hashlib import sha256
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo


def main() -> None:
    root = Path(__file__).resolve().parent.parent
    source = root / "plugin" / "yoona-seo"
    expected = {
        "yoona-seo.php", "readme.txt", "content/changes.json",
        "includes/class-yoona-seo-engine.php", "includes/class-yoona-seo-admin.php",
        "includes/class-yoona-seo-divi.php",
        "includes/class-yoona-seo-design.php",
        "includes/class-yoona-seo-offer-hero.php",
        "templates/header.php", "templates/footer.php", "templates/front-page.php",
        "templates/content.php", "templates/archive.php",
        "templates/offer-hero.php",
        "assets/design.css", "assets/design-data.json",
        "assets/fonts/archivo-black-latin-400-normal.woff2",
        "assets/fonts/archivo-black-latin-ext-400-normal.woff2",
        "assets/fonts/OFL-Archivo-Black.txt", "assets/fonts/README.md",
        "assets/fonts/manrope-latin-wght-normal.woff2",
        "assets/fonts/manrope-latin-ext-wght-normal.woff2",
        "assets/fonts/OFL-Manrope.txt", "assets/fonts/README-Manrope.md",
    }
    actual = {path.relative_to(source).as_posix() for path in source.rglob("*") if path.is_file()}
    if actual != expected:
        raise ValueError(f"Unexpected plugin files: {actual ^ expected}")
    target = root / "downloads" / "yoona-seo-v3.2.zip"
    target.parent.mkdir(exist_ok=True)
    with ZipFile(target, "w", ZIP_DEFLATED, compresslevel=9) as archive:
        for relative in sorted(expected):
            path = source / relative
            if path.is_symlink():
                raise ValueError("Symlinks are not allowed in the installation package.")
            info = ZipInfo.from_file(path, arcname="yoona-seo/" + relative)
            info.compress_type = ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, path.read_bytes())
    with ZipFile(target) as archive:
        if archive.testzip() is not None:
            raise ValueError("ZIP CRC verification failed.")
        for name in archive.namelist():
            if archive.read(name) != (root / "plugin" / name).read_bytes():
                raise ValueError(f"ZIP does not match the source: {name}")
    payload = target.read_bytes()
    digest = sha256(payload).hexdigest()
    target.with_suffix(".zip.sha256").write_text(digest + "  " + target.name + "\n")
    # Keep the first shared download URL useful: it now points to the current version.
    alias = root / "downloads" / "yoona-seo.zip"
    alias.write_bytes(payload)
    alias.with_suffix(".zip.sha256").write_text(digest + "  " + alias.name + "\n")
    print(f"{target}: {len(payload)} bytes; SHA-256 {digest}")


if __name__ == "__main__":
    main()
