<?php
/** A separate PHP process is needed because PHP constants cannot be undefined. */
if (!defined('WP_CLI') || !WP_CLI) {
    exit(1);
}
define('WPSEO_VERSION', 'test-existing-provider');
$ids = get_option('yoona_test_ids');
update_post_meta($ids['home'], '_yoona_seo_description', 'Opis dostarczany do istniejącej wtyczki SEO.');
$GLOBALS['wp_query'] = new WP_Query(array('page_id' => $ids['home']));
ob_start();
Yoona_SEO_Plugin::description_tag();
$html = ob_get_clean();
if ($html !== '') {
    WP_CLI::error('Duplicate meta description: own tag rendered while an SEO provider is active.');
}
if (apply_filters('wpseo_metadesc', 'old') !== 'Opis dostarczany do istniejącej wtyczki SEO.') {
    WP_CLI::error('Existing SEO provider did not receive the deployed description.');
}
WP_CLI::success('Existing SEO provider receives metadata without a duplicate description tag.');
