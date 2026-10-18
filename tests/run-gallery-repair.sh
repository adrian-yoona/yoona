#!/usr/bin/env bash
# Minimal real WordPress/Rank Math regression harness. No production credentials.
set -Eeuo pipefail
repair_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
repair_prefix="yoona-seo-test-gallery-${$}-${RANDOM}"
repair_network="${repair_prefix}-net"
repair_volume="${repair_prefix}-wp"
repair_db="${repair_prefix}-db"
repair_web="${repair_prefix}-web"
repair_cli="${repair_prefix}-cli"
repair_composer="${repair_prefix}-composer"
repair_rank="${YOONA_TEST_RANK_MATH_SOURCE:-/tmp/yoona-gallery-rank-source}"
repair_rank_checkout=''
repair_source="${YOONA_GALLERY_PLUGIN_SOURCE:-$repair_root/plugin/yoona-seo}"
repair_wp_image='wordpress:php8.3-apache@sha256:81a34254acaa6ff3b9a67268c2745e9a041c4094cbe5ccd35b75ed3bbdc033b5'
repair_db_image='mariadb:11.4@sha256:1292844148b311e4ed4300022a996d39083f415a963e970cf47cad1b3b18e3a6'
repair_cli_image='wordpress:cli@sha256:36243da432a0f0a3b56dc3240ecee9b159108d71305e7c064d987a20a465bfde'
repair_composer_image='composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac'
repair_cleanup() {
  local repair_status=$?
  docker exec -u 0 "$repair_cli" rm -f /tmp/yoona-gallery-private-source.json >/dev/null 2>&1 || true
  if [[ "${YOONA_TEST_KEEP:-0}" == 1 ]]; then
    printf 'Keeping owned gallery test resources: %s\n' "$repair_prefix"
  else
    docker rm -fv "$repair_composer" "$repair_cli" "$repair_web" "$repair_db" >/dev/null 2>&1 || true
    docker volume rm "$repair_volume" >/dev/null 2>&1 || true
    docker network rm "$repair_network" >/dev/null 2>&1 || true
  fi
  if [[ -n "$repair_rank_checkout" ]]; then rm -rf -- "$repair_rank_checkout"; fi
  return "$repair_status"
}
trap repair_cleanup EXIT
if [[ ! -f "$repair_rank/vendor/autoload.php" ]]; then
  repair_rank_checkout="$(mktemp -d /tmp/yoona-gallery-rank-source.XXXXXX)"
  repair_rank="$repair_rank_checkout"
  git -C "$repair_rank" init -q
  git -C "$repair_rank" remote add origin https://github.com/rankmath/seo-by-rank-math.git
  git -C "$repair_rank" fetch --depth=1 origin 50fc59587112316ad754ca3c9a26e1282795d49d
  git -C "$repair_rank" checkout -q --detach FETCH_HEAD
fi
test -f "$repair_rank/vendor/autoload.php"
docker network create "$repair_network" >/dev/null
docker volume create "$repair_volume" >/dev/null
docker run -d --name "$repair_db" --network "$repair_network" --network-alias database \
  -e MARIADB_DATABASE=yoona_test -e MARIADB_USER=yoona_test -e MARIADB_PASSWORD=local-test-only -e MARIADB_ROOT_PASSWORD=local-root-test-only \
  --health-cmd='healthcheck.sh --connect --innodb_initialized' --health-interval=1s --health-timeout=3s --health-retries=60 "$repair_db_image" >/dev/null
docker run -d --name "$repair_web" --network "$repair_network" -p 127.0.0.1::80 -v "$repair_volume:/var/www/html" \
  -e WORDPRESS_DB_HOST=database -e WORDPRESS_DB_NAME=yoona_test -e WORDPRESS_DB_USER=yoona_test -e WORDPRESS_DB_PASSWORD=local-test-only \
  -e 'WORDPRESS_CONFIG_EXTRA=define("WP_HTTP_BLOCK_EXTERNAL", true); define("DISABLE_WP_CRON", true); define("AUTOMATIC_UPDATER_DISABLED", true);' "$repair_wp_image" >/dev/null
