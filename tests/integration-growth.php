<?php
/** Disposable service-guide fixtures, authorization probes and preservation checks. */
if (!defined('WP_CLI') || !WP_CLI || !isset($args[0])) exit(1);
global $wpdb, $wp_query, $post;
$count = 0;
$check = static function ($condition, $label) use (&$count) {
    if (!$condition) WP_CLI::error('Growth: ' . $label);
    ++$count;
    WP_CLI::log('PASS Growth ' . $label);
};
$snapshot = static function () use ($wpdb) {
    return array('posts' => $wpdb->get_results("SELECT * FROM {$wpdb->posts} ORDER BY ID", ARRAY_A), 'meta' => $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A));
};
$mode = $args[0];
if ($mode === 'snapshot') {
    update_option('yoona_test_growth_snapshot', $snapshot(), false);
    WP_CLI::success('Captured every post and metadata row before service-guide browsing.');
    return;
}
if ($mode === 'verify') {
    $actual = $snapshot(); $expected = get_option('yoona_test_growth_snapshot');
    if ($actual !== $expected) {
        foreach (array('posts' => 'ID', 'meta' => 'meta_id') as $table => $key) {
            $before = array_column($expected[$table], null, $key); $after = array_column($actual[$table], null, $key);
            foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $id) {
                $a = isset($before[$id]) ? $before[$id] : array(); $b = isset($after[$id]) ? $after[$id] : array();
                if ($a === $b) continue;
                $fields = array(); foreach (array_unique(array_merge(array_keys($a), array_keys($b))) as $field) if (!array_key_exists($field, $a) || !array_key_exists($field, $b) || $a[$field] !== $b[$field]) $fields[] = $field;
                WP_CLI::log('Changed fixture row ' . $table . '/' . $id . ': ' . implode(', ', $fields) . (isset($b['meta_key']) ? ' key=' . $b['meta_key'] : ''));
            }
        }
    }
    $check($actual === $expected, 'Every post and metadata row remains byte-identical after frontend and security requests.');
    WP_CLI::success($count . ' complete growth database preservation assertion passed.');
    return;
}
$check(class_exists('Yoona_SEO_Service_Guides') && class_exists('Yoona_SEO_Growth_Pages'), 'Current guide and explicit page-install classes are loaded.');
$guides = Yoona_SEO_Service_Guides::data();
$existing_paths = array_keys(Yoona_SEO_Design::data()['offer_heroes']);
$child_paths = array_values(array_diff(array_keys($guides), $existing_paths));
$check(count($guides) === 47 && count($existing_paths) === 41 && count($child_paths) === 6, 'The reviewed catalogue has 41 existing service pages and six distinct specialist pages.');
if ($mode === 'private') {
    if (empty($args[1]) || !is_readable($args[1])) WP_CLI::error('Private mode requires a CLI-only source export.');
    $records = array_column(json_decode(file_get_contents($args[1]), true)['pages'], null, 'path');
    $projects = Yoona_SEO_Project_Guides::data();
    $patches = array_column(json_decode(file_get_contents(dirname(YOONA_SEO_FILE) . '/content/changes.json'), true)['pages'], null, 'path');
    $check(count($projects) === 150, 'The reviewed project corpus has 150 factual guides.');
    $private_baseline = $snapshot();
    $media_parser = new ReflectionMethod('Yoona_SEO_Service_Guides', 'rendered_media');
    $media_parser->setAccessible(true);
    $image_sources = static function ($html) {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        $sources = array(); foreach ($dom->getElementsByTagName('img') as $image) $sources[] = $image->getAttribute('src');
        return $sources;
    };
    $gallery = '<figure class="wp-block-gallery" data-label="a > b"><figure class="wp-block-image"><a href="/one/"><img src="/one.jpg" loading="eager"><figcaption>Source caption</figcaption></a></figure><figure><picture><source srcset="/two.webp"><img src="/two.jpg" loading="lazy"></picture></figure></figure>';
    $form = '<div class="et_pb_module et_pb_contact_form_container"><form><label>Name<input name="name"></label><textarea>literal &lt;img&gt;</textarea></form></div>';
    $mixed = $media_parser->invoke(null, '<section><p>Original text</p>' . $gallery . '<img src="/loose.jpg"><div class="et_pb_image"><img src="/divi.jpg"></div>' . $form . '</section>');
    $check(count($mixed) === 4 && $mixed[0]['html'] === $gallery && $mixed[3]['html'] === $form, 'Balanced final HTML preserves complete nested native galleries and Divi forms alongside loose and Divi images.');
    $check($image_sources(implode('', array_column($mixed, 'html'))) === array('/one.jpg', '/two.jpg', '/loose.jpg', '/divi.jpg'), 'Mixed galleries and media retain every source image once and in order.');
    $seed = get_post((int) get_option('page_on_front'), ARRAY_A);
    $fixture_id = 900000;
    $set_context = static function ($path, $content, $type = 'post', $status = 'publish', $password = '') use (&$wp_query, &$post, &$fixture_id, $seed, $records) {
        ++$fixture_id;
        $record = $seed;
        $record['ID'] = $fixture_id; $record['post_name'] = trim($path, '/'); $record['post_parent'] = 0;
        $record['post_title'] = $records[$path]['post_title']; $record['post_content'] = $content;
        $record['post_type'] = $type; $record['post_status'] = $status; $record['post_password'] = $password;
        $post = new WP_Post((object) $record);
        wp_cache_set($fixture_id, $post, 'posts');
        $wp_query = new WP_Query(); $wp_query->is_page = $type === 'page'; $wp_query->is_single = $type === 'post'; $wp_query->is_singular = true;
        $wp_query->queried_object = $post; $wp_query->queried_object_id = $fixture_id; $wp_query->post = $post; $wp_query->posts = array($post); $wp_query->post_count = 1; $wp_query->current_post = 0;
        setup_postdata($post);
        return $fixture_id;
    };
    $variants = 0;
    foreach ($projects as $path => $definition) {
        $check(isset($records[$path]) && $records[$path]['post_type'] === 'post' && $definition['title'] === $records[$path]['post_title'], 'A project guide belongs to its actual source post and title: ' . $path);
        $source = $records[$path]['post_content'];
        $contents = array($source);
        if (count($definition['source_sha256']) > 1) {
            $patched = Yoona_SEO_Divi::prepare($source, $patches[$path]['divi_text']);
            $check(!is_wp_error($patched), 'A known SEO version is reproducible from the frozen patch bundle: ' . $path);
            $contents[] = $patched;
        }
        foreach ($contents as $content) {
            $id = $set_context($path, $content);
            $context = Yoona_SEO_Project_Guides::context($id);
            $check(is_array($context) && $context['path'] === $path && $context['summary'] === $definition['summary'], 'An exact original or reviewed SEO version resolves the factual project guide: ' . $path);
            $rendered = apply_filters('the_content', $content);
            $ranges = $media_parser->invoke(null, $rendered);
            $check($image_sources($rendered) === $image_sources(implode('', array_column($ranges, 'html'))), 'Final rendered project media retain all source photographs after WordPress image filters: ' . $path);
            ++$variants;
            wp_cache_delete($id, 'posts');
        }
        $id = $set_context($path, $source . "\n<!-- later editorial change -->");
        $check(!Yoona_SEO_Project_Guides::context($id), 'A later editorial change falls back without replacing project text: ' . $path);
        wp_cache_delete($id, 'posts');
        $id = $set_context($path, $source);
        $post->post_title .= ' — later title change'; wp_cache_set($id, $post, 'posts');
        $check(!Yoona_SEO_Project_Guides::context($id), 'A later project title change falls back instead of retaining stale project facts: ' . $path);
        wp_cache_delete($id, 'posts');
    }
    $check($variants === 160, 'All 150 original and ten reviewed SEO source variants are accepted.');
    foreach (array('draft', 'private') as $status) {
        $path = array_key_first($projects); $id = $set_context($path, $records[$path]['post_content'], 'post', $status);
        $check(!Yoona_SEO_Project_Guides::context($id), 'A nonpublic project never exposes its factual guide.'); wp_cache_delete($id, 'posts');
    }
    $path = array_key_first($projects); $id = $set_context($path, $records[$path]['post_content'], 'post', 'publish', 'local-secret');
    $check(!Yoona_SEO_Project_Guides::context($id), 'A password-protected project never exposes its guide.'); wp_cache_delete($id, 'posts');
    $check(!isset($projects['/drukarnia-wielkoformatowa-olsztyn-ostroda/']) && !isset($projects['/agencja-reklamowa-olsztyn-yoona/']) && !isset($projects['/folia-szroniona-billenium/']), 'Two general articles and the empty project have no invented case-study content.');
    $check(empty($projects['/pylony-ergo-arena/']['location']) && empty($projects['/markiza-irjados/']['location']), 'Examples without verified project locations omit the location field.');
    $check($snapshot() === $private_baseline, 'All private source checks used memory only and wrote no source HTML or metadata to the served database.');
    WP_CLI::success($count . ' memory-only private project source assertions passed; no private body was printed.');
    return;
}
$query_page = static function ($id) use (&$wp_query, &$post) {
    $wp_query = new WP_Query(array('page_id' => $id, 'post_type' => 'page'));
    $post = get_post($id);
    if ($post) setup_postdata($post);
};
if ($mode === 'prepare') {
    // Re-running these disposable fixtures removes only specialist IDs created
    // by the preceding run, never an unowned address collision.
    if (get_option('yoona_test_growth_ids')) {
        $prior = get_option(Yoona_SEO_Growth_Pages::STATE_OPTION, array());
        foreach (isset($prior['entries']) ? $prior['entries'] : array() as $entry) {
            $candidate = get_post((int) $entry['id']);
            if ($candidate && in_array('/' . $candidate->post_name . '/', $child_paths, true) && get_post_meta($candidate->ID, '_yoona_growth_owned', true) === '3.4.0') wp_delete_post($candidate->ID, true);
        }
        delete_option(Yoona_SEO_Growth_Pages::STATE_OPTION);
        delete_option(Yoona_SEO_Growth_Pages::LOCK_OPTION);
        delete_option('yoona_test_growth_created');
    }
    $ids = array();
    foreach ($existing_paths as $path) {
        $existing = get_page_by_path(trim($path, '/'), OBJECT, 'page');
        $title = isset($guides[$path]['title']) ? $guides[$path]['title'] : trim($path, '/');
        $hero_image = Yoona_SEO_Design::data()['offer_heroes'][$path]['image'];
        $images = array_column(Yoona_SEO_Design::data()['services'], 'image');
        $image = $images[0] === $hero_image ? $images[1] : $images[0];
        $photo_module = '[et_pb_image src="' . esc_url($image) . '"][/et_pb_image]';
        $content = '[et_pb_section][et_pb_row][et_pb_column][et_pb_text]<h1>Old synthetic service heading</h1><p>GROWTH_OLD_COPY_SENTINEL</p>[/et_pb_text]' . $photo_module . $photo_module . '[et_pb_image src="https://yoona.pl/wp-content/uploads/2023/09/agencja-reklamowa-olsztyn.png"][/et_pb_image][et_pb_contact_form]<p>GROWTH_PRESERVED_FORM</p>[/et_pb_contact_form][/et_pb_column][/et_pb_row][/et_pb_section]';
        $id = wp_insert_post(wp_slash(array('ID' => $existing ? $existing->ID : 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => trim($path, '/'), 'post_title' => $title, 'post_content' => $content)), true);
        $check(!is_wp_error($id), 'Existing service fixture is public: ' . $path);
        update_post_meta($id, '_et_pb_use_builder', 'on');
        update_post_meta($id, 'rank_math_title', 'GROWTH_NATIVE ' . $title);
        update_post_meta($id, 'rank_math_description', 'Natywny opis SEO zachowany przy prezentacji przewodnika.');
        delete_post_meta($id, '_yoona_seo_title');
        delete_post_meta($id, '_yoona_seo_description');
        $ids[$path] = $id;
    }
    foreach (array('kontakt' => 'Kontakt', 'pelna-oferta' => 'Pełna oferta', 'growth-unlisted' => 'Unlisted source') as $slug => $title) {
        $existing = get_page_by_path($slug, OBJECT, 'page');
        $id = wp_insert_post(wp_slash(array('ID' => $existing ? $existing->ID : 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => '<p>GROWTH_PRESERVED_UNLISTED_SOURCE</p>')), true);
        $check(!is_wp_error($id), 'Supplementary public fixture created: ' . $slug);
        update_post_meta($id, 'rank_math_title', 'GROWTH_NATIVE ' . $title);
        update_post_meta($id, 'rank_math_description', 'Natywny opis SEO zachowany przy prezentacji przewodnika.');
        delete_post_meta($id, '_yoona_seo_title');
        delete_post_meta($id, '_yoona_seo_description');
        $ids['/' . $slug . '/'] = $id;
    }
    foreach (array('growth-project-color' => '/zmiana-koloru-auta-ostroda/', 'growth-project-pylons' => '/pylony-ergo-arena/', 'growth-project-awning' => '/markiza-irjados/') as $slug => $source_path) {
        $definition = Yoona_SEO_Project_Guides::data()[$source_path];
        $existing = get_page_by_path($slug, OBJECT, 'post');
        $source = '[growth_project_guide path="' . $source_path . '"]<figure><img src="' . esc_url(Yoona_SEO_Design::data()['services'][0]['image']) . '" alt="Synthetic fixture using existing source photograph"></figure><p>GROWTH_PRESERVED_PROJECT_SOURCE</p>';
        $id = wp_insert_post(wp_slash(array('ID' => $existing ? $existing->ID : 0, 'post_type' => 'post', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $definition['title'], 'post_content' => $source)), true);
        $check(!is_wp_error($id), 'A public project component fixture contains only reviewed JSON facts and synthetic original media: ' . $slug);
        // Match the production project template's full-width component shell;
        // content.php otherwise nests a second constrained content container.
        update_post_meta($id, '_et_pb_use_builder', 'on');
        update_post_meta($id, 'rank_math_title', 'GROWTH_NATIVE ' . $definition['title']);
        update_post_meta($id, 'rank_math_description', 'Natywny opis SEO zachowany przy prezentacji przewodnika.');
        $ids['/' . $slug . '/'] = $id;
    }
    $mu = <<<'MU'
<?php
/** Synthetic media/form modules in the disposable test site; not licensed Divi. */
function et_pb_is_pagebuilder_used($id) { return get_post_meta($id, '_et_pb_use_builder', true) === 'on'; }
add_filter('the_content', function ($content) {
    if (et_pb_is_pagebuilder_used(get_the_ID())) { remove_filter('the_content', 'wpautop'); remove_filter('the_content', 'shortcode_unautop'); }
    return $content;
}, 8);
foreach (array('et_pb_section','et_pb_row','et_pb_column','et_pb_text','et_pb_image','et_pb_contact_form') as $tag) {
    add_shortcode($tag, function ($attributes, $content = '', $tag = '') {
        if (!isset($GLOBALS['growth_module_counters'][$tag])) $GLOBALS['growth_module_counters'][$tag] = 0;
        ++$GLOBALS['growth_module_counters'][$tag];
        if ($tag === 'et_pb_image') return '<div class="et_pb_module et_pb_image growth-original-image"><img src="' . esc_url($attributes['src']) . '" alt="Original service photograph"></div>';
        if ($tag === 'et_pb_contact_form') return '<div class="et_pb_module et_pb_contact_form_container growth-original-form"><form><label>GROWTH_PRESERVED_FORM<input name="growth-test" autocomplete="off"></label></form></div>';
        return '<div class="growth-source-module ' . esc_attr($tag) . '">' . do_shortcode($content) . '</div>';
    });
}
add_shortcode('growth_project_guide', function ($attributes) {
    $definitions = Yoona_SEO_Project_Guides::data();
    if (empty($attributes['path']) || !isset($definitions[$attributes['path']])) return '';
    ob_start(); Yoona_SEO_Project_Guides::render($definitions[$attributes['path']]); return ob_get_clean();
});
MU;
    $check(file_put_contents(WPMU_PLUGIN_DIR . '/zz-yoona-growth-fixture.php', $mu) !== false, 'A synthetic original-media and contact-form renderer is installed only in the disposable WordPress.');
    update_option('yoona_test_growth_ids', $ids, false);
    update_option('yoona_test_growth_children', $child_paths, false);
    wp_set_current_user(1);
    WP_CLI::success($count . ' growth fixture assertions passed.');
    return;
}
if ($mode !== 'guards') WP_CLI::error('Use prepare|guards|snapshot|verify.');
$ids = get_option('yoona_test_growth_ids');
foreach ($existing_paths as $path) {
    $query_page($ids[$path]);
    $context = Yoona_SEO_Service_Guides::context($ids[$path]);
    $check(is_array($context) && $context['path'] === $path, 'A published queried service resolves its reviewed guide: ' . $path);
}
$query_page($ids['/banery/']);
$GLOBALS['growth_module_counters'] = array();
$media = Yoona_SEO_Service_Guides::legacy_media($ids['/banery/'], Yoona_SEO_Service_Guides::context($ids['/banery/']));
$check(count($media) === 2 && strpos(implode('', $media), 'growth-original-image') !== false && strpos(implode('', $media), 'growth-original-form') !== false, 'Legacy media preserve one distinct original photograph and the original contact form.');
$check(strpos(implode('', $media), 'agencja-reklamowa-olsztyn') === false && strpos(implode('', $media), 'GROWTH_OLD_COPY_SENTINEL') === false, 'The retired knob photograph and replaced old text never enter preserved media.');
$check($GLOBALS['growth_module_counters']['et_pb_image'] === 3 && $GLOBALS['growth_module_counters']['et_pb_text'] === 1 && $GLOBALS['growth_module_counters']['et_pb_contact_form'] === 1, 'Every original image, text and form callback still runs before de-duplicating visible media.');
$probe = $ids['/banery/'];
$probe_before = get_post($probe, ARRAY_A);
foreach (array(array('post_status' => 'draft'), array('post_status' => 'private'), array('post_password' => 'local-growth-secret')) as $changes) {
    $wpdb->update($wpdb->posts, $changes, array('ID' => $probe)); clean_post_cache($probe); $query_page($probe);
    $check(!Yoona_SEO_Service_Guides::context($probe), 'A nonpublic or password-protected page never receives a public guide.');
    $wpdb->update($wpdb->posts, array('post_status' => $probe_before['post_status'], 'post_password' => $probe_before['post_password']), array('ID' => $probe)); clean_post_cache($probe);
}
$query_page($ids['/growth-unlisted/']);
$check(!Yoona_SEO_Service_Guides::context($ids['/growth-unlisted/']), 'An unreviewed service slug falls back to original content.');
wp_set_current_user(1);
$read_baseline = $snapshot();
Yoona_SEO_Service_Guides::data();
ob_start(); Yoona_SEO_Growth_Pages::render_panel(); ob_end_clean();
$check($snapshot() === $read_baseline, 'Reading guide data and its admin panel creates no pages and changes no metadata.');
$check(!get_option(Yoona_SEO_Growth_Pages::STATE_OPTION), 'No new service pages were created by activation or initial browsing.');
wp_set_current_user(get_user_by('login', 'yoona_design_reader')->ID);
$check(is_wp_error(Yoona_SEO_Growth_Pages::install()) && is_wp_error(Yoona_SEO_Growth_Pages::rollback()), 'A subscriber cannot install or roll back service pages.');
wp_set_current_user(1);
$admin = wp_get_current_user(); $admin->add_cap('publish_pages', false);
$check(is_wp_error(Yoona_SEO_Growth_Pages::install()), 'manage_options without publish_pages is insufficient for page creation.');
$admin->remove_cap('publish_pages'); wp_set_current_user(1);
update_option(Yoona_SEO_Growth_Pages::LOCK_OPTION, 'local-in-progress-lock', false);
$check(is_wp_error(Yoona_SEO_Growth_Pages::install()) && is_wp_error(Yoona_SEO_Growth_Pages::rollback()), 'An existing operation lock prevents overlapping install and rollback.');
$check(get_option(Yoona_SEO_Growth_Pages::LOCK_OPTION) === 'local-in-progress-lock', 'A competing operation never steals or deletes an existing lock.');
delete_option(Yoona_SEO_Growth_Pages::LOCK_OPTION);
update_option(Yoona_SEO_Growth_Pages::STATE_OPTION, 'local-malformed-history', false);
$check(is_wp_error(Yoona_SEO_Growth_Pages::install()) && is_wp_error(Yoona_SEO_Growth_Pages::rollback()), 'Malformed stored history blocks both operations rather than silently replacing the ledger.');
$check(get_option(Yoona_SEO_Growth_Pages::STATE_OPTION) === 'local-malformed-history', 'A malformed history is preserved for inspection.');
delete_option(Yoona_SEO_Growth_Pages::STATE_OPTION);
$collision_path = $child_paths[0];
$collision = wp_insert_post(wp_slash(array('post_type' => 'page', 'post_status' => 'draft', 'post_name' => trim($collision_path, '/'), 'post_title' => 'Existing specialist collision', 'post_content' => 'GROWTH_EXISTING_COLLISION_BODY')), true);
add_post_meta($collision, 'growth_collision_meta', 'first'); add_post_meta($collision, 'growth_collision_meta', 'second');
$collision_before = array(get_post($collision, ARRAY_A), get_post_meta($collision));
$old_rows = array(); foreach ($ids as $id) $old_rows[$id] = array(get_post($id, ARRAY_A), get_post_meta($id));
$installed = Yoona_SEO_Growth_Pages::install();
$check(!is_wp_error($installed) && count($installed['created']) === 5 && count($installed['skipped']) === 1, 'The explicit install creates five missing specialist pages and skips the existing collision.');
$created = $installed['created'];
$check(array(get_post($collision, ARRAY_A), get_post_meta($collision)) === $collision_before, 'An existing draft with duplicate metadata is never overwritten or adopted.');
foreach ($old_rows as $id => $row) $check(array(get_post($id, ARRAY_A), get_post_meta($id)) === $row, 'Specialist creation preserves an existing page and all native SEO metadata: ' . $id);
$second = Yoona_SEO_Growth_Pages::install();
$check(!is_wp_error($second) && count($second['created']) === 0 && count($second['skipped']) === 6, 'A repeated install creates no duplicate pages.');
$edited = (int) $created[0];
$wpdb->update($wpdb->posts, array('post_content' => get_post_field('post_content', $edited) . '<p>Later editor change.</p>'), array('ID' => $edited)); clean_post_cache($edited);
$query_page($edited);
$check(!Yoona_SEO_Service_Guides::context($edited), 'A specialist page edited after installation displays its later stored copy instead of replacing it with the bundled guide.');
$meta_edited = (int) $created[1];
add_post_meta($meta_edited, 'growth_later_plugin_meta', 'preserve this later plugin metadata');
$rolled = Yoona_SEO_Growth_Pages::rollback();
$check(!is_wp_error($rolled) && count($rolled['trashed']) === 3 && count($rolled['conflicts']) === 2, 'Rollback trashes only three unchanged owned pages and preserves later content and metadata changes.');
$check(get_post_status($edited) === 'publish' && strpos(get_post_field('post_content', $edited), 'Later editor change.') !== false, 'A changed owned page remains published with the later edit intact.');
$check(get_post_status($meta_edited) === 'publish' && get_post_meta($meta_edited, 'growth_later_plugin_meta', true) === 'preserve this later plugin metadata', 'A page with later plugin metadata remains published and keeps that metadata.');
$check(array(get_post($collision, ARRAY_A), get_post_meta($collision)) === $collision_before, 'Rollback leaves the unowned collision and its duplicate metadata intact.');
foreach ($created as $id) wp_delete_post((int) $id, true);
wp_delete_post($collision, true);
delete_option(Yoona_SEO_Growth_Pages::STATE_OPTION);
delete_option(Yoona_SEO_Growth_Pages::LOCK_OPTION);
$all = Yoona_SEO_Growth_Pages::install();
$check(!is_wp_error($all) && count($all['created']) === 6, 'A clean explicit install publishes all six reviewed specialist pages for frontend checks.');
$title_probe = (int) $all['created'][0]; $saved_title = get_post_field('post_title', $title_probe);
$wpdb->update($wpdb->posts, array('post_title' => $saved_title . ' — manual title edit'), array('ID' => $title_probe)); clean_post_cache($title_probe); $query_page($title_probe);
$check(!Yoona_SEO_Service_Guides::context($title_probe), 'An owned specialist page with a later title edit falls back to its stored copy.');
$wpdb->update($wpdb->posts, array('post_title' => $saved_title), array('ID' => $title_probe)); clean_post_cache($title_probe); $query_page($title_probe);
$check(is_array(Yoona_SEO_Service_Guides::context($title_probe)), 'Restoring the exact original specialist title resolves the reviewed guide again.');
foreach ($all['created'] as $id) {
    $record = get_post($id);
    $check($record->post_status === 'publish' && $record->post_type === 'page' && get_post_meta($id, '_yoona_growth_owned', true) === '3.4.0', 'A created specialist page has the expected type, status and ownership marker.');
    $check(get_post_meta($id, '_yoona_growth_content_sha256', true) === hash('sha256', $record->post_content), 'A created specialist page records the exact stored fallback-copy fingerprint.');
    $native_title = get_post_meta($id, 'rank_math_title', true);
    $check(strpos($native_title, 'Olsztyn') !== false && strpos($native_title, 'Ostróda') !== false, 'A created specialist page has native local Rank Math metadata for both cities.');
}
$cards = Yoona_SEO_Design::offer_cards();
$check(count($cards) === 47 && count(array_unique(array_column($cards, 'url'))) === 47, 'The public catalogue contains exactly 47 distinct existing and specialist services.');
update_option('yoona_test_growth_created', $all['created'], false);
WP_CLI::success($count . ' growth authorization, collision, rollback and context assertions passed.');
