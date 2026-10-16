<?php
/** Run against a real, disposable WordPress database using WP CLI eval-file. */
if (!defined('WP_CLI') || !WP_CLI) {
    exit(1);
}

$GLOBALS['yoona_test_count'] = 0;
function yoona_assert($condition, $message) {
    global $yoona_test_count;
    if (!$condition) {
        WP_CLI::error($message);
    }
    ++$yoona_test_count;
    WP_CLI::log('PASS ' . $message);
}
function yoona_ok($result, $operation) {
    yoona_assert(!is_wp_error($result), $operation . (is_wp_error($result) ? ': ' . $result->get_error_message() : ''));
    return $result;
}
function yoona_snapshot($ids) {
    $result = array();
    foreach ($ids as $id) {
        clean_post_cache($id);
        $post = get_post($id);
        $result[$id] = array(
            'title' => $post->post_title,
            'content' => $post->post_content,
            'meta' => get_post_meta($id),
        );
    }
    return $result;
}
function yoona_bundle($pages) {
    return array(
        'schema_version' => 1,
        'site_url' => home_url('/'),
        'audit_verified' => true,
        'pages' => $pages,
    );
}
function yoona_page($path, $extra = array()) {
    return array_merge(array(
        'path' => $path,
        'seo_title' => 'Nowy tytuł Yoona — ' . ($path === '/' ? 'strona główna' : trim($path, '/')),
        'meta_description' => 'Sprawdź ofertę Yoona. Poznaj szczegóły i skontaktuj się z zespołem.',
    ), $extra);
}
function yoona_preview_path($preview, $path) {
    foreach ($preview['pages'] as $page) {
        if ($page['path'] === $path) {
            return $page;
        }
    }
    WP_CLI::error('Preview omitted path: ' . $path);
}
function yoona_no_mutation($bundle, $paths, $ids, $label) {
    $before = yoona_snapshot($ids);
    Yoona_SEO_Engine::apply($bundle, $paths);
    yoona_assert(yoona_snapshot($ids) === $before, $label);
}

$ids = get_option('yoona_test_ids');
$admin = get_user_by('login', 'yoona_test_admin');
wp_set_current_user($admin->ID);
yoona_assert(class_exists('Yoona_SEO_Engine'), 'Plugin activated and its engine loaded.');
foreach (get_option('yoona_test_activation_snapshot') as $id => $snapshot) {
    yoona_assert(get_post($id, ARRAY_A) === $snapshot['post'] && get_post_meta($id) === $snapshot['meta'], 'Activation leaves post ' . $id . ' and all its metadata unchanged.');
}

$initial = yoona_snapshot($ids);
$simple = yoona_bundle(array(yoona_page('/'), yoona_page('/o-nas/')));
$normalized = yoona_ok(Yoona_SEO_Engine::validate_bundle($simple), 'Valid JSON structure is accepted.');
update_option('yoona_seo_imported_bundle', $normalized, false);
$loaded = yoona_ok(Yoona_SEO_Engine::load_bundle(), 'Imported bundle is loaded.');
yoona_assert($loaded['pages'][0]['seo_title'] === $simple['pages'][0]['seo_title'], 'Imported JSON takes priority over the bundled file.');
yoona_assert(yoona_snapshot($ids) === $initial, 'Importing a bundle does not update published content or metadata.');

$false = $simple;
$false['audit_verified'] = false;
yoona_no_mutation($false, array('/'), $ids, 'An unverified audit cannot change any post.');
$empty = yoona_bundle(array());
yoona_no_mutation($empty, array('/'), $ids, 'An empty bundle cannot change any post.');
yoona_no_mutation($simple, array(), $ids, 'An empty selection cannot change any post.');
$other_site = $simple;
$other_site['site_url'] = 'https://wrong.example.invalid';
yoona_no_mutation($other_site, array('/'), $ids, 'A bundle for another website cannot change any post.');

