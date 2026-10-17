#!/usr/bin/env bash
# Focused collections tests on an isolated installation from run-wordpress.sh.
set -Eeuo pipefail
if [[ "$#" -ne 3 || -z "${YOONA_TEST_EXPORT_JSON:-}" ]]; then
  printf 'Usage: YOONA_TEST_EXPORT_JSON=/private/export.json YOONA_TEST_BROWSER=1 bash tests/run-collections.sh http://127.0.0.1:PORT CLI_CONTAINER WEB_CONTAINER\n' >&2
  exit 2
fi
collection_test_url="$1"
collection_test_cli="$2"
collection_test_web="$3"
collection_test_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
collection_test_input="/tmp/yoona-collections-private-${$}.json"
[[ "$collection_test_url" =~ ^http://127\.0\.0\.1:[0-9]+$ ]]
[[ "$collection_test_cli" == yoona-seo-test-*-cli ]]
[[ "$collection_test_web" == yoona-seo-test-*-web ]]
test -r "$YOONA_TEST_EXPORT_JSON"
cleanup_input() { docker exec -u 0 "$collection_test_cli" rm -f "$collection_test_input" >/dev/null 2>&1 || true; }
trap cleanup_input EXIT
wp_collection() { docker exec -u 0 "$collection_test_cli" wp --allow-root --path=/var/www/html "$@"; }
docker cp "$collection_test_root/plugin/yoona-seo/." "$collection_test_web:/var/www/html/wp-content/plugins/yoona-seo/"
docker cp "$collection_test_root/tests/integration-collections.php" "$collection_test_web:/var/www/html/yoona-tests/integration-collections.php"
docker cp "$YOONA_TEST_EXPORT_JSON" "$collection_test_cli:$collection_test_input"
docker exec "$collection_test_web" chmod -R a+rX /var/www/html/wp-content/plugins/yoona-seo /var/www/html/yoona-tests
docker exec "$collection_test_web" sh -c 'find /var/www/html/wp-content/plugins/yoona-seo -name "*.php" -exec php -l {} \;'
wp_collection plugin get yoona-seo --field=version
wp_collection plugin get seo-by-rank-math --field=version
wp_collection eval-file /var/www/html/yoona-tests/integration-collections.php prepare "$collection_test_input" --skip-themes
wp_collection eval-file /var/www/html/yoona-tests/integration-collections.php private "$collection_test_input" --skip-themes
wp_collection option update yoona_seo_design_enabled 0
python3 - "$collection_test_url" <<'PY'
import sys
from urllib.request import urlopen
for path in ('/pelna-oferta/', '/nasze-realizacje/', '/plotki-ogrodki-piwne/'):
    with urlopen(sys.argv[1] + path) as response:
        assert response.status == 200
PY
wp_collection option delete yoona_seo_design_enabled
wp_collection eval-file /var/www/html/yoona-tests/integration-collections.php snapshot --skip-themes
python3 "$collection_test_root/tests/integration-collections-http.py" "$collection_test_url"
if [[ "${YOONA_TEST_BROWSER:-0}" == 1 ]]; then
  node "$collection_test_root/tests/integration-collections-browser.cjs" "$collection_test_url" "$collection_test_cli"
fi
wp_collection eval-file /var/www/html/yoona-tests/integration-collections.php verify --skip-themes
wp_collection core version
docker exec "$collection_test_cli" php -v | head -n 1
printf 'All focused collections presentation tests passed.\n'
