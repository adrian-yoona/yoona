<?php
/** Prepare native metadata, then verify rollback after the frontend tests. */
if (!defined('WP_CLI') || !WP_CLI) {
    exit(1);
}
wp_set_current_user(get_user_by('login', 'yoona_test_admin')->ID);
$ids = get_option('yoona_test_ids');
if (isset($args[0]) && $args[0] === 'rollback') {
    $fixture = get_option('yoona_test_native_persistence');
    $result = Yoona_SEO_Engine::rollback($fixture['batch_id']);
    if (is_wp_error($result) || get_post_meta($ids['home']) !== $fixture['meta']) {
        WP_CLI::error('Native SEO persistence rollback did not restore exact original metadata.');
    }
    WP_CLI::success('Native metadata and legacy overlays restore exactly after deactivation/reactivation.');
    return;
}
$before = get_post_meta($ids['home']);
$bundle = array(
    'schema_version' => 2,
    'site_url' => home_url('/'),
    'seo_storage' => 'rank_math',
    'audit_verified' => true,
    'pages' => array(array(
        'path' => '/',
        'seo_title' => 'Yoona — trwałe natywne SEO Rank Math',
        'meta_description' => 'Natywny opis Rank Math działa także po wyłączeniu Yoona SEO.',
    )),
);
$result = Yoona_SEO_Engine::apply($bundle, array('/'));
if (is_wp_error($result) || $result['status'] !== 'success') {
    WP_CLI::error('Could not prepare native Rank Math metadata for frontend persistence checks.');
}
if (metadata_exists('post', $ids['home'], '_yoona_seo_title') || metadata_exists('post', $ids['home'], '_yoona_seo_description')) {
    WP_CLI::error('Native deployment left a Yoona metadata overlay.');
}
update_option('yoona_test_native_persistence', array('batch_id' => $result['batch_id'], 'meta' => $before), false);
WP_CLI::success('Prepared native Rank Math title and description without Yoona overlays.');
