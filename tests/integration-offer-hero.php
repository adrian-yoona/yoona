<?php
/** Focused real-WordPress offer presentation fixtures; never runs on production. */
if (!defined('WP_CLI') || !WP_CLI || !isset($args[0])) {
    exit(1);
}
if (!class_exists('Yoona_SEO_Offer_Hero')) {
    WP_CLI::error('Current offer hero class is not loaded.');
}
global $wpdb, $post, $wp_query;
$checks = 0;
$check = static function ($condition, $label) use (&$checks) {
    if (!$condition) {
        WP_CLI::error('Offer hero: ' . $label . ' No private source HTML is printed.');
    }
    ++$checks;
    WP_CLI::log('PASS Offer hero ' . $label);
};
$snapshot = static function () use ($wpdb) {
    return array(
        'posts' => $wpdb->get_results("SELECT * FROM {$wpdb->posts} ORDER BY ID", ARRAY_A),
        'meta' => $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A),
    );
};
$mode = $args[0];
if ($mode === 'snapshot') {
    update_option('yoona_test_offer_snapshot', $snapshot(), false);
    WP_CLI::success('Captured complete post and metadata baseline for offer HTTP/browser checks.');
    return;
}
if ($mode === 'verify') {
    $check($snapshot() === get_option('yoona_test_offer_snapshot'), 'HTTP browsing and render helpers preserve every post and metadata row exactly.');
    WP_CLI::success($checks . ' offer database assertions passed.');
    return;
}
if (!in_array($mode, array('prepare', 'private'), true) || empty($args[1]) || !is_readable($args[1])) {
    WP_CLI::error('Use prepare|private with the private export outside the document root, or snapshot|verify.');
}
$source_export = json_decode(file_get_contents($args[1]), true);
$records = array_column($source_export['pages'], null, 'path');
$data = Yoona_SEO_Design::data();
$definitions = $data['offer_heroes'];
$check(count($definitions) === 41, 'The curated allowlist contains 41 service pages.');
$first_section = static function ($content) {
    $end = strpos($content, '[/et_pb_section]');
    return false === $end ? false : substr($content, 0, $end + strlen('[/et_pb_section]'));
};
if ($mode === 'prepare') {
    $mu = <<<'MU'
<?php
/** Local Divi-like rendering probes. This fixture is not the licensed theme. */
if (!function_exists('et_pb_is_pagebuilder_used')) {
    function et_pb_is_pagebuilder_used($post_id) { return get_post_meta($post_id, '_et_pb_use_builder', true) === 'on'; }
}
add_filter('the_content', function ($content) {
    if (et_pb_is_pagebuilder_used(get_the_ID())) {
        remove_filter('the_content', 'wpautop');
        remove_filter('the_content', 'shortcode_unautop');
    }
    $GLOBALS['yoona_offer_fixture_counters'] = array();
    return $content;
}, 8);
add_filter('the_content', function ($content) {
    return isset($_GET['yoona_offer_mutate']) ? substr_replace($content, 'changed-before-shortcodes', 20, 0) : $content;
}, 9);
foreach (array('et_pb_section', 'et_pb_row', 'et_pb_column', 'et_pb_row_inner', 'et_pb_column_inner', 'et_pb_text', 'et_pb_divider', 'et_pb_image') as $tag) {
    add_shortcode($tag, function ($attributes, $content = '', $tag = '') {
        if (!isset($GLOBALS['yoona_offer_fixture_counters'][$tag])) $GLOBALS['yoona_offer_fixture_counters'][$tag] = 0;
        $index = $GLOBALS['yoona_offer_fixture_counters'][$tag]++;
        $rendered = do_shortcode($content);
        $element = $tag === 'et_pb_section' ? 'section' : 'div';
        return '<' . $element . ' class="offer-fixture-module ' . esc_attr($tag) . '" data-module="' . esc_attr($tag) . '" data-counter="' . $index . '">' . $rendered . '</' . $element . '>';
    });
}
MU;
    wp_mkdir_p(WPMU_PLUGIN_DIR);
    $check(file_put_contents(WPMU_PLUGIN_DIR . '/yoona-offer-fixture.php', $mu) !== false, 'Disposable shortcode callbacks preserve section and module counter order.');
    $paths = array('/plotki-ogrodki-piwne/', '/oklejanie-przyczep-gastronomicznych/', '/tabliczki-informacyjne/');
    $ids = array();
    foreach ($paths as $index => $path) {
        $record = $records[$path];
        $heading = $index === 0 ? 'h1' : 'h2';
        // Only the already-public banner is projected into the HTTP fixture.
        // The remaining source is deliberately synthetic, never the private body.
        $body = '[et_pb_section admin_label="fixture-preserved-body"][et_pb_row][et_pb_column][et_pb_text]<div id="offer-preserved-body"><' . $heading . '>Zachowana treść oferty</' . $heading . '><p>SYNTETYCZNA TREŚĆ TESTOWA — projekt, produkcja i montaż reklamy.</p><a href="/kontakt/">Kontakt z zespołem</a></div>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
        $existing = get_page_by_path(trim($path, '/'), OBJECT, 'page');
        $id = wp_insert_post(wp_slash(array(
            'ID' => $existing ? $existing->ID : 0,
            'post_type' => 'page', 'post_status' => 'publish', 'post_name' => trim($path, '/'),
            'post_title' => $record['post_title'], 'post_content' => $first_section($record['post_content']) . $body,
        )), true);
        $check(!is_wp_error($id), 'Public banner plus synthetic body fixture created for ' . $path);
        update_post_meta($id, '_et_pb_use_builder', 'on');
        update_post_meta($id, 'rank_math_title', 'OFERTA TEST SEO ' . ($index + 1));
        update_post_meta($id, 'rank_math_description', 'Opis SEO testowej oferty numer ' . ($index + 1) . '.');
        $ids[$path] = $id;
    }
    $original = get_post($ids[$paths[0]]);
    foreach (array('offer-header-mismatch' => array('page', '', ' <!-- changed public header -->'), 'offer-protected' => array('page', 'test-password', ''), 'offer-post' => array('post', '', '')) as $slug => $fixture) {
        $existing = get_page_by_path($slug, OBJECT, $fixture[0]);
        $id = wp_insert_post(wp_slash(array('ID' => $existing ? $existing->ID : 0, 'post_type' => $fixture[0], 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => 'Offer fallback fixture', 'post_password' => $fixture[1], 'post_content' => $original->post_content . $fixture[2])), true);
        $check(!is_wp_error($id), 'Safe fallback fixture created: ' . $slug);
        update_post_meta($id, '_et_pb_use_builder', 'on');
        $ids[$slug] = $id;
    }
    update_option('yoona_test_offer_ids', $ids, false);
    delete_option(Yoona_SEO_Design::OPTION);
    WP_CLI::success($checks . ' offer fixture preparation assertions passed.');
    return;
}
$bundle = json_decode(file_get_contents(WP_PLUGIN_DIR . '/yoona-seo/content/changes.json'), true);
$patches = array_column($bundle['pages'], null, 'path');
$check(class_exists('RankMath'), 'The real Rank Math plugin is active.');
$storage_before = $snapshot();
$fixture_ids = get_option('yoona_test_offer_ids');
$fixture_id = (int) reset($fixture_ids);
$saved_post = get_post($fixture_id);
$saved_query = $wp_query;
$set_context = static function ($path, $content, $type = 'page', $password = '', $status = 'publish') use ($fixture_id) {
    global $post, $wp_query;
    $record = get_post($fixture_id, ARRAY_A);
    $record['post_content'] = $content;
    $record['post_name'] = trim($path, '/');
    $record['post_parent'] = 0;
    $record['post_type'] = $type;
    $record['post_password'] = $password;
    $record['post_status'] = $status;
    $post = new WP_Post((object) $record);
    wp_cache_set($fixture_id, $post, 'posts');
    $wp_query = new WP_Query();
    $wp_query->is_page = $type === 'page';
    $wp_query->is_single = $type === 'post';
    $wp_query->is_singular = true;
    $wp_query->queried_object = $post;
    $wp_query->queried_object_id = $fixture_id;
    $wp_query->post = $post;
    $wp_query->posts = array($post);
    $wp_query->post_count = 1;
    $wp_query->current_post = 0;
    setup_postdata($post);
};
foreach ($definitions as $path => $definition) {
    $check(isset($records[$path]), 'Allowlisted service has its exported source: ' . $path);
    $source = $records[$path]['post_content'];
    $original_header = $first_section($source);
    $check(Yoona_SEO_Offer_Hero::prepare_content($source, $definition) === substr($source, strlen($original_header)), 'Original banner hash accepts source and preserves exact remaining bytes: ' . $path);
    $patched = empty($patches[$path]['divi_text']) ? $source : Yoona_SEO_Divi::prepare($source, $patches[$path]['divi_text']);
    $check(!is_wp_error($patched), 'SEO patch prepares safely: ' . $path);
    $check(Yoona_SEO_Offer_Hero::prepare_content($patched, $definition) === substr($patched, strlen($first_section($patched))), 'SEO-updated banner hash accepts source and preserves exact remaining bytes: ' . $path);
    foreach (array($original_header, $first_section($patched)) as $variant => $header) {
        $body = '[et_pb_section][et_pb_row][et_pb_column][et_pb_text]<p id="offer-counter-body">Unchanged synthetic body</p>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
        $set_context($path, $header . $body);
        $context = Yoona_SEO_Offer_Hero::context($fixture_id);
        $check(is_array($context) && $context['post_id'] === $fixture_id, 'Page context resolves accepted header variant ' . $variant . ': ' . $path);
        ob_start(); the_content(); $ordinary = ob_get_clean();
        $counters = $GLOBALS['yoona_offer_fixture_counters'];
        ob_start(); $removed = Yoona_SEO_Offer_Hero::the_content($context); $rendered = ob_get_clean();
        $check($removed && strpos($rendered, 'id="offer-counter-body"') !== false && strpos($rendered, 'data-module="et_pb_section" data-counter="0"') === false, 'Exactly the first banner output is removed after rendering variant ' . $variant . ': ' . $path);
        $check($GLOBALS['yoona_offer_fixture_counters'] === $counters, 'Divi-style section/module counters are unchanged for variant ' . $variant . ': ' . $path);
        preg_match('/<section class="offer-fixture-module et_pb_section" data-module="et_pb_section" data-counter="1">.*\z/s', $ordinary, $expected);
        $check(isset($expected[0]) && $rendered === $expected[0], 'Entire following rendered body stays byte-identical for variant ' . $variant . ': ' . $path);
    }
    $changed = substr_replace($source, 'changed-header', 20, 0);
    $check(Yoona_SEO_Offer_Hero::prepare_content($changed, $definition) === false, 'An edited banner triggers safe fallback: ' . $path);
}
$path = '/plotki-ogrodki-piwne/';
$source = $records[$path]['post_content'];
foreach (array(array('/o-nas/', 'page', '', 'publish'), array($path, 'post', '', 'publish'), array($path, 'page', 'protected', 'publish'), array($path, 'page', '', 'draft'), array($path, 'page', '', 'private')) as $spec) {
    $set_context($spec[0], $source, $spec[1], $spec[2], $spec[3]);
    $check(Yoona_SEO_Offer_Hero::context($fixture_id) === false, 'Unlisted/post/password/draft/private content is excluded: ' . implode(':', $spec));
}
$set_context($path, $source);
$context = Yoona_SEO_Offer_Hero::context($fixture_id);
$third_party_filter = static function ($content) { return substr_replace($content, 'changed-before-shortcodes', 20, 0); };
add_filter('the_content', $third_party_filter, 9);
ob_start(); $removed = Yoona_SEO_Offer_Hero::the_content($context); $changed_output = ob_get_clean();
remove_filter('the_content', $third_party_filter, 9);
$check(!$removed && strpos($changed_output, 'data-counter="0"') !== false, 'A third-party content mutation retains the original rendered banner for template fallback.');
$throw = static function ($output, $tag) { if ($tag === 'et_pb_section') throw new RuntimeException('local deliberate renderer failure'); return $output; };
add_filter('do_shortcode_tag', $throw, 5, 2);
$before_filters = isset($GLOBALS['wp_filter']['do_shortcode_tag']) ? count($GLOBALS['wp_filter']['do_shortcode_tag']->callbacks[10] ?? array()) : 0;
ob_start();
try { Yoona_SEO_Offer_Hero::the_content($context); } catch (RuntimeException $error) { /* Expected test exception. */ }
ob_end_clean();
remove_filter('do_shortcode_tag', $throw, 5);
$after_filters = isset($GLOBALS['wp_filter']['do_shortcode_tag']) ? count($GLOBALS['wp_filter']['do_shortcode_tag']->callbacks[10] ?? array()) : 0;
$check($before_filters === $after_filters, 'The temporary output filter is removed even if another shortcode callback throws.');
wp_cache_delete($fixture_id, 'posts');
$post = $saved_post;
$wp_query = $saved_query;
wp_reset_postdata();
$check($snapshot() === $storage_before, 'All private-source and render checks leave every post and metadata row unchanged.');
WP_CLI::success($checks . ' focused real-WordPress offer source/render assertions passed; no private body content was written to web tables.');
