<?php
/** Real WordPress/Rank Math persistence; synthetic Divi source, no paid theme. */
if (!defined('WP_CLI') || !WP_CLI) {
    exit(1);
}
$checks = 0;
$check = static function ($condition, $label) use (&$checks) {
    if (!$condition) {
        WP_CLI::error('Divi/Rank Math: ' . $label);
    }
    ++$checks;
    WP_CLI::log('PASS Divi/Rank Math ' . $label);
};
$ok = static function ($result, $label) use ($check) {
    $check(!is_wp_error($result), $label . (is_wp_error($result) ? ': ' . $result->get_error_message() : ''));
    return $result;
};
wp_set_current_user(get_user_by('login', 'yoona_test_admin')->ID);
kses_init();
$check(class_exists('RankMath') && defined('RANK_MATH_VERSION') && function_exists('rank_math'), 'The actual Rank Math plugin and its autoloader run in WordPress.');

$old_first = '<h1>Stara oferta — Łódź</h1><p>Tekst pierwszego modułu.</p>';
$old_second = '<h2>Drugi moduł</h2><p>Oryginalna informacja.</p>';
$old_toggle = '<p>Stara odpowiedź na pytanie klienta.</p>';
$new_first = '<h1>Nowa oferta — Łódź</h1><p>Sprawdź szczegóły &amp; kontakt.</p>';
$new_second = '<h2>Poznaj szczegóły</h2><p>Poprawiona treść drugiego modułu.</p>';
$new_toggle = '<p>Nowa odpowiedź, bez zmiany tytułu pytania.</p>';
$source = '[et_pb_section fb_built="1" _builder_version="4.27.4" background_image="https://example.invalid/bg.jpg" custom_padding="3px|4px|5px|6px"]'
    . "\n" . '[et_pb_row custom_margin="0px|auto||auto"][et_pb_column type="4_4" css_class="preserve-column"]'
    . '[et_pb_text _builder_version="4.27.4" text_font="Open Sans||||||||" text_text_color="#222222" admin_label="Pierwszy moduł"]' . $old_first . '[/et_pb_text]'
    . '[et_pb_image src="https://example.invalid/car.jpg" title_text="Zachowaj obraz" _builder_version="4.27.4" /]'
    . '[et_pb_gallery gallery_ids="12,34,56" gallery_orderby="rand"][/et_pb_gallery]'
    . '[et_pb_toggle title="Pytanie bez zmian" open="off" icon_color="#000000"]' . $old_toggle . '[/et_pb_toggle]'
    . '[et_pb_contact_form email="public@example.invalid" success_message="Dziękujemy"][et_pb_contact_field field_id="Name" required_mark="on"][/et_pb_contact_field][/et_pb_contact_form]'
    . '[et_pb_code]one[et_pb_line_break_holder]two[/et_pb_code]'
    . '[et_pb_text admin_label="Drugi moduł" custom_css_main_element="padding: 3px;"]' . $old_second . '[/et_pb_text]'
    . '[/et_pb_column][/et_pb_row][/et_pb_section]';
