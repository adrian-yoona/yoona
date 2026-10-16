<?php
/** Test fixtures, run before activation to detect unexpected activation writes. */
if (!defined('WP_CLI') || !WP_CLI) {
    exit(1);
}

$ids = array();
foreach (array(
    'home' => array('page', 'publish', 'home', 'Strona główna'),
    'about' => array('page', 'publish', 'o-nas', 'O nas'),
    'builder' => array('page', 'publish', 'builder', 'Builder'),
    'draft' => array('page', 'draft', 'szkic', 'Szkic'),
    'private' => array('page', 'private', 'prywatna', 'Prywatna'),
    'post' => array('post', 'publish', 'poradnik', 'Poradnik'),
    'restricted' => array('page', 'publish', 'restricted', 'Treść z zaufanego edytora'),
) as $key => $spec) {
    $id = wp_insert_post(array(
        'post_type' => $spec[0],
        'post_status' => $spec[1],
        'post_name' => $spec[2],
        'post_title' => $spec[3],
        'post_content' => '<p>Oryginalna treść: ' . $key . '.</p>',
    ), true);
    if (is_wp_error($id)) {
        WP_CLI::error($id->get_error_message());
    }
    $ids[$key] = $id;
}
update_post_meta($ids['builder'], '_elementor_data', '[{"id":"fixture"}]');
update_post_meta($ids['about'], '_yoona_seo_title', 'Poprzedni tytuł SEO');
update_post_meta($ids['about'], '_yoona_seo_description', 'Poprzedni opis SEO.');
update_option('show_on_front', 'page');
update_option('page_on_front', $ids['home']);
update_option('permalink_structure', '/%postname%/');
update_option('yoona_test_ids', $ids);
$snapshot = array();
foreach ($ids as $id) {
    $snapshot[$id] = array('post' => get_post($id, ARRAY_A), 'meta' => get_post_meta($id));
}
update_option('yoona_test_activation_snapshot', $snapshot);
WP_CLI::success('Prepared published, draft, private, front-page and builder fixtures.');
