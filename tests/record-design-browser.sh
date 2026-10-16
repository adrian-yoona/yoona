#!/usr/bin/env bash
# Preserve the last successful targeted Chromium report until the next success.
set -Eeuo pipefail
test_directory="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
test_log="$(mktemp "$test_directory/.design-browser-run.XXXXXX")"
trap 'rm -f -- "$test_log"' EXIT
node "$test_directory/integration-design-browser.cjs" "$@" 2>&1 | tee "$test_log"
mv -- "$test_log" "$test_directory/design-browser-results.txt"
