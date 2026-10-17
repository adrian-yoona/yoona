<?php
/** Synthetic regression fixtures: no private site content or production writes. */
if (!defined('WP_CLI') || !WP_CLI || empty($args[0])) exit(1);
global $wpdb, $post, $wp_query;
$count = 0;
$check = static function ($condition, $label) use (&$count) {
    if (!$condition) WP_CLI::error('Contact cleanup: ' . $label);
    ++$count;
    WP_CLI::log('PASS Contact cleanup ' . $label);
};
$snapshot = static function () use ($wpdb) {
    return array('posts' => $wpdb->get_results("SELECT * FROM {$wpdb->posts} ORDER BY ID", ARRAY_A), 'meta' => $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A));
};
$mode = $args[0];
if ($mode === 'snapshot') { update_option('yoona_test_contact_cleanup_snapshot', $snapshot(), false); WP_CLI::success('Captured all post and metadata rows.'); return; }
if ($mode === 'verify') { $check($snapshot() === get_option('yoona_test_contact_cleanup_snapshot'), 'Every post and metadata row remains byte-identical after HTTP and browser rendering.'); WP_CLI::success($count . ' database preservation assertion passed.'); return; }
$image = 'https://yoona.pl/wp-content/uploads/2023/07/agencja-reklamowa-olsztyn-1.png';
$body = '<h2>MASZ PYTANIE?</h2><h2><span>KONTAKT</span></h2><p id="cleanup-retired-contact">SYNTHETIC RETIRED CONTACT BODY</p><p>791 144 100 · wyceny&#64;yoona.pl</p>';
$divi = static function ($src, $text, $attributes = 'background_color="#e1e1e1"') {
    return '[et_pb_section ' . $attributes . '][et_pb_row][et_pb_column][et_pb_image src="' . $src . '" title_text="Synthetic knob fixture"][/et_pb_image][/et_pb_column][et_pb_column][et_pb_text]' . $text . '[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
};
$section = $divi($image, $body);
$before = '[et_pb_section][et_pb_row][et_pb_column][et_pb_text]<p id="cleanup-preserved-before">SYNTHETIC BEFORE</p>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
$after = '[et_pb_section][et_pb_row][et_pb_column][et_pb_text]<p id="cleanup-preserved-after">SYNTHETIC AFTER</p>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
if ($mode === 'prepare') {
    $mu = <<<'MU'
<?php
/** Isolated Divi-like callback probes, not the proprietary Divi renderer. */
add_filter('the_content', function ($content) {
    if (get_post_meta(get_the_ID(), '_et_pb_use_builder', true) === 'on') { remove_filter('the_content', 'wpautop'); remove_filter('the_content', 'shortcode_unautop'); }
    $GLOBALS['yoona_collection_counters'] = array();
    return $content;
}, 8);
foreach (array('et_pb_section','et_pb_row','et_pb_column','et_pb_row_inner','et_pb_column_inner','et_pb_text','et_pb_divider','et_pb_image','et_pb_button','et_pb_blurb') as $tag) {
    add_shortcode($tag, function ($attributes, $content = '', $tag = '') {
        if (!isset($GLOBALS['yoona_collection_counters'][$tag])) $GLOBALS['yoona_collection_counters'][$tag] = 0;
        $index = $GLOBALS['yoona_collection_counters'][$tag]++;
        $body = do_shortcode($content);
        if ($tag === 'et_pb_image' && !empty($attributes['src'])) $body .= '<img src="' . esc_url($attributes['src']) . '" alt="Synthetic contact fixture photo">';
        $element = $tag === 'et_pb_section' ? 'section' : 'div';
        return '<' . $element . ' class="cleanup-fixture-module ' . esc_attr($tag) . '" data-module="' . esc_attr($tag) . '" data-counter="' . $index . '">' . $body . '</' . $element . '>';
    });
}
add_shortcode('yoona_shared_contact', function () { return do_shortcode((string) get_option('yoona_test_shared_contact_shortcode')); });
add_filter('the_content', function ($content) {
    return isset($_GET['cleanup_early_shortcode']) ? do_shortcode($content) : $content;
}, 9);
add_filter('the_content', function ($content) {
    return isset($_GET['cleanup_rendered_transform']) ? str_replace('MASZ PYTANIE?', 'Masz <span>pytanie?</span>', $content) : $content;
}, 12);
MU;
    $check(file_put_contents(WPMU_PLUGIN_DIR . '/zzz-yoona-cleanup-regression.php', $mu) !== false, 'Synthetic shared-component and render-transform probes are installed.');
    update_option('yoona_test_shared_contact_shortcode', $section, false);
    $cases = array(
        'cleanup-new-page' => array('page', $section),
        'cleanup-project' => array('post', $section),
        'cleanup-edited' => array('page', $divi($image . '?cache=20261017#photo', str_replace('791 144 100', '500 600 700', $body), 'admin_label="Independently edited block" custom_padding="24px|||" background_color="#ddd"')),
        'cleanup-global' => array('page', '[yoona_shared_contact]'),
        'cleanup-html' => array('page', '<section class="et_pb_section synthetic-retired-contact"><div class="et_pb_row"><div class="et_pb_column"><img src="' . $image . '" alt=""></div><div class="et_pb_column">' . $body . '</div></div></section>'),
        'cleanup-unrelated' => array('page', '[et_pb_section][et_pb_row][et_pb_column][et_pb_text]<div id="cleanup-legitimate-contact"><h2>MASZ PYTANIE?</h2><h2>KONTAKT</h2><p>LEGITIMATE CONTACT CONTENT</p></div>[/et_pb_text][et_pb_image src="https://yoona.pl/wp-content/uploads/2026/03/real-project.jpg"][/et_pb_image][/et_pb_column][/et_pb_row][/et_pb_section]'),
    );
    $ids = array();
    foreach ($cases as $slug => $case) {
        $existing = get_page_by_path($slug, OBJECT, $case[0]);
        $id = wp_insert_post(wp_slash(array('ID' => $existing ? $existing->ID : 0, 'post_type' => $case[0], 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => 'Synthetic cleanup regression ' . $slug, 'post_content' => $before . $case[1] . $after)), true);
        $check(!is_wp_error($id), 'A public synthetic route is created: ' . $slug);
        update_post_meta($id, '_et_pb_use_builder', 'on');
        $ids[$slug] = $id;
    }
    update_option('yoona_test_contact_cleanup_ids', $ids, false);
    flush_rewrite_rules(true);
    WP_CLI::success($count . ' synthetic cleanup fixture assertions passed.');
    return;
}
if ($mode !== 'private') WP_CLI::error('Use prepare|private|snapshot|verify.');
$ids = get_option('yoona_test_contact_cleanup_ids');
$fixture_id = (int) $ids['cleanup-new-page'];
$saved_post = $post; $saved_query = $wp_query;
$set_context = static function ($content, $type = 'page', $slug = 'cleanup-unknown-renamed') use ($fixture_id) {
    global $post, $wp_query;
    $record = get_post($fixture_id, ARRAY_A);
    $record['post_content'] = $content; $record['post_name'] = $slug; $record['post_parent'] = 0; $record['post_type'] = $type;
    $post = new WP_Post((object) $record); wp_cache_set($fixture_id, $post, 'posts');
    $wp_query = new WP_Query(); $wp_query->is_page = $type === 'page'; $wp_query->is_single = $type === 'post'; $wp_query->is_singular = true;
    $wp_query->queried_object = $post; $wp_query->queried_object_id = $fixture_id; $wp_query->post = $post; $wp_query->posts = array($post); $wp_query->post_count = 1; $wp_query->current_post = 0;
    setup_postdata($post);
};
$render = static function ($cleaned) {
    $disabled = array();
    if (!$cleaned) foreach (array('do_shortcode_tag' => 'filter_shortcode', 'et_module_shortcode_output' => 'filter_divi', 'the_content' => 'filter_content') as $hook => $method) {
        $callback = array('Yoona_SEO_Content_Cleanup', $method); $priority = has_filter($hook, $callback);
        if ($priority !== false) {
            foreach ($GLOBALS['wp_filter'][$hook]->callbacks[$priority] as $item) if ($item['function'] === $callback) $disabled[] = array($hook, $callback, $priority, $item['accepted_args']);
            remove_filter($hook, $callback, $priority);
        }
    }
    ob_start();
    try {
        if ($cleaned) Yoona_SEO_Content_Cleanup::the_content(); else the_content();
        return ob_get_contents();
    } finally { ob_end_clean(); foreach ($disabled as $item) add_filter($item[0], $item[1], $item[2], $item[3]); }
};
$without_target = static function ($html) {
    preg_match_all('~</?(?:section|div)\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*>~i', $html, $tokens, PREG_OFFSET_CAPTURE);
    $stack = array(); $remove = array();
    foreach ($tokens[0] as $token) {
        if (stripos($token[0], '</') !== 0) { $stack[] = array($token[1], preg_match('/\bclass="[^"]*\bet_pb_section\b/', $token[0])); continue; }
        $opening = array_pop($stack); if ($opening === null || !$opening[1]) continue;
        $start = $opening[0]; $end = $token[1] + strlen($token[0]);
        if (strpos(substr($html, $start, $end - $start), 'id="cleanup-retired-contact"') !== false) $remove[] = array($start, $end - $start);
    }
    usort($remove, static function ($a, $b) { return $b[0] <=> $a[0]; });
    foreach ($remove as $span) $html = substr_replace($html, '', $span[0], $span[1]);
    return $html;
};
$before_db = $snapshot();
update_option('yoona_test_contact_cleanup_private_snapshot', $before_db, false);
$positives = array(
    'new public page route' => array($section, 'page'),
    'public project post route' => array($section, 'post'),
    'independently edited Divi attributes and contact copy' => array($divi($image, str_replace('791 144 100', '500 600 700', $body), 'background_color="#ccc" admin_label="Edited yesterday" custom_padding="20px|||"'), 'page'),
    'image URL query and fragment' => array($divi($image . '?updated=1#photo', $body), 'page'),
    'WordPress resized retired image' => array($divi(str_replace('-1.png', '-1-900x650.png', $image), $body), 'page'),
    'optimized WebP retired image' => array($divi($image . '.webp', $body), 'page'),
    'optimized AVIF retired image' => array($divi(str_replace('.png', '.avif', $image), $body), 'page'),
    'other retired knob basename' => array($divi(str_replace('-1.png', '.png', $image), $body), 'page'),
    'edited question with both original office mailto links' => array($divi($image, '<h2>POROZMAWIAJMY O PROJEKCIE</h2><h2>KONTAKT</h2><p id="cleanup-retired-contact">SYNTHETIC RETIRED CONTACT BODY</p><a href="mailto:wyceny@yoona.pl">Olsztyn</a><a href="mailto:biuro@yoona.pl">Ostróda</a>'), 'page'),
    'heading typography and email entities' => array($divi($image, str_replace(array('MASZ PYTANIE?', 'KONTAKT', 'wyceny&#64;yoona.pl'), array('Masz <span>pytanie?</span>', '<strong>Kontakt</strong>', '&#119;&#121;&#99;&#101;&#110;&#121;&#64;yoona.pl'), $body)), 'page'),
    'reordered columns' => array('[et_pb_section][et_pb_row][et_pb_column][et_pb_text]' . $body . '[/et_pb_text][/et_pb_column][et_pb_column][et_pb_image title_text="Synthetic" src="' . $image . '"][/et_pb_image][/et_pb_column][/et_pb_row][/et_pb_section]', 'page'),
    'shared/global shortcode content' => array('[yoona_shared_contact]', 'page'),
    'two repeated sections' => array($section . $section, 'page'),
    'pre-rendered native HTML section' => array('<section class="et_pb_section"><div class="et_pb_row"><div><img src="' . $image . '"></div><div>' . $body . '</div></div></section>', 'page'),
    'quoted HTML boundary characters' => array('<div class="et_pb_section"><p title="before > after &lt;/div&gt;">SYNTHETIC LABEL</p><div><img src="' . $image . '"></div><div>' . $body . '</div></div>', 'page'),
    'lazy-loaded image attributes' => array('<section class="et_pb_section"><img src="/placeholder.png" data-src="' . $image . '">' . $body . '</section>', 'page'),
    'responsive picture source' => array('<section class="et_pb_section"><picture><source srcset="' . $image . ' 1x, ' . str_replace('-1.png', '-1-900x650.png', $image) . ' 2x"><img src="/placeholder.png"></picture>' . $body . '</section>', 'page'),
    'Divi inline background image' => array('<section class="et_pb_section" style="background-image:url(' . $image . ')">' . $body . '</section>', 'page'),
);
foreach ($positives as $label => $case) {
    $set_context($before . $case[0] . $after, $case[1]);
    $ordinary = $render(false); $counters = $GLOBALS['yoona_collection_counters'];
    $expected = $without_target($ordinary); $actual = $render(true);
    $check($ordinary !== $expected && $actual === $expected, 'Only the complete retired contact block is omitted: ' . $label . '.');
    $check($GLOBALS['yoona_collection_counters'] === $counters, 'Every shortcode callback still executes with identical counters: ' . $label . '.');
    ob_start(); the_content(); $global = ob_get_clean();
    $check($global === $expected, 'Ordinary WordPress the_content also removes the component: ' . $label . '.');
}
$negatives = array(
    'same headings with another real photograph' => $divi(str_replace('agencja-reklamowa-olsztyn-1.png', 'actual-project.jpg', $image), $body),
    'retired image alone used in content' => $divi($image, '<p id="cleanup-legitimate-image">LEGITIMATE SINGLE IMAGE CONTENT</p>'),
    'deceptively similar image basename' => $divi(str_replace('-1.png', '-10.png', $image), $body),
    'heading and image in different sibling sections' => $divi($image, '<p id="cleanup-legitimate-image">LEGITIMATE IMAGE</p>') . $divi(str_replace('-1.png', '-other.png', $image), $body),
    'knob URL occurs only in a hyperlink' => '<section class="et_pb_section"><a href="' . $image . '">Reference link</a>' . $body . '</section>',
    'knob image appears only inside an HTML comment' => '<section class="et_pb_section"><!-- <img src="' . $image . '"> -->' . $body . '</section>',
    'knob image appears only inside script text' => '<section class="et_pb_section"><script type="application/json">{"example":"<img src=\"' . $image . '\">"}</script>' . $body . '</section>',
    'knob image appears only inside an HTML template' => '<section class="et_pb_section"><template><img src="' . $image . '"></template>' . $body . '</section>',
    'knob filename appears only in plain text' => '<section class="et_pb_section"><p>' . $image . '</p>' . $body . '</section>',
    'heading and image in different nested child sections' => '<div class="et_pb_section outer-layout">' . $divi($image, '<p>LEGITIMATE IMAGE CONTENT</p>') . $divi(str_replace('-1.png', '-other.png', $image), $body) . '</div>',
);
foreach ($negatives as $label => $content) {
    $set_context($before . $content . $after);
    $ordinary = $render(false); $actual = $render(true);
    $check($actual === $ordinary, 'Unrelated content remains byte-identical: ' . $label . '.');
}
$set_context($before . $section . $after);
$native = '<div class="et_pb_section" data-layout="left > right"><div class="et_pb_row"><img src="' . $image . '">' . $body . '</div></div>';
$check(apply_filters('et_module_shortcode_output', $native, 'et_pb_section', null) === '', 'The actual Divi section-output hook suppresses a matching complete component.');
$check(apply_filters('et_module_shortcode_output', $native, 'et_pb_row', null) === $native, 'Divi row/module output is not filtered as a section.');
$check(apply_filters('do_shortcode_tag', $native, 'et_pb_section', array(), array()) === '', 'The normal WordPress shortcode-output hook suppresses a matching section without source hashes.');
$previous_post = $post;
$post = new WP_Post((object) array_merge(get_object_vars($post), array('ID' => 987654, 'post_type' => 'et_pb_layout')));
$check(apply_filters('et_module_shortcode_output', $native, 'et_pb_section', null) === '', 'A Theme Builder library post becoming current does not hide the public page context.');
$post = $previous_post;
$outer_start = '<section class="et_pb_section outer-layout"><p id="cleanup-outer-preserved">OUTER LAYOUT CONTENT</p>';
$outer_end = '<p id="cleanup-outer-after">OUTER LAYOUT AFTER</p></section>';
$check(Yoona_SEO_Content_Cleanup::filter_content($outer_start . $native . $outer_end) === $outer_start . $outer_end, 'Only the innermost matching Divi section is removed from a larger layout.');
$broken = '<div class="et_pb_section"><div class="et_pb_row"><img src="' . $image . '">' . $body . '</div>';
$check(Yoona_SEO_Content_Cleanup::filter_content($broken) === $broken, 'Malformed unclosed layout markup is retained without guessing removal boundaries.');
$set_context($before . $section . $after);
$early = static function ($content) { return do_shortcode($content); };
add_filter('the_content', $early, 9);
$ordinary = $render(false); $expected = $without_target($ordinary); $actual = $render(true);
remove_filter('the_content', $early, 9);
$check($actual === $expected && $actual !== $ordinary, 'Earlier the_content shortcode rendering cannot preserve the retired block.');
$transform = static function ($content) { return str_replace('MASZ PYTANIE?', 'Masz <span>pytanie?</span>', $content); };
add_filter('the_content', $transform, 12);
$ordinary = $render(false); $expected = $without_target($ordinary); $actual = $render(true);
remove_filter('the_content', $transform, 12);
$check($actual === $expected && $actual !== $ordinary, 'Later rendered typography and email transformations cannot preserve the retired block.');
wp_cache_delete($fixture_id, 'posts'); $post = $saved_post; $wp_query = $saved_query; wp_reset_postdata();
$after_db = $snapshot();
if ($after_db !== $before_db) foreach ($after_db as $table => $rows) foreach ($rows as $index => $row) {
    $old_row = isset($before_db[$table][$index]) ? $before_db[$table][$index] : array();
    foreach ($row as $column => $value) if (!array_key_exists($column, $old_row) || $old_row[$column] !== $value) WP_CLI::log('Snapshot difference: ' . $table . ' row ' . $index . ' column ' . $column . ' (values withheld).');
}
$check($after_db === $before_db, 'Synthetic render checks leave every stored post and metadata row unchanged.');
WP_CLI::success($count . ' semantic cleanup regression assertions passed.');
