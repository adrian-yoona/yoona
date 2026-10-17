<?php
/** Disposable collection fixtures and private, memory-only source checks. */
if (!defined('WP_CLI') || !WP_CLI || !isset($args[0])) exit(1);
global $wpdb, $post, $wp_query;
$count = 0;
$check = static function ($condition, $label) use (&$count) {
    if (!$condition) WP_CLI::error('Collections: ' . $label . ' No private source HTML is printed.');
    ++$count;
    WP_CLI::log('PASS Collections ' . $label);
};
$snapshot = static function () use ($wpdb) {
    return array('posts' => $wpdb->get_results("SELECT * FROM {$wpdb->posts} ORDER BY ID", ARRAY_A), 'meta' => $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A));
};
$mode = $args[0];
if ($mode === 'hide-services' || $mode === 'protect-catalog' || $mode === 'protect-projects') {
    $ids = get_option('yoona_test_collection_ids');
    $targets = $mode === 'hide-services' ? array($ids['/litery-3d/'] => array('post_status' => 'draft'), $ids['/reklamy-swietlne/'] => array('post_status' => 'private'), $ids['/tablice-olsztyn/'] => array('post_password' => 'local-secret')) : array(($mode === 'protect-catalog' ? $ids['/pelna-oferta/'] : (int) get_option('page_for_posts')) => array('post_password' => 'local-secret'));
    $saved = array();
    foreach ($targets as $id => $changes) {
        $record = get_post($id);
        $saved[$id] = array('post_status' => $record->post_status, 'post_password' => $record->post_password);
        $wpdb->update($wpdb->posts, $changes, array('ID' => $id));
        clean_post_cache($id);
    }
    update_option('yoona_test_collection_visibility', $saved, false);
    WP_CLI::success('Prepared a temporary public-visibility exclusion probe.');
    return;
}
if ($mode === 'restore-visibility') {
    foreach ((array) get_option('yoona_test_collection_visibility') as $id => $changes) { $wpdb->update($wpdb->posts, $changes, array('ID' => $id)); clean_post_cache($id); }
    delete_option('yoona_test_collection_visibility');
    WP_CLI::success('Restored exact status and password fields after visibility probe.');
    return;
}
if ($mode === 'snapshot') {
    update_option('yoona_test_collections_snapshot', $snapshot(), false);
    WP_CLI::success('Captured every post and metadata row before collections browsing.');
    return;
}
if ($mode === 'verify') {
    $check($snapshot() === get_option('yoona_test_collections_snapshot'), 'Every post and metadata row remains byte-identical after HTTP/browser rendering.');
    WP_CLI::success($count . ' complete database preservation assertion passed.');
    return;
}
$check(class_exists('Yoona_SEO_Content_Cleanup') && class_exists('Yoona_SEO_Project_Gallery'), 'Current presentation helper classes are loaded.');
if (!in_array($mode, array('prepare', 'private'), true) || empty($args[1]) || !is_readable($args[1])) WP_CLI::error('Use prepare|private PRIVATE_EXPORT or snapshot|verify.');
$records = array_column(json_decode(file_get_contents($args[1]), true)['pages'], null, 'path');
$data = Yoona_SEO_Design::data();
$check(count($data['removed_contact_sections']) === 44 && count($data['offer_heroes']) === 41, 'Reviewed scope contains 44 repeated sections and 41 services.');
$sections = static function ($content) { preg_match_all('/\[et_pb_section(?:\s|\])[\s\S]*?\[\/et_pb_section\]/', $content, $matches); return $matches[0]; };
$contact = static function ($content, $path) use ($data, $sections) {
    foreach ($sections($content) as $section) if (in_array(hash('sha256', $section), $data['removed_contact_sections'][$path]['section_sha256'], true)) return $section;
    return false;
};
if ($mode === 'prepare') {
    foreach ($wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_name LIKE %s", 'collection-%')) as $old_fixture) wp_delete_post((int) $old_fixture, true);
    $mu = <<<'MU'
<?php
/** Isolated shortcode probes; not the licensed Divi renderer. */
function et_pb_is_pagebuilder_used($id) { return get_post_meta($id, '_et_pb_use_builder', true) === 'on'; }
add_filter('the_content', function ($content) {
    if (et_pb_is_pagebuilder_used(get_the_ID())) { remove_filter('the_content', 'wpautop'); remove_filter('the_content', 'shortcode_unautop'); }
    $GLOBALS['yoona_collection_counters'] = array();
    return $content;
}, 8);
add_filter('the_content', function ($content) { return isset($_GET['yoona_cleanup_mutate']) ? str_replace('MASZ PYTANIE', 'ZMIENIONA SEKCJA', $content) : $content; }, 9);
foreach (array('et_pb_section','et_pb_row','et_pb_column','et_pb_row_inner','et_pb_column_inner','et_pb_text','et_pb_divider','et_pb_image','et_pb_button','et_pb_blurb') as $tag) {
    add_shortcode($tag, function ($attributes, $content = '', $tag = '') {
        if (!isset($GLOBALS['yoona_collection_counters'][$tag])) $GLOBALS['yoona_collection_counters'][$tag] = 0;
        $index = $GLOBALS['yoona_collection_counters'][$tag]++;
        $body = do_shortcode($content);
        if ($tag === 'et_pb_image' && !empty($attributes['src'])) $body .= '<img src="' . esc_url($attributes['src']) . '" alt="Public contact fixture photo">';
        $element = $tag === 'et_pb_section' ? 'section' : 'div';
        return '<' . $element . ' class="collection-fixture-module ' . esc_attr($tag) . '" data-module="' . esc_attr($tag) . '" data-counter="' . $index . '">' . $body . '</' . $element . '>';
    });
}
MU;
    wp_mkdir_p(WPMU_PLUGIN_DIR);
    @unlink(WPMU_PLUGIN_DIR . '/yoona-offer-fixture.php');
    @unlink(WPMU_PLUGIN_DIR . '/yoona-collections-fixture.php');
    $check(file_put_contents(WPMU_PLUGIN_DIR . '/zz-yoona-collections-fixture.php', $mu) !== false, 'Local Divi-like counter probes load after older design callbacks.');
    $ids = array();
    foreach ($data['removed_contact_sections'] as $path => $definition) {
        $record = $records[$path];
        $known = $contact($record['post_content'], $path);
        $check($known !== false, 'The public repeated-section fixture is selected by reviewed hash: ' . $path);
        $before = '[et_pb_section][et_pb_row][et_pb_column][et_pb_text]<div id="collection-preserved-before"><h2>ZACHOWANA TREŚĆ TESTOWA</h2><p>SYNTETYCZNY opis usługi i szczegóły realizacji.</p></div>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
        $after = '[et_pb_section][et_pb_row][et_pb_column][et_pb_text]<p id="collection-preserved-after">SYNTETYCZNA treść po usuwanym bloku.</p>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
        $first = isset($data['offer_heroes'][$path]) ? $sections($record['post_content'])[0] : '';
        $existing = get_page_by_path(trim($path, '/'), OBJECT, 'page');
        $id = wp_insert_post(wp_slash(array('ID' => $existing ? $existing->ID : 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => trim($path, '/'), 'post_title' => $record['post_title'], 'post_content' => $first . $before . $known . $after)), true);
        $check(!is_wp_error($id), 'Public banner/contact plus synthetic remainder fixture created: ' . $path);
        update_post_meta($id, '_et_pb_use_builder', 'on');
        update_post_meta($id, 'rank_math_title', 'KATALOG TEST SEO ' . $record['post_title']);
        update_post_meta($id, 'rank_math_description', 'Natywny opis SEO katalogu i oferty.');
        delete_post_meta($id, '_yoona_seo_title');
        delete_post_meta($id, '_yoona_seo_description');
        $ids[$path] = $id;
    }
    foreach (array('collection-protected' => array('page', 'publish', 'fixture-secret'), 'collection-unlisted' => array('page', 'publish', '')) as $slug => $spec) {
        $id = wp_insert_post(wp_slash(array('post_type' => $spec[0], 'post_status' => $spec[1], 'post_name' => $slug, 'post_title' => 'Collection exclusion fixture', 'post_password' => $spec[2], 'post_content' => '[et_pb_section][et_pb_row][et_pb_column][et_pb_text]<p>UNLISTED SECRET BODY</p>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]' . $contact($records['/plotki-ogrodki-piwne/']['post_content'], '/plotki-ogrodki-piwne/'))), true);
        $check(!is_wp_error($id), 'Exclusion fixture created: ' . $slug);
        update_post_meta($id, '_et_pb_use_builder', 'on');
    }
    $index = get_page_by_path('nasze-realizacje', OBJECT, 'page');
    $index_id = wp_insert_post(array('ID' => $index ? $index->ID : 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'nasze-realizacje', 'post_title' => 'Nasze realizacje'), true);
    $check(!is_wp_error($index_id), 'Native WordPress posts page is configured.');
    update_option('page_for_posts', $index_id);
    update_option('show_on_front', 'page');
    update_post_meta($index_id, 'rank_math_title', 'REALIZACJE NATYWNE SEO');
    update_post_meta($index_id, 'rank_math_description', 'Natywny opis realizacji WordPress.');
    $wpdb->update($wpdb->posts, array('post_status' => 'draft'), array('post_type' => 'post'));
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $uploads = wp_upload_dir();
    wp_mkdir_p($uploads['path']);
    $projects = array();
    for ($i = 1; $i <= 40; ++$i) {
        $file = $uploads['path'] . '/collection-photo-' . $i . '.jpg';
        $canvas = imagecreatetruecolor(900, 650);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 38 + $i, 57, 62));
        imagestring($canvas, 5, 40, 40, 'LOCAL PHOTO FIXTURE ' . $i, imagecolorallocate($canvas, 255, 255, 255));
        imagejpeg($canvas, $file, 82);
        imagedestroy($canvas);
        $attachment = wp_insert_attachment(array('post_title' => 'Fotografia testowa ' . $i, 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit'), $file, 0, true);
        $check(!is_wp_error($attachment), 'A real local image attachment is created: ' . $i);
        wp_update_attachment_metadata($attachment, wp_generate_attachment_metadata($attachment, $file));
        update_post_meta($attachment, '_wp_attachment_image_alt', 'Fotografia realizacji testowej ' . $i);
        $url = wp_get_attachment_url($attachment);
        $date = '2026-09-' . sprintf('%02d', 1 + intdiv($i - 1, 2)) . ' 12:00:00';
        $id = wp_insert_post(wp_slash(array('post_type' => 'post', 'post_status' => 'publish', 'post_name' => 'collection-project-' . $i, 'post_title' => 'REALIZACJA ' . sprintf('%02d', $i) . ' — OZNAKOWANIE MARKI', 'post_date' => $date, 'post_date_gmt' => $date, 'post_excerpt' => 'FORBIDDEN_EXCERPT_' . $i, 'post_content' => $i % 2 === 0 ? '[et_pb_image src="' . $url . '"][/et_pb_image]<p>FORBIDDEN_BODY_' . $i . '</p>' : '<p>FORBIDDEN_BODY_' . $i . '</p>')), true);
        $check(!is_wp_error($id), 'Dated published project fixture created: ' . $i);
        if ($i % 2 !== 0) set_post_thumbnail($id, $attachment);
        $projects[] = $id;
    }
    foreach (array(array('draft', ''), array('private', ''), array('publish', 'secret')) as $i => $spec) wp_insert_post(array('post_type' => 'post', 'post_status' => $spec[0], 'post_password' => $spec[1], 'post_name' => 'collection-excluded-' . $i, 'post_title' => 'FORBIDDEN_PROJECT_' . $i, 'post_date' => '2026-10-15 12:00:00', 'post_content' => '<img src="' . $url . '" alt="Fixture">'));
    $unillustrated = wp_insert_post(array('post_type' => 'post', 'post_status' => 'publish', 'post_name' => 'collection-no-photo', 'post_title' => 'FORBIDDEN_PROJECT_NO_PHOTO', 'post_date' => '2026-10-16 12:00:00', 'post_content' => '<p>No photograph in this fixture.</p>'));
    $check(Yoona_SEO_Project_Gallery::image($unillustrated) === '', 'A post without a photograph produces no invented photo.');
    foreach (array('external' => '<img src="https://external.example.invalid/wp-content/uploads/photo.jpg">', 'traversal' => '<img src="/wp-content/uploads/../private.jpg">', 'knob' => '[et_pb_image src="https://yoona.pl/wp-content/uploads/2026/03/agencja-reklamowa-olsztyn.png"][/et_pb_image]', 'knob-1' => '<img src="https://yoona.pl/wp-content/uploads/2026/03/agencja-reklamowa-olsztyn-1.png">') as $label => $content) {
        $id = wp_insert_post(wp_slash(array('post_type' => 'post', 'post_status' => 'publish', 'post_name' => 'collection-invalid-' . $label, 'post_title' => 'FORBIDDEN_PROJECT_' . $label, 'post_date' => '2026-10-16 12:00:00', 'post_content' => $content)), true);
        $check(!is_wp_error($id) && Yoona_SEO_Project_Gallery::image($id) === '', 'The photo helper rejects ' . $label . ' input.');
    }
    $broken_attachment = wp_insert_post(array('post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image/jpeg', 'post_title' => 'Broken attachment fixture'));
    $wpdb->update($wpdb->posts, array('guid' => ''), array('ID' => $broken_attachment));
    clean_post_cache($broken_attachment);
    $broken_project = wp_insert_post(array('post_type' => 'post', 'post_status' => 'publish', 'post_name' => 'collection-broken-thumbnail', 'post_title' => 'FORBIDDEN_PROJECT_BROKEN_THUMBNAIL', 'post_date' => '2026-10-16 12:00:00', 'post_content' => '<p>No fallback photo.</p>'));
    update_post_meta($broken_project, '_thumbnail_id', $broken_attachment);
    $check(Yoona_SEO_Project_Gallery::image($broken_project) === '', 'A nonexistent attachment file and empty media URL produce no invented photograph.');
    foreach (Yoona_SEO_Project_Gallery::CONTACT_IMAGES as $i => $basename) {
        $attachment = wp_insert_post(array('post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image/png', 'post_title' => 'Retired featured photo fixture', 'guid' => $url));
        update_post_meta($attachment, '_wp_attached_file', '2026/10/' . $basename);
        $id = wp_insert_post(array('post_type' => 'post', 'post_status' => 'publish', 'post_name' => 'collection-retired-thumbnail-' . $i, 'post_title' => 'FORBIDDEN_PROJECT_RETIRED_THUMBNAIL_' . $i, 'post_date' => '2026-10-16 12:00:00', 'post_content' => '<p>No project photograph.</p>'));
        update_post_meta($id, '_thumbnail_id', $attachment);
        $check(Yoona_SEO_Project_Gallery::image($id) === '', 'The featured image resolver rejects retired contact filename ' . ($i + 1) . '.');
    }
    update_option('yoona_test_collection_ids', $ids, false);
    update_option('yoona_test_collection_projects', array_reverse($projects), false);
    delete_option(Yoona_SEO_Design::OPTION);
    flush_rewrite_rules(true);
    WP_CLI::success($count . ' collection fixture preparation assertions passed.');
    return;
}
$before = $snapshot();
$bundle = json_decode(file_get_contents(WP_PLUGIN_DIR . '/yoona-seo/content/changes.json'), true);
$patches = array_column($bundle['pages'], null, 'path');
$ids = get_option('yoona_test_collection_ids');
$fixture_id = (int) $ids['/plotki-ogrodki-piwne/'];
$saved_post = get_post($fixture_id);
$saved_query = $wp_query;
$set_context = static function ($path, $content, $type = 'page', $password = '', $status = 'publish') use ($fixture_id) {
    global $post, $wp_query;
    $record = get_post($fixture_id, ARRAY_A);
    $record['post_content'] = $content; $record['post_name'] = trim($path, '/'); $record['post_parent'] = 0;
    $record['post_type'] = $type; $record['post_password'] = $password; $record['post_status'] = $status;
    $post = new WP_Post((object) $record);
    wp_cache_set($fixture_id, $post, 'posts');
    $wp_query = new WP_Query(); $wp_query->is_page = $type === 'page'; $wp_query->is_single = $type === 'post'; $wp_query->is_singular = true;
    $wp_query->queried_object = $post; $wp_query->queried_object_id = $fixture_id; $wp_query->post = $post; $wp_query->posts = array($post); $wp_query->post_count = 1; $wp_query->current_post = 0;
    setup_postdata($post);
};
foreach ($data['removed_contact_sections'] as $path => $definition) {
    $source = $records[$path]['post_content'];
    $patched = empty($patches[$path]['divi_text']) ? $source : Yoona_SEO_Divi::prepare($source, $patches[$path]['divi_text']);
    $check(!is_wp_error($patched), 'Frozen SEO patches remain valid for ' . $path);
    foreach (array($source, $patched) as $variant => $content) {
        $known = $contact($content, $path);
        $check($known !== false, 'Reviewed original/SEO section variant ' . $variant . ' resolves: ' . $path);
        $set_context($path, $content);
        $captured = array();
        $capture = static function ($output, $tag, $attributes, $match) use (&$captured, $known) {
            if ($tag === 'et_pb_section' && in_array(hash('sha256', $match[0]), array(hash('sha256', $known), hash('sha256', wptexturize($known))), true)) $captured[] = $output;
            return $output;
        };
        add_filter('do_shortcode_tag', $capture, 100, 4);
        ob_start(); the_content(); $ordinary = ob_get_clean();
        remove_filter('do_shortcode_tag', $capture, 100);
        $counters = $GLOBALS['yoona_collection_counters'];
        ob_start(); Yoona_SEO_Content_Cleanup::the_content(); $cleaned = ob_get_clean();
        $check(count($captured) === 1, 'Exactly one ordinary rendered contact section is captured for variant ' . $variant . ': ' . $path);
        // Core adds image loading/decoding attributes after do_shortcode_tag.
        // Locate the same unique fixture section in the final ordinary output
        // rather than assuming pre-filter image markup remains byte-identical.
        preg_match('/\A<section[^>]*>/', $captured[0], $opening);
        $start = isset($opening[0]) ? strpos($ordinary, $opening[0]) : false;
        $end = false === $start ? false : strpos($ordinary, '</section>', $start);
        $check(false !== $start && false !== $end, 'The exact counter-addressed section exists in final filtered markup for variant ' . $variant . ': ' . $path);
        $expected = substr_replace($ordinary, '', $start, $end + strlen('</section>') - $start);
        $check($cleaned === $expected, 'Only the reviewed rendered section is removed; every other byte remains for variant ' . $variant . ': ' . $path);
        $check($GLOBALS['yoona_collection_counters'] === $counters, 'Every shortcode/module executes with identical counters for variant ' . $variant . ': ' . $path);
        $changed = str_replace($known, substr_replace($known, 'edited-section', 40, 0), $content);
        $set_context($path, $changed);
        ob_start(); the_content(); $expected = ob_get_clean();
        ob_start(); Yoona_SEO_Content_Cleanup::the_content(); $actual = ob_get_clean();
        $check($actual === $expected, 'An edited contact section is preserved by safe fallback for variant ' . $variant . ': ' . $path);
    }
}
$sample = $records['/plotki-ogrodki-piwne/']['post_content'];
foreach (array(array('/unlisted/', 'page', '', 'publish'), array('/plotki-ogrodki-piwne/', 'post', '', 'publish'), array('/plotki-ogrodki-piwne/', 'page', 'secret', 'publish'), array('/plotki-ogrodki-piwne/', 'page', '', 'draft'), array('/plotki-ogrodki-piwne/', 'page', '', 'private')) as $spec) {
    $set_context($spec[0], $sample, $spec[1], $spec[2], $spec[3]);
    ob_start(); the_content(); $expected = ob_get_clean();
    ob_start(); Yoona_SEO_Content_Cleanup::the_content(); $actual = ob_get_clean();
    $check($actual === $expected, 'Unlisted/post/password/draft/private content remains untouched: ' . implode(':', $spec));
}
$resolved = array();
$unresolved = array();
wp_cache_set($fixture_id, array(), 'post_meta');
foreach ($records as $path => $record) {
    if ($record['post_type'] !== 'post') continue;
    $set_context($path, $record['post_content'], 'post');
    $html = Yoona_SEO_Project_Gallery::image($fixture_id);
    if ($html === '') { $unresolved[] = (int) $record['post_id']; continue; }
    preg_match('/src="([^"]+)"/', $html, $match);
    $check(isset($match[1]), 'An exported project resolves to escaped photograph markup: ' . (int) $record['post_id']);
    $resolved[] = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
}
sort($unresolved);
$check(count($resolved) === 150 && count(array_unique($resolved)) === 150 && $unresolved === array(3184, 3225, 3922), 'All 153 private project sources yield 150 distinct embedded photos and exactly three source-only gaps; featured-image metadata is absent from this export.');
$reflection = new ReflectionMethod('Yoona_SEO_Project_Gallery', 'source_pattern');
$reflection->setAccessible(true);
$pattern = $reflection->invoke(null);
$cases = array(
    'HTML first src attribute' => array('<img src="https://yoona.pl/wp-content/uploads/2026/03/test.jpg">', true),
    'Divi first src attribute' => array('[et_pb_image src="https://yoona.pl/wp-content/uploads/2026/03/test.jpg"][/et_pb_image]', true),
    'HTML additional attributes' => array('<img alt="Realizacja" src="/wp-content/uploads/2026/03/test.webp">', true),
    'Divi additional attributes' => array('[et_pb_image admin_label="Photo" src="/wp-content/uploads/2026/03/test.png"][/et_pb_image]', true),
    'External host' => array('<img src="https://external.example.invalid/wp-content/uploads/test.jpg">', false),
    'Deceptive host' => array('<img src="https://yoona.pl.evil.invalid/wp-content/uploads/test.jpg">', false),
    'Traversal' => array('<img src="/wp-content/uploads/../private.jpg">', false),
    'data-src is not src' => array('<img data-src="/wp-content/uploads/test.jpg">', false),
    'srcset is not src' => array('<img srcset="/wp-content/uploads/test.jpg 900w">', false),
    'Contact knob 1' => array('<img src="/wp-content/uploads/agencja-reklamowa-olsztyn-1.png">', false),
    'Contact knob 2' => array('[et_pb_image src="/wp-content/uploads/agencja-reklamowa-olsztyn.png"][/et_pb_image]', false),
);
foreach ($cases as $label => $case) {
    $set_context('/collection-pattern-probe/', $case[0], 'post');
    $image = Yoona_SEO_Project_Gallery::image($fixture_id);
    $sql_match = (bool) $wpdb->get_var($wpdb->prepare("SELECT REPLACE(REPLACE(LOWER(%s), %s, ''), %s, '') REGEXP %s", $case[0], Yoona_SEO_Project_Gallery::CONTACT_IMAGES[0], Yoona_SEO_Project_Gallery::CONTACT_IMAGES[1], $pattern));
    $check(($image !== '') === $case[1] && $sql_match === $case[1], 'The actual MariaDB and PHP photo grammar agree: ' . $label);
}
$set_context('/plotki-ogrodki-piwne/', $sample);
$callback_count = static function () { return isset($GLOBALS['wp_filter']['do_shortcode_tag']->callbacks[10]) ? count($GLOBALS['wp_filter']['do_shortcode_tag']->callbacks[10]) : 0; };
$before_filters = $callback_count();
$throw = static function ($output, $tag) { if ($tag === 'et_pb_section') throw new RuntimeException('Deliberate local render exception.'); return $output; };
add_filter('do_shortcode_tag', $throw, 5, 2);
ob_start();
try { Yoona_SEO_Content_Cleanup::the_content(); } catch (RuntimeException $error) { /* Expected local test failure. */ }
ob_end_clean();
remove_filter('do_shortcode_tag', $throw, 5);
$check($callback_count() === $before_filters, 'The temporary cleanup filter is removed even after another shortcode callback throws.');
wp_cache_delete($fixture_id, 'post_meta');
wp_cache_delete($fixture_id, 'posts'); $post = $saved_post; $wp_query = $saved_query; wp_reset_postdata();
$check($snapshot() === $before, 'All private original and SEO checks leave every stored post/meta row unchanged.');
WP_CLI::success($count . ' private source/render assertions passed; source remains outside HTTP tables and logs.');
