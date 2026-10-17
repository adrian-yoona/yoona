<?php
/** Disposable real-WordPress fixtures and mutation checks for the visual layer. */
if (!defined('WP_CLI') || !WP_CLI) {
    exit(1);
}
if (!class_exists('Yoona_SEO_Design')) {
    WP_CLI::error('The visual design class is not loaded.');
}
global $wpdb;
$mode = isset($args[0]) ? $args[0] : 'prepare';
$count = 0;
$check = static function ($condition, $label) use (&$count) {
    if (!$condition) {
        WP_CLI::error('Visual design: ' . $label);
    }
    ++$count;
    WP_CLI::log('PASS Design ' . $label);
};
$snapshot = static function () use ($wpdb) {
    return array(
        'posts' => $wpdb->get_results("SELECT * FROM {$wpdb->posts} ORDER BY ID", ARRAY_A),
        'meta' => $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A),
    );
};
if ($mode === 'verify') {
    $actual = $snapshot();
    $expected = get_option('yoona_test_design_snapshot');
    if ($actual !== $expected) {
        foreach (array('posts' => 'ID', 'meta' => 'meta_id') as $table => $id_key) {
            $before_rows = array_column($expected[$table], null, $id_key);
            $after_rows = array_column($actual[$table], null, $id_key);
            foreach (array_unique(array_merge(array_keys($before_rows), array_keys($after_rows))) as $row_id) {
                $before_row = isset($before_rows[$row_id]) ? $before_rows[$row_id] : array();
                $after_row = isset($after_rows[$row_id]) ? $after_rows[$row_id] : array();
                if ($before_row !== $after_row) {
                    $keys = array();
                    foreach (array_unique(array_merge(array_keys($before_row), array_keys($after_row))) as $key) {
                        if (!array_key_exists($key, $before_row) || !array_key_exists($key, $after_row) || $before_row[$key] !== $after_row[$key]) {
                            $keys[] = $key;
                        }
                    }
                    WP_CLI::log('Changed fixture row ' . $table . '/' . $row_id . ': ' . implode(', ', $keys) . (isset($after_row['meta_key']) ? ' key=' . $after_row['meta_key'] : ''));
                }
            }
        }
    }
    $check($actual === $expected, 'Browsing, enabling and disabling the design preserve every post row and all metadata exactly.');
    WP_CLI::success($count . ' visual design database assertions passed.');
    return;
}
if ($mode === 'snapshot') {
    update_option('yoona_test_design_snapshot', $snapshot(), false);
    WP_CLI::success('Captured post and metadata baseline before visual-only HTTP actions.');
    return;
}
if ($mode === 'protect-home' || $mode === 'restore-home') {
    $home = (int) get_option('page_on_front');
    if ($mode === 'protect-home') {
        update_option('yoona_test_home_password', get_post($home)->post_password, false);
        $wpdb->update($wpdb->posts, array('post_password' => 'local-home-test-password'), array('ID' => $home));
    } else {
        $wpdb->update($wpdb->posts, array('post_password' => get_option('yoona_test_home_password')), array('ID' => $home));
        delete_option('yoona_test_home_password');
    }
    clean_post_cache($home);
    WP_CLI::success('Prepared or restored the password-protected front-page fixture.');
    return;
}
$check($mode === 'prepare', 'Known test preparation mode.');
delete_option(Yoona_SEO_Design::OPTION);
$check(Yoona_SEO_Design::enabled(), 'The requested redesign is enabled when its option has never been saved.');
update_option(Yoona_SEO_Design::OPTION, '0', false);
$check(!Yoona_SEO_Design::enabled(), 'An explicit zero disables the design.');
update_option(Yoona_SEO_Design::OPTION, '1', false);
$check(Yoona_SEO_Design::enabled(), 'An explicit one enables the design.');
delete_option(Yoona_SEO_Design::OPTION);
$check(has_filter('template_include', array('Yoona_SEO_Design', 'template')) !== false, 'The design participates in the normal WordPress template filter.');

