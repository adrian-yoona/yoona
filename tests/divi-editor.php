<?php
/** Disposable WordPress tests. No licensed Divi and no private customer export. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || empty( $args[0] ) ) exit( 1 );
if ( ! preg_match( '~\Ahttp://127\.0\.0\.1:\d+/?\z~', get_option( 'siteurl' ) ) || count( (array) get_option( 'yoona_gallery_fixture_ids' ) ) !== 41 ) WP_CLI::error( 'Divi editor probes require the disposable loopback gallery site.' );
if ( ! class_exists( 'Yoona_SEO_Divi_Editor' ) ) require_once WP_PLUGIN_DIR . '/yoona-seo/includes/class-yoona-seo-divi-editor.php';
$mode = $args[0];
$checks = 0;
$check = static function ( $value, $label ) use ( &$checks ) { if ( ! $value ) throw new RuntimeException( 'FAILED: ' . $label ); ++$checks; };
$raw = static function ( $id ) { return get_post_field( 'post_content', $id, 'raw' ); };
$meta = static function ( $id ) { $result = array(); foreach ( array( '_et_pb_use_builder', '_et_pb_page_layout', Yoona_SEO_Divi_Editor::META ) as $key ) $result[ $key ] = get_post_meta( $id, $key, false ); return $result; };
$module = static function ( $tag, $content = '', $attributes = '' ) { return '[' . $tag . $attributes . ']' . $content . '[/' . $tag . ']'; };
$section = static function ( $content ) use ( $module ) { return $module( 'et_pb_section', $module( 'et_pb_row', $module( 'et_pb_column', $content, ' type="4_4"' ) ) ); };
global $wpdb;
$snapshot = static function () use ( $wpdb ) { return array( $wpdb->get_results( "SELECT * FROM {$wpdb->posts} ORDER BY ID", ARRAY_A ), $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A ) ); };

if ( 'lock' === $mode ) {
 $id = absint( $args[1] ); $start = (float) $args[2];
 // Keep cached-absence workers overlapping if an unsafe add_option() upsert
 // is reintroduced; the direct INSERT IGNORE implementation avoids this hook.
 add_filter( 'default_option_' . Yoona_SEO_Divi_Editor::LOCK . $id, static function ( $default ) { usleep( 400000 ); return $default; } );
 while ( microtime( true ) < $start ) usleep( 20000 );
 $acquire = new ReflectionMethod( 'Yoona_SEO_Divi_Editor', 'acquire_lock' ); $acquire->setAccessible( true );
 $release = new ReflectionMethod( 'Yoona_SEO_Divi_Editor', 'release_lock' ); $release->setAccessible( true );
 $token = $acquire->invoke( null, $id );
 if ( is_wp_error( $token ) ) { echo 'BLOCKED'; return; }
 echo 'WINNER';
 try { usleep( 1400000 ); } finally { $release->invoke( null, $id, $token ); }
 return;
}

$admin = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
wp_set_current_user( $admin[0] );
$gallery_ids = get_option( 'yoona_gallery_fixture_ids' );
$banery = get_page_by_path( 'banery', OBJECT, 'page' );
if ( ! $banery ) WP_CLI::error( 'Missing public banery fixture.' );

if ( 'readonly' === $mode ) {
 $before = $snapshot();
 $design_property = new ReflectionProperty( 'Yoona_SEO_Design', 'data' ); $design_property->setAccessible( true );
 $old_design = Yoona_SEO_Design::data();
 $counter = 0;
 $count = static function ( $pre ) use ( &$counter ) { ++$counter; return $pre; };
 add_filter( 'pre_do_shortcode_tag', $count, 1 );
 try {
  $hero = $section( $module( 'et_pb_text', '<h1>Dawny nagłówek</h1>' ) );
  $intro = $section( $module( 'et_pb_text', '<h2>Dawny wstęp</h2><p>Tekst zastępowany.</p>' ) );
  $tail = $section( $module( 'et_pb_gallery', '', ' gallery_ids="101,102,103"' ) . $module( 'et_pb_contact_form', '', ' title="Formularz zachowany"' ) . $module( 'et_pb_code', '[trustindex no-registration=public-test]' ) );
  $data = $old_design;
  $data['offer_heroes']['/banery/']['section_sha256'] = array( hash( 'sha256', $hero ) );
  $data['offer_heroes']['/banery/']['service_intro_sha256'] = array( hash( 'sha256', $intro ) );
  $data['offer_heroes']['/banery/']['service_intro_mode'] = 'replace';
  $design_property->setValue( null, $data );
  $post = clone $banery; $post->post_content = $hero . $intro . $tail;
  $generated = Yoona_SEO_Divi_Editor::materialize( $post );
  $check( is_string( $generated ), 'service returns native source' );
  $check( 1 === substr_count( $generated, $tail ), 'whole original gallery/form/code section retained once' );
  $check( false === strpos( $generated, $hero ) && false === strpos( $generated, $intro ), 'only exact reviewed hero and intro replaced' );
  foreach ( array( '[et_pb_section ', '[et_pb_row ', '[et_pb_column ', '[et_pb_text ', '[et_pb_image ', '[et_pb_button ', '[et_pb_accordion ', '[et_pb_accordion_item ' ) as $tag ) $check( false !== strpos( $generated, $tag ), 'actual native module ' . $tag );
  foreach ( array( 'applications', 'materials', 'process', 'pricing', 'quote' ) as $field ) foreach ( Yoona_SEO_Service_Guides::data()['/banery/'][ $field ] as $copy ) $check( false !== strpos( html_entity_decode( wp_strip_all_tags( $generated ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), $copy ), 'all guide copy retained: ' . $field );
  $check( 1 === preg_match_all( '/<h1(?:\s|>)/i', $generated ), 'single H1 for service' );
  $edited = clone $post; $edited->post_content = '<!-- handwritten -->' . $post->post_content;
  $check( false !== strpos( Yoona_SEO_Divi_Editor::materialize( $edited ), $edited->post_content ), 'unknown manual hero kept complete' );
  $post->post_title = 'Oferta [et_pb_code]nie uruchamiaj';
  $check( false !== strpos( Yoona_SEO_Divi_Editor::materialize( $post ), '&#91;et_pb_code&#93;' ), 'derived title shortcode characters escaped' );
  $front = get_post( get_option( 'page_on_front' ) );
  $check( $front instanceof WP_Post, 'front page exists' );
  $front = clone $front; $front->post_content = $tail;
  $home = Yoona_SEO_Divi_Editor::materialize( $front );
  $check( is_string( $home ) && false !== strpos( $home, '[et_pb_fullwidth_slider ' ), 'homepage is real native slider' );
  $check( 5 === substr_count( $home, '[et_pb_slide ' ), 'homepage contains all five editable slides' );
  $check( 1 === substr_count( $home, $tail ), 'homepage retains original functional section with parents' );
  $front->post_content = '<form><input name="unexpected"></form>';
  $check( is_wp_error( Yoona_SEO_Divi_Editor::materialize( $front ) ), 'ungrouped custom form rejected before write' );
  $catalog = get_page_by_path( 'pelna-oferta', OBJECT, 'page' );
  $check( $catalog instanceof WP_Post, 'catalog exists' );
  $catalog = clone $catalog; $catalog->post_content = $tail;
  $catalog_content = Yoona_SEO_Divi_Editor::materialize( $catalog );
  $check( 47 === substr_count( $catalog_content, '[et_pb_image ' ), 'all 47 catalog image modules materialized' );
  $check( false !== strpos( $catalog_content, 'type="1_6"' ) && 8 === substr_count( $catalog_content, 'yn-divi-collection-row yn-divi-offer-catalog' ), 'catalog native six-column rows' );
  $check( 1 === substr_count( $catalog_content, $tail ), 'catalog retains complete functional source section' );
  $index = get_post( get_option( 'page_for_posts' ) );
  $native_index = Yoona_SEO_Divi_Editor::materialize( $index );
  $check( false !== strpos( $native_index, '[et_pb_code ' ) && 1 === substr_count( $native_index, '[yoona_realizacje]' ), 'index is native movable code module with dynamic collection' );
  $check( 0 === $counter, 'preparing all layouts executes zero shortcode callbacks' );
  $old_get = $_GET;
  try {
   foreach ( array( 'et_fb', 'et_bfb', 'et_pb_preview', 'et_pb_preview_nonce', 'et_pb_layout' ) as $key ) { $_GET = array( $key => '' ); $check( Yoona_SEO_Divi_Editor::builder_request(), 'builder route excluded: ' . $key ); }
  } finally { $_GET = $old_get; }
  $old_query = isset( $GLOBALS['wp_query'] ) ? $GLOBALS['wp_query'] : null;
  $GLOBALS['wp_query'] = new WP_Query();
  $query = new WP_Query( array( Yoona_SEO_Project_Gallery::QUERY_FLAG => 'native', 'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 24, 'paged' => 1, 'ignore_sticky_posts' => true, 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'no_found_rows' => false ) );
  $gallery = Yoona_SEO_Divi_Editor::gallery_shortcode();
  $check( 24 === substr_count( $gallery, 'class="yn-project-gallery-card"' ), 'native dynamic index returns 24 real photographs' );
  $check( false !== strpos( $gallery, 'Następne' ) && $query->max_num_pages >= 2, 'native dynamic index has counted pagination' );
  foreach ( $query->posts as $record ) $check( false !== strpos( $gallery, 'data-project-id="' . $record->ID . '"' ), 'native index exact query order/ID' );
  $GLOBALS['wp_query']->set( 'paged', 2 );
  $gallery2 = Yoona_SEO_Divi_Editor::gallery_shortcode();
  $check( 24 === substr_count( $gallery2, 'class="yn-project-gallery-card"' ), 'native secondary query page 2 has 24 cards' );
  preg_match_all( '/data-project-id="(\d+)"/', $gallery, $one ); preg_match_all( '/data-project-id="(\d+)"/', $gallery2, $two );
  $check( ! array_intersect( $one[1], $two[1] ), 'page 2 does not repeat projects' );
  $GLOBALS['wp_query'] = $old_query;
 } finally {
  remove_filter( 'pre_do_shortcode_tag', $count, 1 );
  $design_property->setValue( null, $old_design );
 }
 $check( $before === $snapshot(), 'all post and metadata rows unchanged by materialization and dynamic gallery' );
 WP_CLI::success( "Divi readonly: {$checks} assertions passed; native source only, no licensed Divi UI." );
 return;
}

if ( 'mutation' !== $mode ) WP_CLI::error( 'Use readonly, mutation or lock.' );
$created = array();
$terms = array();
$original_options = array( 'show_on_front' => get_option( 'show_on_front' ), 'page_on_front' => get_option( 'page_on_front' ), 'page_for_posts' => get_option( 'page_for_posts' ) );
$previous_query = isset( $GLOBALS['wp_query'] ) ? $GLOBALS['wp_query'] : null;
$previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
$make = static function ( $body = null ) use ( &$created, $section, $module ) {
 if ( null === $body ) $body = $section( $module( 'et_pb_text', '<h1>Oryginalna strona</h1><p>Źródło [ ] &amp; cudzysłów " oraz \\.</p>' ) . $module( 'et_pb_gallery', '', ' gallery_ids="101,102"' ) . $module( 'et_pb_contact_form', '', ' title="Formularz"' ) );
 $id = wp_insert_post( wp_slash( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'yoona-divi-editor-' . wp_generate_uuid4(), 'post_title' => 'Disposable native Divi probe', 'post_content' => $body ) ), true );
 if ( is_wp_error( $id ) ) throw new RuntimeException( 'Cannot create disposable page.' );
 $created[] = $id;
 add_post_meta( $id, '_et_pb_use_builder', 'off' ); add_post_meta( $id, '_et_pb_use_builder', 'legacy-duplicate' );
 add_post_meta( $id, '_et_pb_page_layout', 'left_sidebar' ); add_post_meta( $id, Yoona_SEO_Divi_Editor::META, '0' ); add_post_meta( $id, Yoona_SEO_Divi_Editor::META, 'manual-marker' );
 add_post_meta( $id, 'rank_math_title', 'UNCHANGED NATIVE SEO TITLE' );
 return $id;
};
try {
 $id = $make(); $original = $raw( $id ); $original_meta = $meta( $id );
 $fingerprint = Yoona_SEO_Divi_Editor::fingerprint( $id );
 wp_update_post( array( 'ID' => $id, 'post_content' => $original . '<p>Nowa ręczna zmiana.</p>' ) );
 $check( is_wp_error( Yoona_SEO_Divi_Editor::save( $id, $fingerprint ) ), 'stale source refuses conversion' );
 $check( ! metadata_exists( 'post', $id, Yoona_SEO_Divi_Editor::BACKUP ), 'stale source creates no backup/write' );
 wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $original ) ) );
 $result = Yoona_SEO_Divi_Editor::save( $id, Yoona_SEO_Divi_Editor::fingerprint( $id ) );
 $check( $id === $result && Yoona_SEO_Divi_Editor::authoritative( $id ), 'explicit conversion succeeds' );
 $native = $raw( $id );
 $check( 1 === substr_count( $native, $original ), 'original complete native tree retained once' );
 $check( array( 'on' ) === get_post_meta( $id, '_et_pb_use_builder', false ) && array( 'et_full_width_page' ) === get_post_meta( $id, '_et_pb_page_layout', false ), 'builder and full-width settings persisted exactly' );
 $check( get_post_meta( $id, Yoona_SEO_Divi_Editor::BACKUP, true )['content'] === $original, 'verified original independent backup' );
 $check( 'UNCHANGED NATIVE SEO TITLE' === get_post_meta( $id, 'rank_math_title', true ), 'Rank Math title untouched' );
 $check( is_wp_error( Yoona_SEO_Divi_Editor::save( $id, Yoona_SEO_Divi_Editor::fingerprint( $id ) ) ), 'second conversion cannot overwrite edits' );
 $manual = $native . $section( $module( 'et_pb_text', '<h2>Ręczna zmiana Divi</h2><p>Ta wersja ma przetrwać cofnięcie.</p>' ) );
 wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $manual ) ) );
 $GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $id ) ); $GLOBALS['post'] = get_post( $id );
 $check( 'divi-content.php' === basename( Yoona_SEO_Design::template( 'theme.php' ) ), 'real singular query chooses native authoritative wrapper' );
 $check( Yoona_SEO_Divi_Editor::materialize( $id ) === $manual, 'manual Divi edit stays authoritative' );
 $GLOBALS['wp_query'] = $previous_query; $GLOBALS['post'] = $previous_post;
 add_filter( 'wp_revisions_to_keep', '__return_zero' );
 try {
  $check( $id === Yoona_SEO_Divi_Editor::save( $id, Yoona_SEO_Divi_Editor::fingerprint( $id ), 'restore' ), 'restore succeeds with revisions disabled' );
  $check( $raw( $id ) === $original && $meta( $id ) === $original_meta, 'restore exact bytes and duplicate/zero metadata rows' );
  $recovery = get_post_meta( $id, Yoona_SEO_Divi_Editor::RECOVERY, false );
  $check( end( $recovery )['content'] === $manual, 'manual changes saved independently of revisions' );
  $check( $id === Yoona_SEO_Divi_Editor::save( $id, Yoona_SEO_Divi_Editor::fingerprint( $id ), 'recover' ), 'independent recovery succeeds' );
  $check( $raw( $id ) === $manual && Yoona_SEO_Divi_Editor::authoritative( $id ), 'manual native layout recovered exactly' );
 } finally { remove_filter( 'wp_revisions_to_keep', '__return_zero' ); }

 // An actual term-ID/post-ID collision must remain an archive route.
 $collision_slug = 'yoona-divi-collision-' . $id;
 if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM {$wpdb->terms} WHERE term_id = %d", $id ) ) ) {
  $wpdb->insert( $wpdb->terms, array( 'term_id' => $id, 'name' => 'Disposable Divi collision', 'slug' => $collision_slug, 'term_group' => 0 ) );
  $wpdb->insert( $wpdb->term_taxonomy, array( 'term_id' => $id, 'taxonomy' => 'category', 'description' => '', 'parent' => 0, 'count' => 0 ) );
  $terms[] = $id; clean_term_cache( $id, 'category' );
  $GLOBALS['wp_query'] = new WP_Query( array( 'category_name' => $collision_slug ) );
  $check( is_category() && (int) get_queried_object_id() === $id, 'real WordPress taxonomy query collides with converted page ID' );
  $check( 'archive.php' === basename( Yoona_SEO_Design::template( 'theme.php' ) ), 'archive with same numeric ID does not use converted page' );
  $GLOBALS['wp_query'] = $previous_query;
 } else throw new RuntimeException( 'Disposable term ID collision target already used.' );

 $id2 = $make(); $source2 = $raw( $id2 ); $meta2 = $meta( $id2 );
 $deny_layout = static function ( $pre, $object_id, $key, $value ) use ( $id2 ) { return (int) $object_id === $id2 && '_et_pb_page_layout' === $key && 'et_full_width_page' === $value ? false : $pre; };
 add_filter( 'add_post_metadata', $deny_layout, 10, 4 );
 try { $result = Yoona_SEO_Divi_Editor::save( $id2, Yoona_SEO_Divi_Editor::fingerprint( $id2 ) ); }
 finally { remove_filter( 'add_post_metadata', $deny_layout, 10 ); }
 $check( is_wp_error( $result ) && $raw( $id2 ) === $source2 && $meta( $id2 ) === $meta2, 'blocked layout write verified full rollback' );
 $check( metadata_exists( 'post', $id2, Yoona_SEO_Divi_Editor::BACKUP ), 'failed conversion retains original backup' );

 $id3 = $make(); $source3 = $raw( $id3 ); $meta3 = $meta( $id3 );
 $later_key = static function ( $mid, $object_id, $key, $value ) use ( $id3 ) { if ( (int) $object_id === $id3 && Yoona_SEO_Divi_Editor::META === $key && '1' === $value ) update_post_meta( $id3, '_et_pb_use_builder', 'off' ); };
 add_action( 'added_post_meta', $later_key, 10, 4 );
 try { $result = Yoona_SEO_Divi_Editor::save( $id3, Yoona_SEO_Divi_Editor::fingerprint( $id3 ) ); }
 finally { remove_action( 'added_post_meta', $later_key, 10 ); }
 $check( is_wp_error( $result ) && $raw( $id3 ) === $source3 && $meta( $id3 ) === $meta3, 'final verifier catches later-key mutation of earlier metadata' );

 $id4 = $make(); $source4 = $raw( $id4 );
 $damage = static function ( $data, $postarr ) use ( $id4 ) { if ( ! empty( $postarr['ID'] ) && (int) $postarr['ID'] === $id4 ) $data['post_content'] .= '<p>SYNTHETIC BLOCKED ROLLBACK</p>'; return $data; };
 add_filter( 'wp_insert_post_data', $damage, 10, 2 );
 try { $result = Yoona_SEO_Divi_Editor::save( $id4, Yoona_SEO_Divi_Editor::fingerprint( $id4 ) ); }
 finally { remove_filter( 'wp_insert_post_data', $damage, 10 ); }
 $check( is_wp_error( $result ) && false !== strpos( $result->get_error_message(), 'pełne przywrócenie nie powiodły' ), 'both write/rollback failure is reported accurately' );
 $check( get_post_meta( $id4, Yoona_SEO_Divi_Editor::BACKUP, true )['content'] === $source4, 'only verified original backup survives failed rollback' );
 $check( $id4 === Yoona_SEO_Divi_Editor::save( $id4, Yoona_SEO_Divi_Editor::fingerprint( $id4 ), 'restore' ) && $raw( $id4 ) === $source4, 'retained original copy recovers partial failure' );

 $id5 = $make( $section( $module( 'et_pb_code', '<iframe src="https://example.invalid/map"></iframe>' ) ) ); $source5 = $raw( $id5 );
 $no_html = static function ( $caps ) { $caps['unfiltered_html'] = false; return $caps; };
 add_filter( 'user_has_cap', $no_html );
 try { $result = Yoona_SEO_Divi_Editor::save( $id5, Yoona_SEO_Divi_Editor::fingerprint( $id5 ) ); }
 finally { remove_filter( 'user_has_cap', $no_html ); }
 $check( is_wp_error( $result ) && 'html_permissions' === $result->get_error_code(), 'limited HTML account blocked before writes' );
 $check( $raw( $id5 ) === $source5 && ! metadata_exists( 'post', $id5, Yoona_SEO_Divi_Editor::BACKUP ), 'HTML denial leaves original and metadata unchanged' );
 $no_admin = static function ( $caps ) { $caps['manage_options'] = false; return $caps; };
 add_filter( 'user_has_cap', $no_admin );
 try { $result = Yoona_SEO_Divi_Editor::save( $id5, Yoona_SEO_Divi_Editor::fingerprint( $id5 ) ); }
 finally { remove_filter( 'user_has_cap', $no_admin ); }
 $check( is_wp_error( $result ) && 'forbidden' === $result->get_error_code(), 'non-admin cannot convert' );

 $front = get_post( get_option( 'page_on_front' ) );
 $make_stale = static function ( $generated, $post ) { wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => $post->post_content . '<p>Concurrent source.</p>' ) ) ); return $generated; };
 $id6 = $make();
 update_option( 'page_on_front', $id6 ); update_option( 'show_on_front', 'page' );
 add_filter( 'yoona_seo_divi_home_content', $make_stale, 10, 2 );
 try { $result = Yoona_SEO_Divi_Editor::save( $id6, Yoona_SEO_Divi_Editor::fingerprint( $id6 ) ); }
 finally { remove_filter( 'yoona_seo_divi_home_content', $make_stale, 10 ); update_option( 'page_on_front', $original_options['page_on_front'] ); }
 $check( is_wp_error( $result ) && 'source_changed' === $result->get_error_code(), 'source changing during materialization is detected before backup/write' );
 $check( ! metadata_exists( 'post', $id6, Yoona_SEO_Divi_Editor::BACKUP ), 'source changed during materialization creates no conversion backup' );

 // Same unique option key is contested by two real WP-CLI processes.
 $start = (string) ( microtime( true ) + 8 );
 $worker_file = isset( $args[1] ) ? $args[1] : '/tmp/yoona-divi-editor.php';
 $check( is_readable( $worker_file ), 'parallel workers use the real test file rather than the eval pseudo-path' );
 $command = array( PHP_BINARY, '/usr/local/bin/wp', '--allow-root', '--path=' . ABSPATH, 'eval-file', $worker_file, 'lock', (string) $id5, $start, '--skip-themes' );
 $processes = array();
 for ( $i = 0; $i < 2; ++$i ) { $pipes = array(); $process = proc_open( $command, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes ); fclose( $pipes[0] ); $processes[] = array( $process, $pipes ); }
 $outputs = array();
 foreach ( $processes as $worker ) { $outputs[] = trim( stream_get_contents( $worker[1][1] ) ); $err = stream_get_contents( $worker[1][2] ); fclose( $worker[1][1] ); fclose( $worker[1][2] ); $check( 0 === proc_close( $worker[0] ), 'real parallel lock worker exits cleanly: ' . $err ); }
 sort( $outputs );
 $check( array( 'BLOCKED', 'WINNER' ) === $outputs, 'real parallel workers admit exactly one lock owner: ' . wp_json_encode( $outputs ) );
 $check( false === get_option( Yoona_SEO_Divi_Editor::LOCK . $id5 ), 'lock owner releases its unique option' );

 // Handler refuses GET and invalid nonce before invoking the writer.
 $old_server = $_SERVER; $old_post = $_POST; $old_request = $_REQUEST;
 $die = static function () { return static function ( $message ) { throw new RuntimeException( 'EXPECTED HTTP DENIAL ' . wp_strip_all_tags( (string) $message ) ); }; };
 add_filter( 'wp_die_handler', $die ); add_filter( 'wp_die_ajax_handler', $die );
 try {
  $_SERVER['REQUEST_METHOD'] = 'GET';
  try { Yoona_SEO_Divi_Editor::handle(); $check( false, 'GET accepted unexpectedly' ); } catch ( RuntimeException $error ) { $check( false !== strpos( $error->getMessage(), 'wymaga formularza POST' ), 'HTTP handler denies GET' ); }
  $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = array( 'post_id' => $id5, 'action' => 'yoona_seo_divi_convert', 'source_fingerprint' => Yoona_SEO_Divi_Editor::fingerprint( $id5 ) ); $_REQUEST = $_POST; $_REQUEST['_wpnonce'] = 'invalid';
  try { Yoona_SEO_Divi_Editor::handle(); $check( false, 'bad nonce accepted unexpectedly' ); } catch ( RuntimeException $error ) { $check( false !== strpos( $error->getMessage(), 'EXPECTED HTTP DENIAL' ), 'HTTP handler denies invalid nonce' ); }
 } finally { $_SERVER = $old_server; $_POST = $old_post; $_REQUEST = $old_request; remove_filter( 'wp_die_handler', $die ); remove_filter( 'wp_die_ajax_handler', $die ); }
 $check( $raw( $id5 ) === $source5 && ! metadata_exists( 'post', $id5, Yoona_SEO_Divi_Editor::BACKUP ), 'HTTP denials make no page write' );
 WP_CLI::success( "Divi writer: {$checks} assertions passed; real WP SQL, race, guards, verified rollback and independent recovery." );
} finally {
 $GLOBALS['wp_query'] = $previous_query; $GLOBALS['post'] = $previous_post;
 foreach ( $original_options as $key => $value ) if ( get_option( $key ) !== $value ) update_option( $key, $value );
 foreach ( $terms as $term_id ) wp_delete_term( $term_id, 'category' );
 foreach ( $created as $id ) { delete_option( Yoona_SEO_Divi_Editor::LOCK . $id ); wp_delete_post( $id, true ); }
}
