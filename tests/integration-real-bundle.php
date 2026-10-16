<?php
/** Verify the actual reviewed package against a private export in disposable tables. */
if (!defined('WP_CLI') || !WP_CLI || !isset($args[0], $args[1])) {
    exit(1);
}
global $table_prefix;
if ($table_prefix !== 'yoona_real_') {
    WP_CLI::error('Private source fixtures require the separate yoona_real_ table prefix.');
}
wp_set_current_user(get_user_by('login', 'yoona_test_admin')->ID);
kses_init();
global $wpdb;
$wpdb->suppress_errors(true);
$checks = 0;
$check = static function ($condition, $label) use (&$checks) {
    if (!$condition) {
        WP_CLI::error('Actual package: ' . $label . ' No source HTML is included in this report.');
    }
    ++$checks;
};
$export = json_decode(file_get_contents($args[0]), true);
$bundle = json_decode(file_get_contents($args[1]), true);
WP_CLI::log('Actual reviewed package SHA-256: ' . hash_file('sha256', $args[1]));
$check(is_array($export) && is_array($bundle), 'Private input files are valid JSON.');
$bundle = Yoona_SEO_Engine::validate_bundle($bundle);
$check(!is_wp_error($bundle), 'The actual package passes WordPress import and HTML validation.' . (is_wp_error($bundle) ? ' ' . $bundle->get_error_message() : ''));
$check(count($bundle['pages']) === count($export['pages']), 'The package covers every exported public item.');
$source_by_path = array_column($export['pages'], null, 'path');
$expected_content = array();
$body_items = 0;
$patch_count = 0;

foreach ($bundle['pages'] as $item) {
    $check(isset($source_by_path[$item['path']]), 'Every package path has a source record.');
    $source = $source_by_path[$item['path']]['post_content'];
    $check(hash('sha256', $source) === $source_by_path[$item['path']]['content_sha256'], 'Exported content hashes match the original bytes.');
    $expected_content[$item['path']] = $source;
    if (empty($item['divi_text'])) {
        continue;
    }
    ++$body_items;
    $patch_count += count($item['divi_text']);
    $check(isset($item['expected_content_sha256']) && hash_equals(hash('sha256', $source), $item['expected_content_sha256']), 'Every body update has the exact source baseline.');
    $patched = Yoona_SEO_Divi::prepare($source, $item['divi_text']);
    $check(!is_wp_error($patched), 'Every actual Divi patch resolves safely against its exported source: ' . $item['path'] . (is_wp_error($patched) ? ' ' . $patched->get_error_code() . ': ' . $patched->get_error_message() : ''));
    $lookup = array();
    foreach ($item['divi_text'] as $patch) {
        $lookup[$patch['module_type'] . ':' . $patch['module_index']] = $patch;
    }
    // Independent replacement over flat text/toggle shortcode bodies. Equality
    // proves that every byte outside the reviewed body spans is unchanged.
    $expected = $source;
    foreach (array('et_pb_text', 'et_pb_toggle') as $type) {
        $index = 0;
        $expected = preg_replace_callback(
            '/(\[' . $type . '\b[^\]]*\])(.*?)(\[\/' . $type . '\])/s',
            static function ($match) use ($lookup, $type, &$index, $check) {
                $key = $type . ':' . $index++;
                if (!isset($lookup[$key])) {
                    return $match[0];
                }
                $patch = $lookup[$key];
                $check(hash_equals(hash('sha256', $match[2]), $patch['expected_html_sha256']), 'Per-type module indices identify the exact audited HTML.');
                return $match[1] . $patch['html'] . $match[3];
            },
            $expected
        );
    }
    $check(is_string($expected) && $patched === $expected, 'Prepared content preserves every other byte, attribute and module.');
    $expected_content[$item['path']] = $expected;
}
WP_CLI::log(sprintf('PASS Actual package: %d items, %d body updates and %d module patches pass WordPress HTML and exact-source checks.', count($bundle['pages']), $body_items, $patch_count));

