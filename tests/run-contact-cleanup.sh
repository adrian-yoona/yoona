#!/usr/bin/env bash
# Focused, entirely synthetic contact-component regression on run-wordpress.sh resources.
set -Eeuo pipefail
if [[ "$#" -ne 3 ]]; then
  printf 'Usage: bash tests/run-contact-cleanup.sh http://127.0.0.1:PORT CLI_CONTAINER WEB_CONTAINER\n' >&2
  exit 2
fi
cleanup_test_url="$1"
cleanup_test_cli="$2"
cleanup_test_web="$3"
cleanup_test_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
[[ "$cleanup_test_url" =~ ^http://127\.0\.0\.1:[0-9]+$ ]]
[[ "$cleanup_test_cli" == yoona-seo-test-*-cli ]]
[[ "$cleanup_test_web" == yoona-seo-test-*-web ]]
wp_cleanup() { docker exec -u 0 "$cleanup_test_cli" wp --allow-root --path=/var/www/html "$@"; }
docker cp "$cleanup_test_root/plugin/yoona-seo/." "$cleanup_test_web:/var/www/html/wp-content/plugins/yoona-seo/"
docker cp "$cleanup_test_root/tests/integration-contact-cleanup.php" "$cleanup_test_web:/var/www/html/yoona-tests/integration-contact-cleanup.php"
docker exec "$cleanup_test_web" chmod -R a+rX /var/www/html/wp-content/plugins/yoona-seo /var/www/html/yoona-tests
docker exec "$cleanup_test_web" sh -c 'find /var/www/html/wp-content/plugins/yoona-seo -name "*.php" -exec php -l {} \;'
wp_cleanup plugin get yoona-seo --field=version
wp_cleanup plugin get seo-by-rank-math --field=version
wp_cleanup eval-file /var/www/html/yoona-tests/integration-contact-cleanup.php prepare --skip-themes
# Rank Math may create rank_math_og_content_image on the first response for a
# newly inserted native-HTML fixture. Initialize normal original-theme and
# current-presentation responses before comparing every stored row. No metadata
# keys or rows are excluded from the private or browser snapshots below.
wp_cleanup option update yoona_seo_design_enabled 0
python3 - "$cleanup_test_url" <<'PYWARM'
import sys
from urllib.request import urlopen
base=sys.argv[1]
for slug in ('cleanup-new-page','cleanup-project','cleanup-edited','cleanup-global','cleanup-html','cleanup-unrelated'):
    with urlopen(base+'/'+slug+'/', timeout=45) as response:
        assert response.status == 200
for query in ('?et_fb=1','?et_pb_preview=1','?preview=true'):
    with urlopen(base+'/cleanup-new-page/'+query, timeout=45) as response:
        assert response.status == 200
print('Warmed original-theme and builder/preview fixture responses before strict snapshots.')
PYWARM
wp_cleanup option delete yoona_seo_design_enabled
python3 - "$cleanup_test_url" <<'PYWARM'
import sys
from urllib.request import urlopen
base=sys.argv[1]
for slug in ('cleanup-new-page','cleanup-project','cleanup-edited','cleanup-global','cleanup-html','cleanup-unrelated'):
    with urlopen(base+'/'+slug+'/', timeout=45) as response:
        assert response.status == 200
for query in ('?cleanup_early_shortcode=1','?cleanup_rendered_transform=1','?cleanup_early_shortcode=1&cleanup_rendered_transform=1'):
    with urlopen(base+'/cleanup-new-page/'+query, timeout=45) as response:
        assert response.status == 200
print('Warmed current-presentation and transformed fixture responses before strict snapshots.')
PYWARM
wp_cleanup eval-file /var/www/html/yoona-tests/integration-contact-cleanup.php private --skip-themes
wp_cleanup eval-file /var/www/html/yoona-tests/integration-contact-cleanup.php snapshot --skip-themes
node "$cleanup_test_root/tests/integration-contact-cleanup-browser.cjs" "$cleanup_test_url" "$cleanup_test_cli"
wp_cleanup eval-file /var/www/html/yoona-tests/integration-contact-cleanup.php verify --skip-themes
printf 'All focused contact cleanup tests passed.\n'