// Simulate a second worker creating the lock after this worker cached its absence.
// add_option() may overwrite that row; INSERT IGNORE must leave it untouched.
global $wpdb;
delete_option(Yoona_SEO_Engine::LOCK_OPTION);
get_option(Yoona_SEO_Engine::LOCK_OPTION);
$other_worker_lock = array('token' => wp_generate_uuid4(), 'expires' => time() + 300);
$wpdb->insert($wpdb->options, array('option_name' => Yoona_SEO_Engine::LOCK_OPTION, 'option_value' => maybe_serialize($other_worker_lock), 'autoload' => 'no'));
$before_locked = yoona_snapshot($ids);
$locked = Yoona_SEO_Engine::apply($simple, array('/'));
yoona_assert(is_wp_error($locked) && $locked->get_error_code() === 'yoona_operation_locked', 'A concurrent operation lock is respected even with a stale missing-option cache.');
yoona_assert(yoona_snapshot($ids) === $before_locked, 'A rejected concurrent operation leaves all page values unchanged.');
yoona_assert($wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", Yoona_SEO_Engine::LOCK_OPTION)) === maybe_serialize($other_worker_lock), 'A rejected concurrent operation never replaces the other worker lock.');
$wpdb->delete($wpdb->options, array('option_name' => Yoona_SEO_Engine::LOCK_OPTION));
wp_cache_delete(Yoona_SEO_Engine::LOCK_OPTION, 'options');
wp_cache_delete('notoptions', 'options');

foreach (array(
    'Executable script' => yoona_page('/o-nas/', array('content' => '<script>alert(1)</script>', 'expected_content_sha256' => hash('sha256', get_post($ids['about'])->post_content))),
    'Inline event handler' => yoona_page('/o-nas/', array('content' => '<p onclick="alert(1)">Test</p>', 'expected_content_sha256' => hash('sha256', get_post($ids['about'])->post_content))),
    'External absolute URL path' => yoona_page('https://outside.example.invalid/o-nas/'),
    'Path traversal' => yoona_page('/../o-nas/'),
) as $label => $page) {
    $unsafe = yoona_bundle(array($page));
    yoona_assert(is_wp_error(Yoona_SEO_Engine::validate_bundle($unsafe)), $label . ' is rejected during import validation.');
    yoona_no_mutation($unsafe, array($page['path']), $ids, $label . ' cannot update the site.');
}
$duplicate = yoona_bundle(array(yoona_page('/'), yoona_page('/')));
yoona_assert(is_wp_error(Yoona_SEO_Engine::validate_bundle($duplicate)), 'Duplicate paths are rejected.');

$preview = yoona_ok(Yoona_SEO_Engine::preview($simple), 'Preview is generated.');
$home = yoona_preview_path($preview, '/');
$about = yoona_preview_path($preview, '/o-nas/');
yoona_assert($home['post_id'] === $ids['home'] && $home['can_apply'], 'The root URL maps to the configured static front page.');
yoona_assert($about['post_id'] === $ids['about'] && $about['can_apply'], 'A normal permalink maps to the correct published page.');
yoona_assert($about['current']['seo_title'] === 'Poprzedni tytuł SEO', 'Preview contains the existing SEO title.');
yoona_assert(yoona_snapshot($ids) === $initial, 'Preview is read-only.');

$result = yoona_ok(Yoona_SEO_Engine::apply($simple, array('/')), 'Selected homepage update succeeds.');
yoona_assert(!empty($result['batch_id']), 'Deployment returns a backup batch identifier.');
yoona_assert(get_post_meta($ids['home'], '_yoona_seo_title', true) === $simple['pages'][0]['seo_title'], 'Selected homepage receives its exact SEO title.');
yoona_assert(get_post_meta($ids['home'], '_yoona_seo_description', true) === $simple['pages'][0]['meta_description'], 'Selected homepage receives its exact meta description.');
$selected_snapshot = yoona_snapshot($ids);
yoona_assert($selected_snapshot[$ids['about']] === $initial[$ids['about']], 'An unselected page is untouched.');
yoona_assert($selected_snapshot[$ids['home']]['content'] === $initial[$ids['home']]['content'], 'An SEO-only update does not replace the page body.');
yoona_assert(count(Yoona_SEO_Engine::list_batches()) >= 1, 'Backup history lists the deployment.');
$again = yoona_ok(Yoona_SEO_Engine::preview($simple), 'A second preview succeeds.');
yoona_assert(yoona_preview_path($again, '/')['status'] === 'unchanged', 'Preview detects values that are already deployed.');
yoona_ok(Yoona_SEO_Engine::rollback($result['batch_id']), 'Rollback of SEO changes succeeds.');
yoona_assert(yoona_snapshot($ids) === $initial, 'Rollback restores values and removes metadata that did not exist before.');

$content_page = yoona_page('/o-nas/', array(
    'post_title' => 'Poznaj Yoona',
    'expected_post_title' => get_post($ids['about'])->post_title,
    'content' => '<!-- wp:paragraph --><p>Nowa treść z polskimi znakami: ąęłóź.</p><!-- /wp:paragraph -->',
    'expected_content_sha256' => hash('sha256', get_post($ids['about'])->post_content),
));
$content_bundle = yoona_bundle(array($content_page));
$missing_title = $content_bundle;
unset($missing_title['pages'][0]['expected_post_title']);
yoona_no_mutation($missing_title, array('/o-nas/'), $ids, 'A page-title change without its original title is blocked.');
$stale_title = $content_bundle;
$stale_title['pages'][0]['expected_post_title'] = 'An older title';
yoona_no_mutation($stale_title, array('/o-nas/'), $ids, 'A page-title change cannot overwrite a newer editor title.');
$bad_hash = $content_bundle;
$bad_hash['pages'][0]['expected_content_sha256'] = str_repeat('0', 64);
yoona_no_mutation($bad_hash, array('/o-nas/'), $ids, 'A stale content hash prevents all changes to the affected page.');
$missing_hash = $content_bundle;
unset($missing_hash['pages'][0]['expected_content_sha256']);
yoona_no_mutation($missing_hash, array('/o-nas/'), $ids, 'Body replacement without an expected content hash is blocked.');
$content_result = yoona_ok(Yoona_SEO_Engine::apply($content_bundle, array('/o-nas/')), 'Verified body and SEO changes can be deployed together.');
yoona_assert($content_result['status'] === 'success', 'The body deployment reports successful writes.');
yoona_assert(get_post($ids['about'])->post_content === $content_page['content'], 'Deployed HTML and Polish text retain their exact bytes.');
yoona_assert(get_post($ids['about'])->post_title === $content_page['post_title'], 'The explicit page title change is saved.');
yoona_ok(Yoona_SEO_Engine::rollback($content_result['batch_id']), 'Body deployment rollback succeeds.');
yoona_assert(yoona_snapshot($ids) === $initial, 'Rollback restores original body, page title and previous SEO metadata.');

$conflict_result = yoona_ok(Yoona_SEO_Engine::apply($content_bundle, array('/o-nas/')), 'A deployment is available for conflict testing.');
wp_update_post(array('ID' => $ids['about'], 'post_content' => '<p>Zmiana redaktora po wdrożeniu.</p>'));
Yoona_SEO_Engine::rollback($conflict_result['batch_id']);
yoona_assert(get_post($ids['about'])->post_content === '<p>Zmiana redaktora po wdrożeniu.</p>', 'Rollback never overwrites a subsequent editor change.');

$excluded = yoona_bundle(array(yoona_page('/szkic/'), yoona_page('/prywatna/')));
$excluded_preview = yoona_ok(Yoona_SEO_Engine::preview($excluded), 'Non-public page preview completes.');
yoona_assert(!yoona_preview_path($excluded_preview, '/szkic/')['can_apply'] && !yoona_preview_path($excluded_preview, '/prywatna/')['can_apply'], 'Draft and private pages cannot be deployed by public URL.');
yoona_no_mutation($excluded, array('/szkic/', '/prywatna/'), $ids, 'Draft and private page data remain untouched.');
$builder = yoona_bundle(array(yoona_page('/builder/', array('content' => '<p>Replacement</p>', 'expected_content_sha256' => hash('sha256', get_post($ids['builder'])->post_content)))));
yoona_no_mutation($builder, array('/builder/'), $ids, 'Builder-owned page content is protected from replacement.');

// A title-only wp_update_post still runs content_save_pre over the old body.
// Deny unfiltered_html, as on multisite, and ensure KSES cannot silently eat it.
kses_remove_filters();
wp_update_post(array('ID' => $ids['restricted'], 'post_content' => '<p>Trusted embed.</p><iframe src="https://example.invalid/embed"></iframe>'));
$deny_unfiltered = function ($caps, $cap) {
    return $cap === 'unfiltered_html' ? array('do_not_allow') : $caps;
};
add_filter('map_meta_cap', $deny_unfiltered, 10, 2);
kses_init_filters();
$restricted_bundle = yoona_bundle(array(yoona_page('/restricted/', array('post_title' => 'Nowy tytuł', 'expected_post_title' => get_post($ids['restricted'])->post_title))));
yoona_no_mutation($restricted_bundle, array('/restricted/'), $ids, 'A title-only edit cannot strip an existing trusted embed through KSES.');
remove_filter('map_meta_cap', $deny_unfiltered, 10);
kses_init();

$trusted_before = get_post($ids['restricted'])->post_content;
$safe_body = yoona_bundle(array(yoona_page('/restricted/', array('content' => '<p>Bezpieczna nowa treść.</p>', 'expected_content_sha256' => hash('sha256', $trusted_before)))));
$trusted_result = yoona_ok(Yoona_SEO_Engine::apply($safe_body, array('/restricted/')), 'A trusted administrator can deploy a safe replacement of an embed page.');
yoona_assert($trusted_result['status'] === 'success', 'The trusted embed-page deployment succeeds.');
add_filter('map_meta_cap', $deny_unfiltered, 10, 2);
kses_init_filters();
$safe_snapshot = yoona_snapshot($ids);
Yoona_SEO_Engine::rollback($trusted_result['batch_id']);
yoona_assert(yoona_snapshot($ids) === $safe_snapshot, 'A later administrator without unfiltered_html cannot restore a filtered or broken embed.');
remove_filter('map_meta_cap', $deny_unfiltered, 10);
kses_init();
yoona_ok(Yoona_SEO_Engine::rollback($trusted_result['batch_id']), 'A trusted administrator can retry restoring the original embed.');
yoona_assert(get_post($ids['restricted'])->post_content === $trusted_before, 'Retried trusted rollback restores the exact original embed.');

$diagnostics = yoona_ok(Yoona_SEO_Engine::export_diagnostics(), 'Administrator can export diagnostics.');
$diagnostic_json = wp_json_encode($diagnostics);
yoona_assert(strpos($diagnostic_json, 'local-admin-test-only') === false && strpos($diagnostic_json, 'user_pass') === false, 'Diagnostics contain no user password fields or test administrator password.');
yoona_assert(strpos($diagnostic_json, 'Oryginalna treść: draft') === false && strpos($diagnostic_json, 'Oryginalna treść: private') === false, 'Diagnostics exclude private and draft content.');

$pretty_structure = get_option('permalink_structure');
update_option('permalink_structure', '');
$GLOBALS['wp_rewrite']->init();
$plain_diagnostics = yoona_ok(Yoona_SEO_Engine::export_diagnostics(), 'Diagnostics can export a site using plain query-string permalinks.');
$plain_pages = array_column($plain_diagnostics['pages'], null, 'post_id');
yoona_assert($plain_pages[$ids['home']]['path'] === '/' && !empty($plain_pages[$ids['home']]['path_supported']), 'A static front page keeps its supported root path with plain permalinks.');
yoona_assert($plain_pages[$ids['about']]['path'] === null && empty($plain_pages[$ids['about']]['path_supported']), 'A query-string permalink is explicitly unsupported and never exported as a false homepage path.');
update_option('permalink_structure', $pretty_structure);
$GLOBALS['wp_rewrite']->init();

$subscriber_id = wp_create_user('yoona_test_subscriber', 'local-subscriber-test-only', 'subscriber@example.invalid');
wp_set_current_user($subscriber_id);
yoona_assert(is_wp_error(Yoona_SEO_Engine::apply($simple, array('/'))), 'A subscriber cannot deploy changes.');
yoona_assert(is_wp_error(Yoona_SEO_Engine::rollback($result['batch_id'])), 'A subscriber cannot roll back changes.');
yoona_assert(is_wp_error(Yoona_SEO_Engine::export_diagnostics()), 'A subscriber cannot export site content.');
wp_set_current_user($admin->ID);

update_post_meta($ids['home'], '_yoona_seo_title', 'Yoona — tytuł integracyjny');
update_post_meta($ids['home'], '_yoona_seo_description', 'Oferta Yoona & kontakt.');
$GLOBALS['wp_query'] = new WP_Query(array('page_id' => $ids['home']));
yoona_assert(apply_filters('pre_get_document_title', 'old title') === 'Yoona — tytuł integracyjny', 'WordPress document title receives the deployed SEO title.');
ob_start();
Yoona_SEO_Plugin::description_tag();
$description = ob_get_clean();
yoona_assert(substr_count($description, 'name="description"') === 1 && strpos($description, 'Oferta Yoona &amp; kontakt.') !== false, 'Standalone description output is a single correctly escaped HTML tag.');

// Force a write to fail after the first metadata update, then deny immediate
// restoration. The durable backup must remain reachable in the admin interface.
$before_recovery = yoona_snapshot($ids);
$deny_description_write = function ($check, $post_id, $meta_key) use ($ids) {
    return $post_id === $ids['home'] && $meta_key === '_yoona_seo_description' ? false : $check;
};
$deny_title_restore = function ($check, $post_id, $meta_key) use ($ids) {
    return $post_id === $ids['home'] && $meta_key === '_yoona_seo_title' ? false : $check;
};
add_filter('update_post_metadata', $deny_description_write, 10, 3);
add_filter('delete_post_metadata', $deny_title_restore, 10, 3);
$failed = yoona_ok(Yoona_SEO_Engine::apply(yoona_bundle(array(yoona_page('/'))), array('/')), 'A forced write/restore failure returns a recoverable deployment record.');
remove_filter('update_post_metadata', $deny_description_write, 10);
remove_filter('delete_post_metadata', $deny_title_restore, 10);
yoona_assert(!empty($failed['batch_id']) && $failed['status'] === 'failed', 'A failed deployment still returns its durable backup identifier.');
$recovery_history = Yoona_SEO_Engine::list_batches();
$recovery_index = null;
foreach ($recovery_history as $entry) {
    if ($entry['batch_id'] === $failed['batch_id']) {
        $recovery_index = $entry;
    }
}
yoona_assert($recovery_index && !empty($recovery_index['can_rollback']) && !empty($recovery_index['recovery_needed']), 'Failed partial writes remain explicitly available for recovery.');
ob_start();
Yoona_SEO_Admin::render();
$recovery_admin = ob_get_clean();
yoona_assert(strpos($recovery_admin, 'value="' . $failed['batch_id'] . '"') !== false, 'The admin panel offers a rollback form for the failed deployment.');
yoona_ok(Yoona_SEO_Engine::rollback($failed['batch_id']), 'Recovery can be retried after the underlying write failure is resolved.');
yoona_assert(yoona_snapshot($ids) === $before_recovery, 'Retried recovery restores all original page values.');

$third_value = 'Tytuł zmieniony równolegle przez redaktora';
$external_write = function ($check, $post_id, $meta_key) use ($ids, $third_value) {
    if ($post_id === $ids['post'] && $meta_key === '_yoona_seo_description') {
        update_post_meta($post_id, '_yoona_seo_title', $third_value);
        return false;
    }
    return $check;
};
add_filter('update_post_metadata', $external_write, 10, 3);
$third_result = yoona_ok(Yoona_SEO_Engine::apply(yoona_bundle(array(yoona_page('/poradnik/'))), array('/poradnik/')), 'An external third-value write during a failed deployment returns a reviewable record.');
remove_filter('update_post_metadata', $external_write, 10);
yoona_assert(get_post_meta($ids['post'], '_yoona_seo_title', true) === $third_value, 'Immediate error recovery preserves an unexpected concurrent editor value.');
$third_snapshot = yoona_snapshot($ids);
Yoona_SEO_Engine::rollback($third_result['batch_id']);
yoona_assert(yoona_snapshot($ids) === $third_snapshot, 'A later rollback also preserves a third-value conflict for manual review.');

foreach (array(array('s' => 'yoona-test-search'), array('post_type' => 'post')) as $query) {
    $GLOBALS['wp_query'] = new WP_Query($query);
    yoona_assert(Yoona_SEO_Plugin::title('Existing archive title') === 'Existing archive title' && Yoona_SEO_Plugin::description('Existing archive description') === 'Existing archive description', 'Search and archive queries preserve their original metadata.');
    ob_start();
    Yoona_SEO_Plugin::description_tag();
    yoona_assert(ob_get_clean() === '', 'Search and archive queries do not receive a singular-page description tag.');
}

delete_option('yoona_seo_imported_bundle');
WP_CLI::success($GLOBALS['yoona_test_count'] . ' real WordPress integration assertions passed.');