$old_to_new = array();
$ids_by_path = array();
$before = array();
$meta_keys = array('_yoona_seo_title', '_yoona_seo_description', 'rank_math_title', 'rank_math_description', '_et_pb_use_builder');
$snapshot = static function ($post_id) use ($meta_keys) {
    clean_post_cache($post_id);
    $post = get_post($post_id);
    $result = array('content' => $post->post_content, 'title' => $post->post_title, 'slug' => $post->post_name, 'meta' => array());
    foreach ($meta_keys as $key) {
        $result['meta'][$key] = array('exists' => metadata_exists('post', $post_id, $key), 'values' => get_post_meta($post_id, $key, false));
    }
    return $result;
};
foreach ($export['pages'] as $record) {
    $path = $record['path'];
    $check(is_string($path) && $path[0] === '/' && substr_count(trim($path, '/'), '/') === 0, 'Actual fixtures use supported flat source paths.');
    $id = wp_insert_post(wp_slash(array(
        'post_type' => $record['post_type'], 'post_status' => 'publish',
        'post_name' => $path === '/' ? 'source-home' : trim($path, '/'),
        'post_title' => $record['post_title'], 'post_content' => $record['post_content'],
    )), true);
    $check(!is_wp_error($id), 'Create a private source fixture.');
    if (in_array('Divi', $record['builders'], true)) {
        update_post_meta($id, '_et_pb_use_builder', 'on');
    }
    foreach ($record['seo_metadata'] as $key => $value) {
        update_post_meta($id, $key, wp_slash($value));
    }
    $check(get_post($id)->post_content === $record['post_content'], 'Source bytes survive storage in real WordPress.');
    $old_to_new[$record['post_id']] = $id;
    $ids_by_path[$path] = $id;
    $before[$path] = $snapshot($id);
}
foreach (array('show_on_front', 'permalink_structure') as $setting) {
    update_option($setting, $export['configuration'][$setting]);
}
foreach (array('page_on_front', 'page_for_posts') as $setting) {
    $old_id = $export['configuration'][$setting];
    update_option($setting, isset($old_to_new[$old_id]) ? $old_to_new[$old_id] : 0);
}
$GLOBALS['wp_rewrite']->init();
$preview = Yoona_SEO_Engine::preview($bundle);
$check(!is_wp_error($preview) && count($preview['pages']) === count($bundle['pages']), 'Preview resolves the complete actual package.');
foreach ($preview['pages'] as $row) {
    $check($row['post_id'] === $ids_by_path[$row['path']] && in_array($row['status'], array('ready', 'unchanged'), true), 'Every actual public URL maps to its own ready or unchanged fixture: ' . $row['path'] . ' ' . $row['status'] . ' ' . implode(' ', $row['messages']));
}
$batches = array();
foreach (array_chunk(array_column($bundle['pages'], 'path'), 10) as $selected) {
    $result = Yoona_SEO_Engine::apply($bundle, $selected);
    $check(!is_wp_error($result) && $result['status'] === 'success' && !empty($result['batch_id']), 'A ten-item actual package batch deploys successfully: ' . implode(', ', $selected) . (is_wp_error($result) ? ' ' . $result->get_error_message() : ' ' . $result['message']));
    $batches[] = $result['batch_id'];
}
foreach ($bundle['pages'] as $item) {
    $id = $ids_by_path[$item['path']];
    $check(get_post($id)->post_content === $expected_content[$item['path']], 'Deployed actual content matches the independent source-byte expectation.');
    $check(get_post_meta($id, 'rank_math_title', true) === $item['seo_title'] && get_post_meta($id, 'rank_math_description', true) === $item['meta_description'], 'Every actual item receives its exact native Rank Math metadata.');
    $check(!metadata_exists('post', $id, '_yoona_seo_title') && !metadata_exists('post', $id, '_yoona_seo_description'), 'Actual native deployments leave no legacy Yoona overlays.');
    $check(get_post($id)->post_title === $before[$item['path']]['title'], 'Actual package preserves original page and post titles.');
}
$again = Yoona_SEO_Engine::preview($bundle);
$check(!is_wp_error($again), 'The actual deployed package can be previewed again.');
foreach ($again['pages'] as $row) {
    $check($row['status'] === 'unchanged' && !$row['can_apply'], 'The entire actual package is idempotent.');
}
update_option('yoona_seo_imported_bundle', $bundle, false);
ob_start();
Yoona_SEO_Admin::render();
$admin_html = ob_get_clean();
foreach ($batches as $batch_id) {
    $check(strpos($admin_html, 'value="' . $batch_id . '"') !== false, 'Every batch, including batches older than ten entries, remains available in the admin rollback interface.');
}
unset($admin_html);
foreach (array_reverse($batches) as $batch_id) {
    $result = Yoona_SEO_Engine::rollback($batch_id);
    $check(!is_wp_error($result) && $result['status'] === 'rolled_back', 'Every actual package batch rolls back successfully.');
}
foreach ($ids_by_path as $path => $id) {
    $check($snapshot($id) === $before[$path], 'Actual rollback restores original bytes and exact presence of SEO metadata.');
}
WP_CLI::success(sprintf('%d private actual-source assertions passed: %d items deployed in %d batches, re-previewed unchanged and fully rolled back. No source content was published in the web-served tables or test report.', $checks, count($bundle['pages']), count($batches)));
