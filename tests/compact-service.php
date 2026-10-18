<?php
/** Public synthetic known-hash offers. Never accepts or installs client exports. */
if (!defined('WP_CLI') || !WP_CLI || !isset($args[0])) exit(1);
$mode = $args[0];
global $wpdb;
$ids = get_option('yoona_gallery_fixture_ids');
if (!is_array($ids) || count($ids) !== 41 || !preg_match('~\Ahttp://127\.0\.0\.1:\d+/?\z~', get_option('siteurl'))) {
 WP_CLI::error('Compact probes require the disposable loopback gallery fixture site.');
}
$snapshot = static function () use ($wpdb) {
 return array($wpdb->get_results("SELECT * FROM {$wpdb->posts} ORDER BY ID", ARRAY_A), $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A));
};
if ($mode === 'snapshot') {
 update_option('yoona_compact_snapshot', $snapshot(), false);
 WP_CLI::success('Compact: all post and metadata rows captured after frontend warmup.'); return;
}
if ($mode === 'verify') {
 if ($snapshot() !== get_option('yoona_compact_snapshot')) WP_CLI::error('Compact frontend changed a post or metadata row.');
 WP_CLI::success('Compact: every post and metadata row remained byte-identical.'); return;
}
if ($mode !== 'prepare') WP_CLI::error('Unknown compact test mode.');
if (!is_readable(WPMU_PLUGIN_DIR . '/zz-gallery-repair-fixture.php')) WP_CLI::error('Prepare the standard gallery callbacks first.');
$mu = <<<'MU'
<?php
/** TEST ONLY: public copy and a synthetic header, no production hash changes. */
function yoona_compact_fixture_sections($path, $guide) {
 $hero = '[et_pb_section module_class="compact-old-hero"][et_pb_row][et_pb_column][et_pb_text]<h1>COMPACT SYNTHETIC ORIGINAL HERO</h1><p>Public fixture header.</p>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
 $append = in_array($path, array('/flagi/', '/totemy/'), true);
 $heading = $path === '/flagi/' ? 'h1' : 'h2';
 $intro = '[et_pb_section module_class="compact-old-intro"][et_pb_row][et_pb_column][et_pb_text]<' . $heading . '>' . esc_html($guide['title']) . '</' . $heading . '><p>' . esc_html($guide['overview']) . '</p>[/et_pb_text]';
 if ($append) $intro .= '[et_pb_image src="https://example.invalid/compact-preserved-intro.jpg"][/et_pb_image]';
 $intro .= '[/et_pb_column][/et_pb_row][/et_pb_section]';
 return array($hero, $intro);
}
add_action('plugins_loaded', static function () {
 if (!class_exists('Yoona_SEO_Design') || !class_exists('Yoona_SEO_Service_Guides')) return;
 $data = Yoona_SEO_Design::data();
 $guides = Yoona_SEO_Service_Guides::data();
 foreach ($data['offer_heroes'] as $path => &$definition) {
  list($hero, $intro) = yoona_compact_fixture_sections($path, $guides[$path]);
  $definition['section_sha256'] = array(hash('sha256', $hero));
  $definition['service_intro_sha256'] = array(hash('sha256', $intro));
 }
 unset($definition);
 $property = new ReflectionProperty('Yoona_SEO_Design', 'data');
 $property->setAccessible(true); $property->setValue(null, $data);
}, 100);
MU;
$file = WPMU_PLUGIN_DIR . '/zz-compact-service-fixture.php';
if (false === file_put_contents($file, $mu)) WP_CLI::error('Cannot write the test-only runtime hash fixture.');
require_once $file;
// This CLI request passed plugins_loaded before the MU was installed. The next
// request applies its ReflectionProperty override; preparing source needs only
// the same deterministic public helper, never a modified plugin JSON manifest.
$guides = Yoona_SEO_Service_Guides::data();
foreach ($ids as $path => $id) {
 $post = get_post((int)$id);
 if (!$post || $post->post_type !== 'page' || $post->post_name !== trim($path, '/') || !isset($guides[$path])) WP_CLI::error('Unexpected synthetic fixture identity.');
 $tail_start = strpos($post->post_content, '[et_pb_section module_class="repair-original-tail repair-gallery-section"]');
 if ($tail_start === false) WP_CLI::error('The original synthetic gallery / bottom tail is unavailable.');
 list($hero, $intro) = yoona_compact_fixture_sections($path, $guides[$path]);
 $body = $hero . $intro . substr($post->post_content, $tail_start);
 $result = wp_update_post(wp_slash(array('ID'=>(int)$id, 'post_content'=>$body)), true);
 if (is_wp_error($result)) WP_CLI::error('Cannot prepare public compact fixture.');
}
update_option('yoona_compact_fixture_ready', 'public-synthetic-41-v1', false);
WP_CLI::success('41 public synthetic known-header / known-introduction sources prepared; complete original gallery and lower-module tails retained. The two append offers exercise hero p and h1 semantics. Plugin files and production hashes remain unchanged.');
