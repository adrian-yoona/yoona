#!/usr/bin/env bash
# Focused offer tests on an isolated installation created by run-wordpress.sh.
set -Eeuo pipefail
if [[ "$#" -ne 3 || -z "${YOONA_TEST_EXPORT_JSON:-}" ]]; then
  printf 'Usage: YOONA_TEST_EXPORT_JSON=/private/export.json YOONA_TEST_BROWSER=1 bash tests/run-offer-hero.sh http://127.0.0.1:PORT CLI_CONTAINER WEB_CONTAINER\n' >&2
  exit 2
fi
offer_test_url="$1"
offer_test_cli="$2"
offer_test_web="$3"
offer_test_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
offer_test_input="/tmp/yoona-offer-source-${$}.json"
# This runner is deliberately limited to the disposable test installation.
[[ "$offer_test_url" =~ ^http://127\.0\.0\.1:[0-9]+$ ]]
[[ "$offer_test_cli" == yoona-seo-test-*-cli ]]
[[ "$offer_test_web" == yoona-seo-test-*-web ]]
test -r "$YOONA_TEST_EXPORT_JSON"
cleanup_input() { docker exec -u 0 "$offer_test_cli" rm -f "$offer_test_input" >/dev/null 2>&1 || true; }
trap cleanup_input EXIT
wp_offer_test() { docker exec -u 0 "$offer_test_cli" wp --allow-root --path=/var/www/html "$@"; }
docker cp "$offer_test_root/plugin/yoona-seo/." "$offer_test_web:/var/www/html/wp-content/plugins/yoona-seo/"
docker cp "$offer_test_root/tests/integration-offer-hero.php" "$offer_test_web:/var/www/html/yoona-tests/integration-offer-hero.php"
docker cp "$YOONA_TEST_EXPORT_JSON" "$offer_test_cli:$offer_test_input"
docker exec "$offer_test_web" chmod -R a+rX /var/www/html/wp-content/plugins/yoona-seo /var/www/html/yoona-tests
docker exec "$offer_test_web" sh -c 'find /var/www/html/wp-content/plugins/yoona-seo -name "*.php" -exec php -l {} \;'
wp_offer_test plugin get yoona-seo --field=version
wp_offer_test plugin get seo-by-rank-math --field=version
wp_offer_test eval-file /var/www/html/yoona-tests/integration-offer-hero.php prepare "$offer_test_input" --skip-themes
wp_offer_test eval-file /var/www/html/yoona-tests/integration-offer-hero.php private "$offer_test_input" --skip-themes
# Warm the original block theme before taking the complete database baseline.
wp_offer_test option update yoona_seo_design_enabled 0
python3 - "$offer_test_url" <<'PY'
import sys
from urllib.request import urlopen
with urlopen(sys.argv[1] + '/plotki-ogrodki-piwne/') as response:
    assert response.status == 200
PY
wp_offer_test option delete yoona_seo_design_enabled
wp_offer_test eval-file /var/www/html/yoona-tests/integration-offer-hero.php snapshot --skip-themes
python3 "$offer_test_root/tests/integration-offer-hero-http.py" "$offer_test_url"
if [[ "${YOONA_TEST_BROWSER:-0}" == 1 ]]; then
  node "$offer_test_root/tests/integration-offer-hero-browser.cjs" "$offer_test_url" "$offer_test_cli"
fi
wp_offer_test eval-file /var/www/html/yoona-tests/integration-offer-hero.php verify --skip-themes
wp_offer_test core version
docker exec "$offer_test_cli" php -v | head -n 1
printf 'All focused offer presentation tests passed.\n'
