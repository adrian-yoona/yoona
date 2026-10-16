#!/usr/bin/env bash
# Real WordPress + MariaDB integration tests. No production access or credentials.
set -Eeuo pipefail

test_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
test_prefix="yoona-seo-test-${$}-${RANDOM}"
test_network="${test_prefix}-net"
test_volume="${test_prefix}-wp"
test_database="${test_prefix}-db"
test_web="${test_prefix}-web"
test_cli="${test_prefix}-cli"
test_composer="${test_prefix}-composer"
test_wp_image="${YOONA_TEST_WP_IMAGE:-wordpress:php8.3-apache@sha256:81a34254acaa6ff3b9a67268c2745e9a041c4094cbe5ccd35b75ed3bbdc033b5}"
test_db_image="${YOONA_TEST_DB_IMAGE:-mariadb:11.4@sha256:1292844148b311e4ed4300022a996d39083f415a963e970cf47cad1b3b18e3a6}"
test_cli_image="${YOONA_TEST_CLI_IMAGE:-wordpress:cli@sha256:36243da432a0f0a3b56dc3240ecee9b159108d71305e7c064d987a20a465bfde}"
test_composer_image="${YOONA_TEST_COMPOSER_IMAGE:-composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac}"
test_rank_source="${YOONA_TEST_RANK_MATH_SOURCE:-/tmp/yoona-rank-math-source}"
test_rank_checkout=""

cleanup() {
  local result=$?
  docker exec -u 0 "$test_cli" rm -f /tmp/yoona-source-export.json /tmp/yoona-actual-bundle.json >/dev/null 2>&1 || true
  if [[ "${YOONA_TEST_KEEP:-0}" == 1 ]]; then
    printf 'Keeping local test resources: %s\n' "$test_prefix"
  else
    docker rm -fv "$test_composer" "$test_cli" "$test_web" "$test_database" >/dev/null 2>&1 || true
    docker volume rm "$test_volume" >/dev/null 2>&1 || true
    docker network rm "$test_network" >/dev/null 2>&1 || true
  fi
  if [[ -n "$test_rank_checkout" ]]; then
    rm -rf -- "$test_rank_checkout"
  fi
  return "$result"
}
trap cleanup EXIT

command -v docker >/dev/null
command -v python3 >/dev/null
command -v node >/dev/null
node "$test_root/tests/integration-batch-ui.cjs"
if [[ ! -f "$test_rank_source/vendor/autoload.php" ]]; then
  command -v git >/dev/null
  test_rank_checkout="$(mktemp -d /tmp/yoona-seo-rank-math-test.XXXXXX)"
  test_rank_source="$test_rank_checkout"
  git -C "$test_rank_source" init -q
  git -C "$test_rank_source" remote add origin https://github.com/rankmath/seo-by-rank-math.git
  git -C "$test_rank_source" fetch --depth=1 origin 50fc59587112316ad754ca3c9a26e1282795d49d
  git -C "$test_rank_source" checkout -q --detach FETCH_HEAD
fi
test -f "$test_rank_source/rank-math.php"
test -f "$test_rank_source/vendor/autoload.php"
docker info >/dev/null
for test_image in "$test_wp_image" "$test_db_image" "$test_cli_image" "$test_composer_image"; do
  if ! docker image inspect "$test_image" >/dev/null 2>&1; then
    docker pull "$test_image"
  fi
done

docker network create "$test_network" >/dev/null
docker volume create "$test_volume" >/dev/null
docker run -d --name "$test_database" --network "$test_network" --network-alias database \
  -e MARIADB_DATABASE=yoona_test -e MARIADB_USER=yoona_test \
  -e MARIADB_PASSWORD=local-test-only -e MARIADB_ROOT_PASSWORD=local-root-test-only \
  --health-cmd='healthcheck.sh --connect --innodb_initialized' --health-interval=1s \
  --health-timeout=3s --health-retries=60 "$test_db_image" >/dev/null

