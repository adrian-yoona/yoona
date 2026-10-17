#!/usr/bin/env bash
# Focused service/project guides on a disposable run-wordpress.sh installation.
set -Eeuo pipefail
if [[ "$#" -ne 3 ]]; then
  printf 'Usage: YOONA_TEST_BROWSER=1 bash tests/run-growth.sh http://127.0.0.1:PORT CLI_CONTAINER WEB_CONTAINER\n' >&2
  exit 2
fi
growth_test_url="$1"
growth_test_cli="$2"
growth_test_web="$3"
growth_test_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
[[ "$growth_test_url" =~ ^http://127\.0\.0\.1:[0-9]+$ ]]
[[ "$growth_test_cli" == yoona-seo-test-*-cli ]]
[[ "$growth_test_web" == yoona-seo-test-*-web ]]
wp_growth() { docker exec -u 0 "$growth_test_cli" wp --allow-root --path=/var/www/html "$@"; }
docker cp "$growth_test_root/plugin/yoona-seo/." "$growth_test_web:/var/www/html/wp-content/plugins/yoona-seo/"
docker cp "$growth_test_root/tests/integration-growth.php" "$growth_test_web:/var/www/html/yoona-tests/integration-growth.php"
docker exec "$growth_test_web" chmod -R a+rX /var/www/html/wp-content/plugins/yoona-seo /var/www/html/yoona-tests
docker exec "$growth_test_web" sh -c 'find /var/www/html/wp-content/plugins/yoona-seo -name "*.php" -exec php -l {} \;'
wp_growth plugin get yoona-seo --field=version
wp_growth plugin get seo-by-rank-math --field=version
wp_growth eval-file /var/www/html/yoona-tests/integration-growth.php prepare --skip-themes
wp_growth eval-file /var/www/html/yoona-tests/integration-growth.php guards --skip-themes
if [[ -n "${YOONA_TEST_EXPORT_JSON:-}" ]]; then
  test -r "$YOONA_TEST_EXPORT_JSON"
  growth_test_private="/tmp/yoona-growth-private-${$}.json"
  cleanup_growth_input() { docker exec -u 0 "$growth_test_cli" rm -f "$growth_test_private" >/dev/null 2>&1 || true; }
  trap cleanup_growth_input EXIT
  docker cp "$YOONA_TEST_EXPORT_JSON" "$growth_test_cli:$growth_test_private"
  wp_growth eval-file /var/www/html/yoona-tests/integration-growth.php private "$growth_test_private" --skip-themes
  cleanup_growth_input
fi
# Rank Math legitimately writes an Open Graph image cache on a post's first
# view. Warm these synthetic native-image posts with the presentation disabled,
# then preserve every row, including those cache rows, without exclusions.
wp_growth option update yoona_seo_design_enabled 0
python3 - "$growth_test_url" <<'PY'
import sys
from urllib.request import urlopen
for path in ('/growth-project-color/', '/growth-project-pylons/', '/growth-project-awning/'):
    with urlopen(sys.argv[1] + path) as response:
        assert response.status == 200
print('Success: Three native Rank Math project image caches warmed before the strict all-row baseline.')
PY
wp_growth option delete yoona_seo_design_enabled
wp_growth eval-file /var/www/html/yoona-tests/integration-growth.php snapshot --skip-themes
if [[ "${YOONA_TEST_BROWSER:-0}" == 1 ]]; then
  node "$growth_test_root/tests/integration-growth-browser.cjs" "$growth_test_url" "$growth_test_cli"
fi
wp_growth eval-file /var/www/html/yoona-tests/integration-growth.php verify --skip-themes
wp_growth core version
docker exec "$growth_test_cli" php -v | head -n 1
printf 'All focused service/project growth tests passed.\n'
