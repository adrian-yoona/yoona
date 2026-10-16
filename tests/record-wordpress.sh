#!/usr/bin/env bash
# Preserve the last successful report until the complete next run succeeds.
set -Eeuo pipefail
test_directory="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
test_log="$(mktemp "$test_directory/.wordpress-run.XXXXXX")"
trap 'rm -f -- "$test_log"' EXIT
bash "$test_directory/run-wordpress.sh" 2>&1 | tee "$test_log"
mv -- "$test_log" "$test_directory/wordpress-integration-results.txt"