docker run -d --name "$test_web" --network "$test_network" \
  -p 127.0.0.1::80 -v "$test_volume:/var/www/html" \
  -e WORDPRESS_DB_HOST=database -e WORDPRESS_DB_NAME=yoona_test \
  -e WORDPRESS_DB_USER=yoona_test -e WORDPRESS_DB_PASSWORD=local-test-only \
  -e 'WORDPRESS_CONFIG_EXTRA=define("WP_HTTP_BLOCK_EXTERNAL", true); define("DISABLE_WP_CRON", true); define("AUTOMATIC_UPDATER_DISABLED", true);' \
  "$test_wp_image" >/dev/null

for test_attempt in {1..60}; do
  if [[ "$(docker inspect -f '{{.State.Health.Status}}' "$test_database")" == healthy ]] \
    && docker exec "$test_web" test -f /var/www/html/wp-config.php; then
    break
  fi
  if [[ "$test_attempt" == 60 ]]; then
    docker logs --tail 30 "$test_database"
    docker logs --tail 30 "$test_web"
    exit 1
  fi
  sleep 1
done

test_address="$(docker port "$test_web" 80/tcp)"
test_url="http://${test_address}"
docker exec "$test_web" mkdir -p /var/www/html/yoona-tests
docker cp "$test_root/tests/." "$test_web:/var/www/html/yoona-tests/"
docker cp "$test_root/plugin/yoona-seo" "$test_web:/var/www/html/wp-content/plugins/yoona-seo"
docker exec "$test_web" mkdir -p /var/www/html/wp-content/plugins/seo-by-rank-math
tar -C "$test_rank_source" --exclude=.git -cf - . | docker exec -i "$test_web" tar -C /var/www/html/wp-content/plugins/seo-by-rank-math -xf -
docker exec "$test_web" chmod -R a+rX /var/www/html/yoona-tests /var/www/html/wp-content/plugins/yoona-seo
docker exec "$test_web" chmod -R a+rX /var/www/html/wp-content/plugins/seo-by-rank-math
# The official Git checkout ships an older generated classmap. Regenerate it
# offline using Composer, without installing packages or running scripts/plugins.
docker run --rm --name "$test_composer" --network none --volumes-from "$test_web" \
  --workdir /var/www/html/wp-content/plugins/seo-by-rank-math \
  -e COMPOSER_ALLOW_SUPERUSER=1 -e COMPOSER_ROOT_VERSION=1.0.280 \
  "$test_composer_image" dump-autoload --no-dev --no-plugins --no-scripts --optimize

docker run -d --name "$test_cli" --network "$test_network" --volumes-from "$test_web" \
  -e WORDPRESS_DB_HOST=database -e WORDPRESS_DB_NAME=yoona_test \
  -e WORDPRESS_DB_USER=yoona_test -e WORDPRESS_DB_PASSWORD=local-test-only \
  -e 'WORDPRESS_CONFIG_EXTRA=define("WP_HTTP_BLOCK_EXTERNAL", true); define("DISABLE_WP_CRON", true); define("AUTOMATIC_UPDATER_DISABLED", true);' \
  --entrypoint sh "$test_cli_image" -c 'while :; do sleep 30; done' >/dev/null

wp_test() { docker exec -u 0 "$test_cli" wp --allow-root --path=/var/www/html "$@"; }

printf 'Local WordPress test URL: %s\n' "$test_url"
docker exec "$test_web" sh -c 'find /var/www/html/wp-content/plugins/yoona-seo /var/www/html/yoona-tests -name "*.php" -exec php -l {} \;'
wp_test core install --url="$test_url" --title='Yoona local integration tests' \
  --admin_user=yoona_test_admin --admin_password=local-admin-test-only \
  --admin_email=yoona-test@example.invalid --skip-email --skip-plugins --skip-themes