for repair_attempt in {1..60}; do
  if [[ "$(docker inspect -f '{{.State.Health.Status}}' "$repair_db")" == healthy ]] && docker exec "$repair_web" test -f /var/www/html/wp-config.php; then break; fi
  [[ "$repair_attempt" != 60 ]]
  sleep 1
done
docker exec "$repair_web" mkdir -p /var/www/html/yoona-tests /var/www/html/wp-content/plugins/yoona-seo /var/www/html/wp-content/plugins/seo-by-rank-math
docker cp "$repair_source/." "$repair_web:/var/www/html/wp-content/plugins/yoona-seo/"
docker cp "$repair_root/tests/gallery-repair.php" "$repair_web:/var/www/html/yoona-tests/gallery-repair.php"
tar -C "$repair_rank" --exclude=.git -cf - . | docker exec -i "$repair_web" tar -C /var/www/html/wp-content/plugins/seo-by-rank-math -xf -
docker exec "$repair_web" chmod -R a+rX /var/www/html/yoona-tests /var/www/html/wp-content/plugins/yoona-seo /var/www/html/wp-content/plugins/seo-by-rank-math
docker run --rm --name "$repair_composer" --network none --volumes-from "$repair_web" --workdir /var/www/html/wp-content/plugins/seo-by-rank-math \
 -e COMPOSER_ALLOW_SUPERUSER=1 -e COMPOSER_ROOT_VERSION=1.0.280 "$repair_composer_image" dump-autoload --no-dev --no-plugins --no-scripts --optimize
docker run -d --name "$repair_cli" --network "$repair_network" --volumes-from "$repair_web" \
 -e WORDPRESS_DB_HOST=database -e WORDPRESS_DB_NAME=yoona_test -e WORDPRESS_DB_USER=yoona_test -e WORDPRESS_DB_PASSWORD=local-test-only \
 -e 'WORDPRESS_CONFIG_EXTRA=define("WP_HTTP_BLOCK_EXTERNAL", true); define("DISABLE_WP_CRON", true); define("AUTOMATIC_UPDATER_DISABLED", true);' \
 --entrypoint sh "$repair_cli_image" -c 'while :; do sleep 30; done' >/dev/null
repair_url="http://$(docker port "$repair_web" 80/tcp)"
printf '%s\n%s\n%s\n%s\n%s\n' "$repair_prefix" "$repair_url" "$repair_cli" "$repair_web" "$repair_volume" > /tmp/yoona-gallery-active-environment
wp_repair() { docker exec -u 0 "$repair_cli" wp --allow-root --path=/var/www/html "$@"; }
wp_repair core install --url="$repair_url" --title='Gallery regression tests' --admin_user=yoona_test_admin --admin_password=local-admin-test-only --admin_email=yoona-test@example.invalid --skip-email --skip-plugins --skip-themes
wp_repair option update rank_math_registration_skip 1 --skip-plugins
wp_repair plugin activate seo-by-rank-math yoona-seo
wp_repair eval-file /var/www/html/yoona-tests/gallery-repair.php prepare --skip-themes
wp_repair rewrite flush --hard
docker exec "$repair_web" sh -c 'find /var/www/html/wp-content/plugins/yoona-seo -name "*.php" -exec php -l {} \;'
if [[ -n "${YOONA_TEST_EXPORT_JSON:-}" ]]; then
  test -r "$YOONA_TEST_EXPORT_JSON"
  docker cp "$YOONA_TEST_EXPORT_JSON" "$repair_cli:/tmp/yoona-gallery-private-source.json"
  wp_repair eval-file /var/www/html/yoona-tests/gallery-repair.php private /tmp/yoona-gallery-private-source.json --skip-themes
  docker exec -u 0 "$repair_cli" rm -f /tmp/yoona-gallery-private-source.json
fi
printf 'Gallery repair test URL: %s\n' "$repair_url"
if [[ "${YOONA_GALLERY_PREPARE_ONLY:-0}" != 1 ]]; then
  node "$repair_root/tests/gallery-repair.cjs" "$repair_url" "$repair_cli"
fi
wp_repair core version
wp_repair plugin get seo-by-rank-math --field=version