$expected = str_replace(array($old_first, $old_second, $old_toggle), array($new_first, $new_second, $new_toggle), $source);
$insert = static function ($slug, $title) use ($source, $ok) {
    $id = $ok(wp_insert_post(wp_slash(array('post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => $source)), true), 'Create a published synthetic Divi page.');
    update_post_meta($id, '_et_pb_use_builder', 'on');
    update_post_meta($id, 'yoona_test_untouched', 'Sentinel with Polish bytes: ąęłóź.');
    return $id;
};
$id = $insert('divi-native-test', 'Synthetic Divi');
$empty_id = $insert('divi-native-empty', 'Divi without previous SEO values');
update_post_meta($id, 'rank_math_title', '%title% %sep% Stary Rank Math');
// Deliberately leave rank_math_description absent: rollback must preserve absence.
add_post_meta($id, '_yoona_seo_title', 'Stara nakładka Yoona');
add_post_meta($id, '_yoona_seo_title', 'Druga stara wartość do odtworzenia');
update_post_meta($id, '_yoona_seo_description', 'Stary opis nakładki Yoona.');

$snapshot = static function ($post_id) {
    clean_post_cache($post_id);
    $post = get_post($post_id);
    $data = array('fields' => array(), 'meta' => array());
    foreach (array('post_title', 'post_content', 'post_excerpt', 'post_content_filtered', 'post_name') as $field) {
        $data['fields'][$field] = $post->$field;
    }
    foreach (array('_et_pb_use_builder', 'yoona_test_untouched', '_yoona_seo_title', '_yoona_seo_description', 'rank_math_title', 'rank_math_description') as $key) {
        $data['meta'][$key] = array('exists' => metadata_exists('post', $post_id, $key), 'values' => get_post_meta($post_id, $key, false));
    }
    return $data;
};
$patch = static function ($type, $index, $old, $new) {
    return array('module_type' => $type, 'module_index' => $index, 'expected_html_sha256' => hash('sha256', $old), 'html' => $new);
};
$page = array(
    'path' => '/divi-native-test/',
    'seo_title' => 'Yoona — natywny tytuł SEO Divi',
    'meta_description' => 'Natywny opis Rank Math dla strony z układem Divi.',
    'expected_content_sha256' => hash('sha256', $source),
    'divi_text' => array(
        $patch('et_pb_text', 1, $old_second, $new_second),
        $patch('et_pb_toggle', 0, $old_toggle, $new_toggle),
        $patch('et_pb_text', 0, $old_first, $new_first),
    ),
);
$bundle = array('schema_version' => 2, 'site_url' => home_url('/'), 'seo_storage' => 'rank_math', 'audit_verified' => true, 'pages' => array($page));
$before = $snapshot($id);
$validated = $ok(Yoona_SEO_Engine::validate_bundle($bundle), 'Schema 2 imports native Rank Math metadata and indexed Divi text changes.');
$preview = $ok(Yoona_SEO_Engine::preview($validated), 'Preview the native Divi deployment.');
$check($preview['pages'][0]['post_id'] === $id && $preview['pages'][0]['can_apply'], 'The Divi page maps to its public URL and is ready.');
$check(count($preview['pages'][0]['divi_preview']) === 3, 'Preview separately shows both text indices and toggle index zero.');
$check($snapshot($id) === $before, 'Divi preview preserves original content and metadata.');

$rejected = static function ($bad, $label) use ($id, $snapshot, $check) {
    $before_bad = $snapshot($id);
    Yoona_SEO_Engine::apply($bad, array('/divi-native-test/'));
    $check($snapshot($id) === $before_bad, $label);
};
$bad = $bundle;
$bad['pages'][0]['expected_content_sha256'] = str_repeat('0', 64);
$rejected($bad, 'A stale whole-page content hash blocks body and native metadata together.');
$bad = $bundle;
$bad['pages'][0]['divi_text'][0]['expected_html_sha256'] = str_repeat('0', 64);
$rejected($bad, 'A stale module hash blocks body and native metadata together.');
$bad = $bundle;
$bad['pages'][0]['divi_text'][0]['module_index'] = 500;
$rejected($bad, 'An out-of-range module index cannot change another module or metadata.');
$bad = $bundle;
$bad['pages'][0]['divi_text'][0]['html'] = '<p onclick="alert(1)">Unsafe</p>';
$check(is_wp_error(Yoona_SEO_Engine::validate_bundle($bad)), 'Unsafe module HTML is rejected before import.');
$bad = $bundle;
$bad['pages'][0]['divi_text'][0]['html'] = '[et_pb_image src="https://example.invalid/new.jpg" /]';
$check(is_wp_error(Yoona_SEO_Engine::validate_bundle($bad)), 'A text patch cannot introduce another shortcode.');
$bad = $bundle;
$bad['pages'][0]['content'] = '<p>Whole-page replacement</p>';
$check(is_wp_error(Yoona_SEO_Engine::validate_bundle($bad)), 'A Divi patch cannot also replace the entire page body.');

$result = $ok(Yoona_SEO_Engine::apply($validated, array('/divi-native-test/')), 'Deploy native Rank Math fields and surgical Divi changes.');
$check($result['status'] === 'success' && !empty($result['batch_id']), 'Native Divi deployment reports success with a durable backup.');
$check(get_post($id)->post_content === $expected, 'Stored Divi content matches the exact expected bytes; attributes, images, gallery, code and form remain untouched.');
$check(get_post_meta($id, 'rank_math_title', true) === $page['seo_title'] && get_post_meta($id, 'rank_math_description', true) === $page['meta_description'], 'Both SEO values are stored in the real Rank Math postmeta fields.');
$check(!metadata_exists('post', $id, '_yoona_seo_title') && !metadata_exists('post', $id, '_yoona_seo_description'), 'Native deployment removes both legacy Yoona overlays.');
$check(get_post_meta($id, '_et_pb_use_builder', true) === 'on' && get_post_meta($id, 'yoona_test_untouched', true) === 'Sentinel with Polish bytes: ąęłóź.', 'Builder configuration and unrelated metadata remain unchanged.');

// WordPress parses the preserved shortcode structure. These callbacks are test
// doubles; this does not claim visual compatibility with the licensed Divi theme.
foreach (array('et_pb_section', 'et_pb_row', 'et_pb_column', 'et_pb_text', 'et_pb_toggle') as $tag) {
    add_shortcode($tag, static function ($attrs, $content = '') { return do_shortcode($content); });
}
foreach (array('et_pb_image', 'et_pb_gallery', 'et_pb_contact_form', 'et_pb_code') as $tag) {
    add_shortcode($tag, static function ($attrs, $content = '', $shortcode = '') { return '<span data-preserved="' . esc_attr($shortcode) . '"></span>'; });
}
$rendered = do_shortcode(get_post($id)->post_content);
$check(strpos($rendered, $new_first) !== false && strpos($rendered, $new_second) !== false && strpos($rendered, $new_toggle) !== false, 'The real WordPress shortcode parser reaches all changed module bodies.');
$check(substr_count($rendered, 'data-preserved=') === 4, 'The shortcode parser still reaches the original image, gallery, form and code modules.');
$repeat_before = $snapshot($id);
$history_before = count(Yoona_SEO_Engine::list_batches());
$again = $ok(Yoona_SEO_Engine::preview($validated), 'Preview a previously deployed schema 2 bundle.');
$check($again['pages'][0]['status'] === 'unchanged' && !$again['pages'][0]['can_apply'], 'Re-importing the deployed Divi bundle is idempotent despite original baseline hashes.');
Yoona_SEO_Engine::apply($validated, array('/divi-native-test/'));
$check($snapshot($id) === $repeat_before && count(Yoona_SEO_Engine::list_batches()) === $history_before, 'Reapplying unchanged Divi content creates no extra writes or backup batch.');
$ok(Yoona_SEO_Engine::rollback($result['batch_id']), 'Rollback the native Divi deployment.');
$check($snapshot($id) === $before, 'Rollback restores exact Divi bytes, raw Rank Math templates, missing metadata and duplicate legacy overlay values.');

$empty_before = $snapshot($empty_id);
$empty_bundle = $bundle;
$empty_bundle['pages'][0]['path'] = '/divi-native-empty/';
unset($empty_bundle['pages'][0]['divi_text'], $empty_bundle['pages'][0]['expected_content_sha256']);
$empty_result = $ok(Yoona_SEO_Engine::apply($empty_bundle, array('/divi-native-empty/')), 'A native metadata-only update is supported on a Divi page.');
$check($empty_result['status'] === 'success' && get_post($empty_id)->post_content === $source, 'A metadata-only deployment preserves every Divi source byte.');
$ok(Yoona_SEO_Engine::rollback($empty_result['batch_id']), 'Rollback native metadata that previously did not exist.');
$check($snapshot($empty_id) === $empty_before, 'Rollback removes newly created native metadata and preserves absence of legacy overlays.');

$conflict_result = $ok(Yoona_SEO_Engine::apply($empty_bundle, array('/divi-native-empty/')), 'Prepare a native Rank Math rollback conflict.');
update_post_meta($empty_id, 'rank_math_description', 'Późniejsza zmiana opisu bezpośrednio w Rank Math.');
$conflict_before = $snapshot($empty_id);
Yoona_SEO_Engine::rollback($conflict_result['batch_id']);
$check($snapshot($empty_id) === $conflict_before, 'Rollback preserves a later edit made directly in native Rank Math metadata.');

$legacy = $empty_bundle;
$legacy['schema_version'] = 1;
unset($legacy['seo_storage']);
$legacy['pages'][0]['seo_title'] = 'Legacy overlay title';
$legacy_result = $ok(Yoona_SEO_Engine::apply($legacy, array('/divi-native-empty/')), 'A schema 1 overlay deployment remains supported with Rank Math active.');
$check($legacy_result['status'] === 'success', 'Legacy overlay deployment succeeds.');
update_post_meta($empty_id, 'rank_math_title', 'Natywny tytuł zmieniony po starej partii');
$ok(Yoona_SEO_Engine::rollback($legacy_result['batch_id']), 'An overlay-only backup can still be rolled back with Rank Math active.');
$check(!metadata_exists('post', $empty_id, '_yoona_seo_title') && get_post_meta($empty_id, 'rank_math_title', true) === 'Natywny tytuł zmieniony po starej partii', 'Legacy rollback removes its overlay and leaves untouched native Rank Math fields alone.');
WP_CLI::success($checks . ' real Divi/Rank Math integration assertions passed.');