wp_test eval-file /var/www/html/yoona-tests/prepare-wordpress.php --skip-plugins --skip-themes
wp_test plugin activate yoona-seo
wp_test eval-file /var/www/html/yoona-tests/integration-wordpress.php --skip-themes
wp_test eval-file /var/www/html/yoona-tests/integration-seo-provider.php --skip-themes
wp_test eval-file /var/www/html/yoona-tests/test-divi.php --skip-themes
python3 "$test_root/tests/integration-http.py" "$test_url"
wp_test eval-file /var/www/html/yoona-tests/integration-design.php prepare --skip-themes
wp_test rewrite flush --hard
wp_test option update yoona_seo_design_enabled 0
python3 "$test_root/tests/integration-design-http.py" "$test_url" warm-original
wp_test option delete yoona_seo_design_enabled
wp_test eval-file /var/www/html/yoona-tests/integration-design.php snapshot --skip-themes
python3 "$test_root/tests/integration-design-http.py" "$test_url"
wp_test eval-file /var/www/html/yoona-tests/integration-design.php verify --skip-themes
wp_test option update rank_math_registration_skip 1
wp_test plugin activate seo-by-rank-math
wp_test plugin get seo-by-rank-math --field=version
wp_test eval-file /var/www/html/yoona-tests/integration-divi-rank-math.php --skip-themes
python3 "$test_root/tests/integration-http.py" "$test_url" rank_math
wp_test eval-file /var/www/html/yoona-tests/integration-native-persistence.php --skip-themes
python3 "$test_root/tests/integration-native-http.py" "$test_url"
wp_test plugin deactivate yoona-seo
python3 "$test_root/tests/integration-native-http.py" "$test_url"
wp_test plugin activate yoona-seo
wp_test eval-file /var/www/html/yoona-tests/integration-native-persistence.php rollback --skip-themes
wp_test eval-file /var/www/html/yoona-tests/integration-design.php snapshot --skip-themes
python3 "$test_root/tests/integration-design-http.py" "$test_url" rank_math
wp_test eval-file /var/www/html/yoona-tests/integration-design.php verify --skip-themes
wp_test eval-file /var/www/html/yoona-tests/integration-design.php protect-home --skip-themes
python3 "$test_root/tests/integration-design-http.py" "$test_url" protected-home
wp_test eval-file /var/www/html/yoona-tests/integration-design.php restore-home --skip-themes
if [[ "${YOONA_TEST_BROWSER:-0}" == 1 ]]; then
  node "$test_root/tests/integration-design-browser.cjs" "$test_url"
fi
wp_test eval-file /var/www/html/yoona-tests/integration-design.php verify --skip-themes
if [[ -n "${YOONA_TEST_EXPORT_JSON:-}" ]]; then
  test -r "$YOONA_TEST_EXPORT_JSON"
  # Private source input lives only in the CLI container, outside the shared
  # document root. A separate table prefix is never served by the web container.
  docker cp "$YOONA_TEST_EXPORT_JSON" "$test_cli:/tmp/yoona-source-export.json"
  docker cp "$test_root/plugin/yoona-seo/content/changes.json" "$test_cli:/tmp/yoona-actual-bundle.json"
  wp_real_test() { docker exec -e WORDPRESS_TABLE_PREFIX=yoona_real_ -u 0 "$test_cli" wp --allow-root --path=/var/www/html "$@"; }
  wp_real_test core install --url=https://yoona.pl --title='Private source-copy fixture' \
    --admin_user=yoona_test_admin --admin_password=local-admin-test-only \
    --admin_email=yoona-test@example.invalid --skip-email --skip-plugins --skip-themes
  wp_real_test option update rank_math_registration_skip 1 --skip-plugins
  wp_real_test plugin activate seo-by-rank-math yoona-seo
  wp_real_test eval-file /var/www/html/yoona-tests/integration-real-bundle.php \
    /tmp/yoona-source-export.json /tmp/yoona-actual-bundle.json --skip-themes
  docker exec -u 0 "$test_cli" rm -f /tmp/yoona-source-export.json /tmp/yoona-actual-bundle.json
fi
wp_test core version
docker exec "$test_cli" php -v | head -n 1
printf 'All real WordPress integration tests passed.\n'
