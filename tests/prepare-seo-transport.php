<?php
/** Private local fixtures for actual admin JavaScript transport failures. */
if (!defined('WP_CLI') || !WP_CLI) {
    exit(1);
}
wp_set_current_user(get_user_by('login', 'yoona_test_admin')->ID);
kses_init();
$command = isset($args[0]) ? $args[0] : 'prepare';
$snapshot = static function ($post_id) {
    clean_post_cache($post_id);
    $post = get_post($post_id);
    $result = array('fields' => array(), 'meta' => array());
    foreach (array('post_title', 'post_content', 'post_excerpt', 'post_content_filtered', 'post_name') as $key) {
        $result['fields'][$key] = $post->$key;
    }
    foreach (array('rank_math_title', 'rank_math_description', '_yoona_seo_title', '_yoona_seo_description', '_et_pb_use_builder') as $key) {
        $result['meta'][$key] = array('exists' => metadata_exists('post', $post_id, $key), 'values' => get_post_meta($post_id, $key, false));
    }
    return $result;
};

if ('state' === $command) {
    $fixture = get_option('yoona_test_transport_fixture');
    $state = array('pages' => array(), 'batches' => array());
    foreach ($fixture['ids'] as $path => $id) {
        $actual = $snapshot($id);
        $state['pages'][$path] = array(
            'id' => $id,
            'exact_before' => $actual === $fixture['before'][$path],
            'exact_after' => $actual === $fixture['after'][$path],
            'title' => get_post_meta($id, 'rank_math_title', true),
            'description' => get_post_meta($id, 'rank_math_description', true),
        );
    }
    foreach (Yoona_SEO_Engine::list_batches() as $row) {
        $batch = get_option(Yoona_SEO_Engine::BATCH_PREFIX . $row['batch_id']);
        if (array_intersect(array_keys($fixture['ids']), array_column($batch['pages'], 'path'))) {
            $state['batches'][] = $row;
        }
    }
    WP_CLI::line(wp_json_encode($state));
    return;
}
if ('rollback' === $command) {
    $fixture = get_option('yoona_test_transport_fixture');
    foreach (Yoona_SEO_Engine::list_batches() as $row) {
        $batch = get_option(Yoona_SEO_Engine::BATCH_PREFIX . $row['batch_id']);
        if (array_intersect(array_keys($fixture['ids']), array_column($batch['pages'], 'path')) && $row['can_rollback']) {
            $result = Yoona_SEO_Engine::rollback($row['batch_id']);
            if (is_wp_error($result) || 'rolled_back' !== $result['status']) {
                WP_CLI::error('A transport fixture recovery rollback failed.');
            }
        }
    }
    WP_CLI::success('Restored transport fixture backups.');
    return;
}
if ('prepare' !== $command && 'cleanup' !== $command) {
    WP_CLI::error('Unknown fixture command.');
}

$old_fixture = get_option('yoona_test_transport_fixture');
if (is_array($old_fixture)) {
    $remove_batches = array();
    foreach (Yoona_SEO_Engine::list_batches() as $row) {
        $batch = get_option(Yoona_SEO_Engine::BATCH_PREFIX . $row['batch_id']);
        if (array_intersect(array_keys($old_fixture['ids']), array_column($batch['pages'], 'path'))) {
            Yoona_SEO_Engine::rollback($row['batch_id']);
            $remove_batches[] = $row['batch_id'];
            delete_option(Yoona_SEO_Engine::BATCH_PREFIX . $row['batch_id']);
        }
    }
    $index = get_option(Yoona_SEO_Engine::INDEX_OPTION, array());
    foreach ($remove_batches as $batch_id) {
        unset($index[$batch_id]);
    }
    update_option(Yoona_SEO_Engine::INDEX_OPTION, $index, false);
    foreach ($old_fixture['ids'] as $id) {
        wp_delete_post($id, true);
    }
}
if ('cleanup' === $command) {
    delete_option('yoona_test_transport_fixture');
    delete_option(Yoona_SEO_Engine::IMPORT_OPTION);
    return;
}

$bundle = array('schema_version' => 2, 'seo_storage' => 'rank_math', 'audit_verified' => true, 'site_url' => home_url('/'), 'pages' => array());
$fixture = array('ids' => array(), 'before' => array(), 'after' => array());
for ($number = 0; $number < 200; ++$number) {
    $name = $number < 3 ? array('large', 'second', 'third')[$number] : 'page-' . ($number + 1);
    $is_divi = $number < 3;
    $path = '/transport-' . $name . '/';
    $old = '<p>Oryginalna oferta ąęłóź &amp; kontakt.</p>';
    $new = '<p>Poprawiona oferta ąęłóź &amp; kontakt.</p>';
    $source = $is_divi ? '[et_pb_section][et_pb_row][et_pb_column type="4_4"]' : '<p>Oryginalna treść.</p>';
    if ($is_divi) {
        for ($i = 0; $i < 24; ++$i) {
            $source .= '[et_pb_text admin_label="Zachowaj moduł ' . $i . '"]' . $old . str_repeat('<p>Oryginalna treść rozbudowanej oferty do zachowania.</p>', 160) . '[/et_pb_text]';
        }
        $source .= '[et_pb_image src="https://example.invalid/preserve.jpg" /][/et_pb_column][/et_pb_row][/et_pb_section]';
    }
    $id = wp_insert_post(wp_slash(array('post_type' => 'page', 'post_status' => 'publish', 'post_name' => trim($path, '/'), 'post_title' => 'Transport fixture ' . $name, 'post_content' => $source)), true);
    if (is_wp_error($id)) {
        WP_CLI::error($id->get_error_message());
    }
    if ($is_divi) {
        update_post_meta($id, '_et_pb_use_builder', 'on');
    }
    $fixture['ids'][$path] = $id;
    $fixture['before'][$path] = $snapshot($id);
    $page = array('path' => $path, 'seo_title' => 'Nowy tytuł SEO ' . $name, 'meta_description' => 'Nowy opis Rank Math dla testu transportu ' . $name . '.');
    if ($is_divi) {
        $page['expected_content_sha256'] = hash('sha256', $source);
        $page['divi_text'] = array();
        for ($i = 0; $i < 17; ++$i) {
            $old_module = $old . str_repeat('<p>Oryginalna treść rozbudowanej oferty do zachowania.</p>', 160);
            $page['divi_text'][] = array('module_index' => $i, 'expected_html_sha256' => hash('sha256', $old_module), 'html' => $new);
        }
    }
    $bundle['pages'][] = $page;
    $after = $fixture['before'][$path];
    foreach (array('rank_math_title' => $page['seo_title'], 'rank_math_description' => $page['meta_description']) as $key => $value) {
        $after['meta'][$key] = array('exists' => true, 'values' => array($value));
    }
    if ($is_divi) {
        $replacements = 0;
        $after['fields']['post_content'] = preg_replace_callback('~(\[et_pb_text[^\]]*\]).*?(\[/et_pb_text\])~s', static function ($match) use ($new, &$replacements) {
            return ++$replacements <= 17 ? $match[1] . $new . $match[2] : $match[0];
        }, $source);
    }
    $fixture['after'][$path] = $after;
}
$validated = Yoona_SEO_Engine::validate_bundle($bundle);
if (is_wp_error($validated)) {
    WP_CLI::error($validated->get_error_message());
}
update_option(Yoona_SEO_Engine::IMPORT_OPTION, $validated, false);
update_option('yoona_test_transport_fixture', $fixture, false);
WP_CLI::success('Prepared 200 real transport fixtures, including 3 large Divi pages and 51 text patches.');