$mu_directory = WPMU_PLUGIN_DIR;
if (!is_dir($mu_directory)) {
    wp_mkdir_p($mu_directory);
}
$mu_fixture = <<<'PHP'
<?php
/** Local test hooks and shortcode callbacks; this is not the Divi theme. */
add_action('wp_head', function () { echo '<meta name="yoona-design-head-probe" content="executed" />'; });
add_action('wp_head', function () {
    if (is_page('pelna-oferta')) {
        echo '<style>body .design-offer-sample :is(h3,h4,h5,h6),body .design-offer-sample :is(h3,h4,h5,h6) :is(span,a){font-family:Georgia,serif!important;text-transform:none!important}</style>';
    }
}, 999);
add_action('wp_body_open', function () { echo '<div id="yoona-design-body-probe" hidden></div>'; });
add_action('wp_footer', function () { echo '<div id="yoona-design-footer-probe" hidden></div>'; });
add_action('after_setup_theme', function () {
    if (isset($_GET['yoona_test_no_title_support'])) {
        remove_theme_support('title-tag');
    }
}, 999);
foreach (array('et_pb_section', 'et_pb_row', 'et_pb_column', 'et_pb_text') as $tag) {
    add_shortcode($tag, function ($attributes, $content = '') { return '<div class="design-shortcode-probe">' . do_shortcode($content) . '</div>'; });
}
PHP;
$check(file_put_contents($mu_directory . '/yoona-design-fixture.php', $mu_fixture) !== false, 'Test hooks and shortcode callbacks are installed only in the disposable WordPress.');
$content = '[et_pb_section admin_label="original-wrapper"][et_pb_row][et_pb_column type="4_4"][et_pb_text]<p id="yoona-preserved-content">YOONA_EXISTING_DIVI_BODY_581</p><a href="https://example.invalid/preserved">Existing content link</a>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
$page = wp_insert_post(wp_slash(array(
    'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'design-fixture',
    'post_title' => 'YOONA design fixture', 'post_content' => $content,
)), true);
$check(!is_wp_error($page), 'A public page with existing Divi source is created.');
update_post_meta($page, '_et_pb_use_builder', 'on');
update_post_meta($page, '_yoona_seo_title', 'YOONA design SEO fixture');
update_post_meta($page, '_yoona_seo_description', 'YOONA design description fixture.');
update_post_meta($page, 'rank_math_title', 'YOONA design SEO fixture');
update_post_meta($page, 'rank_math_description', 'YOONA design description fixture.');
$offer = wp_insert_post(wp_slash(array(
    'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'pelna-oferta',
    'post_title' => 'Pełna oferta — typografia',
    'post_content' => '[et_pb_section][et_pb_row][et_pb_column][et_pb_text]<div class="design-offer-sample"><h3><span style="font-family:Georgia,serif">Reklama wielkoformatowa</span></h3><h4><a href="#oferta-test">Ścianki wystawiennicze i ekspozycyjne</a></h4><h5>Litery przestrzenne i podświetlane</h5><h6 id="oferta-test">Tablice informacyjne i unijne</h6></div>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]',
)), true);
$check(!is_wp_error($offer), 'A full-offer page contains mixed-case nested service headings and competing Divi-like font styles.');
update_post_meta($offer, '_et_pb_use_builder', 'on');
foreach (array('banery' => 'Banery i billboardy', 'oklejanie-przyczep-gastronomicznych' => 'Oklejanie przyczep gastronomicznych i zabudowy reklamowe') as $slug => $title) {
    $service = wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => '<p>Synthetic public service catalogue fixture.</p>'), true);
    $check(!is_wp_error($service), 'A published service page is available to the curated photographic catalogue: ' . $slug);
}
$protected = wp_insert_post(array(
    'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'design-protected',
    'post_title' => 'Protected design fixture', 'post_password' => 'local-design-test-password',
    'post_content' => '<p>YOONA_PASSWORD_SECRET_CONTENT_913</p>',
), true);
$check(!is_wp_error($protected), 'A password-protected page is created.');
$post = wp_insert_post(array(
    'post_type' => 'post', 'post_status' => 'publish', 'post_name' => 'design-post',
    'post_title' => 'YOONA design post fixture', 'post_content' => '<p>YOONA_EXISTING_POST_BODY_624</p>',
), true);
$check(!is_wp_error($post), 'A public post fixture is created.');
$reader = get_user_by('login', 'yoona_design_reader');
if (!$reader) {
    $reader_id = wp_create_user('yoona_design_reader', 'local-reader-test-only', 'design-reader@example.invalid');
    $check(!is_wp_error($reader_id), 'A subscriber fixture is created.');
    $reader = get_user_by('id', $reader_id);
}
$reader->set_role('subscriber');
update_option('yoona_test_design_ids', array('page' => $page, 'protected' => $protected, 'post' => $post), false);
update_option('yoona_test_design_snapshot', $snapshot(), false);
WP_CLI::success($count . ' visual design preparation assertions passed.');
