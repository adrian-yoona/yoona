<?php
/** Read-only public collection/icon assertions for an isolated loopback WordPress. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! isset( $args[0] ) ) { exit( 1 ); }
if ( ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( '127.0.0.1', 'localhost' ), true ) ) { WP_CLI::error( 'Use a disposable loopback installation.' ); }
require_once dirname( YOONA_SEO_FILE ) . '/includes/class-yoona-seo-icons.php';
$guides = json_decode( file_get_contents( dirname( YOONA_SEO_FILE ) . '/content/service-guides.json' ), true );
if ( in_array( $args[0], array( 'snapshot', 'verify' ), true ) ) {
	global $wpdb;
	$rows = array( 'posts' => $wpdb->get_results( "SELECT * FROM {$wpdb->posts} ORDER BY ID", ARRAY_A ), 'meta' => $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A ) );
	if ( 'snapshot' === $args[0] ) { update_option( 'yoona35_collections_snapshot', $rows, false ); WP_CLI::success( 'Captured all post and metadata rows before collection browsing.' ); }
	elseif ( $rows === get_option( 'yoona35_collections_snapshot' ) ) { WP_CLI::success( 'All post and metadata rows remain byte-identical after collection browsing.' ); }
	else { WP_CLI::error( 'Collection browsing altered post or metadata rows.' ); }
	return;
}
if ( 'export-state' === $args[0] ) {
	global $wp_the_query, $wp_query;
	$previous_main = $wp_the_query;
	$previous_query = $wp_query;
	$query = new WP_Query();
	$wp_the_query = $query;
	$wp_query = $query;
	$query->query( array( 'post_type' => 'post' ) );
	$state = array( 'offers' => Yoona_SEO_Design::offer_cards(), 'first' => array_map( 'get_permalink', wp_list_pluck( $query->posts, 'ID' ) ), 'total' => (int) $query->found_posts, 'pages' => (int) $query->max_num_pages );
	$query->query( array( 'post_type' => 'post', 'paged' => 2 ) );
	$state['second'] = array_map( 'get_permalink', wp_list_pluck( $query->posts, 'ID' ) );
	$wp_the_query = $previous_main;
	$wp_query = $previous_query;
	echo wp_json_encode( $state );
	return;
}
if ( 'export-icons' === $args[0] ) {
	$icons = array( 'services' => array(), 'social' => array() );
	foreach ( $guides as $path => $guide ) { $icons['services'][ $path ] = Yoona_SEO_Icons::service( $path ); }
	foreach ( array( 'Facebook', 'Instagram', 'Tiktok', 'Youtube' ) as $name ) { $icons['social'][ $name ] = Yoona_SEO_Icons::social( $name ); }
	echo wp_json_encode( $icons );
	return;
}
$count = 0;
$check = static function ( $value, $label ) use ( &$count ) {
	if ( ! $value ) { WP_CLI::error( 'Collections35: ' . $label ); }
	++$count;
	WP_CLI::log( 'PASS Collections35 ' . $label );
};
if ( in_array( $args[0], array( 'prepare', 'prepare-index' ), true ) ) {
	$existing = get_page_by_path( 'nasze-realizacje', OBJECT, 'page' );
	$id = wp_insert_post( array( 'ID' => $existing ? $existing->ID : 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'nasze-realizacje', 'post_title' => 'Nasze realizacje', 'post_content' => '<p>PUBLIC SYNTHETIC POSTS INDEX</p>' ), true );
	$check( ! is_wp_error( $id ), 'A public native posts-index page is prepared.' );
	update_option( 'page_for_posts', $id );
	update_option( 'show_on_front', 'page' );
	flush_rewrite_rules( false );
	if ( 'prepare-index' === $args[0] ) { WP_CLI::success( 'The disposable native posts index is configured.' ); return; }
}
if ( 'prepare' === $args[0] ) {
	global $wpdb;
	foreach ( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('post','attachment') AND post_name LIKE 'yoona35-collection-%'" ) as $old_id ) { wp_delete_post( (int) $old_id, true ); }
	$uploads = wp_upload_dir();
	wp_mkdir_p( $uploads['path'] );
	$file = $uploads['path'] . '/yoona35-collection-photo.jpg';
	if ( ! function_exists( 'imagecreatetruecolor' ) ) { WP_CLI::error( 'GD is required only to create this disposable synthetic photograph.' ); }
	$canvas = imagecreatetruecolor( 900, 675 );
	imagefill( $canvas, 0, 0, imagecolorallocate( $canvas, 35, 58, 54 ) );
	imagestring( $canvas, 5, 45, 300, 'LOCAL COLLECTION FIXTURE - NOT A PRODUCTION PHOTO', imagecolorallocate( $canvas, 255, 255, 255 ) );
	imagejpeg( $canvas, $file, 85 );
	imagedestroy( $canvas );
	$attachment = wp_insert_attachment( array( 'post_title' => 'Local synthetic collection photo', 'post_name' => 'yoona35-collection-photo', 'post_status' => 'inherit', 'post_mime_type' => 'image/jpeg' ), $file, 0, true );
	$check( ! is_wp_error( $attachment ), 'Local public synthetic photo attachment is created.' );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment, wp_generate_attachment_metadata( $attachment, $file ) );
	$url = wp_get_attachment_url( $attachment );
	for ( $index = 1; $index <= 57; ++$index ) {
		$id = wp_insert_post( wp_slash( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_name' => 'yoona35-collection-project-' . $index, 'post_title' => 'REALIZACJA TESTOWA ' . sprintf( '%02d', $index ) . ' — OZNAKOWANIE FIRMY I REKLAMA WIELKOFORMATOWA', 'post_date' => '2026-09-' . sprintf( '%02d', 1 + intdiv( $index - 1, 2 ) ) . ' 12:00:00', 'post_content' => ( $index % 2 ? '<p>HIDDEN_COLLECTION_BODY</p>' : '<img src="' . esc_url( $url ) . '" alt="Synthetic collection photo"><p>HIDDEN_COLLECTION_BODY</p>' ), 'post_excerpt' => 'HIDDEN_COLLECTION_EXCERPT' ) ), true );
		$check( ! is_wp_error( $id ), 'Synthetic public photo project is created: ' . $index );
		if ( $index % 2 ) { set_post_thumbnail( $id, $attachment ); }
	}
	$excluded = array(
		'draft' => array( 'draft', '', '<img src="' . $url . '">' ),
		'private' => array( 'private', '', '<img src="' . $url . '">' ),
		'password' => array( 'publish', 'fixture-password', '<img src="' . $url . '">' ),
		'no-photo' => array( 'publish', '', '<p>No source photo.</p>' ),
		'external-photo' => array( 'publish', '', '<img src="https://external.example.invalid/wp-content/uploads/test.jpg">' ),
		'retired-photo' => array( 'publish', '', '<img src="https://yoona.pl/wp-content/uploads/2026/03/agencja-reklamowa-olsztyn.png">' ),
	);
	foreach ( $excluded as $label => $spec ) {
		$id = wp_insert_post( wp_slash( array( 'post_type' => 'post', 'post_status' => $spec[0], 'post_password' => $spec[1], 'post_name' => 'yoona35-collection-excluded-' . $label, 'post_title' => 'FORBIDDEN_COLLECTION_PROJECT_' . $label, 'post_date' => '2026-10-17 12:00:00', 'post_content' => $spec[2] ) ), true );
		$check( ! is_wp_error( $id ), 'Newer excluded project probe is created: ' . $label );
	}
	flush_rewrite_rules( false );
	WP_CLI::success( $count . ' public collection fixture preparation assertions passed.' );
	return;
}
$check( 47 === count( $guides ), 'All 47 public service definitions are available.' );
foreach ( $guides as $path => $guide ) {
	$svg = Yoona_SEO_Icons::service( $path );
	$check( 1 === substr_count( $svg, '<svg ' ) && false === strpos( $svg, 'data-yn-icon="spark"' ), 'Semantic SVG is mapped: ' . $path );
	$check( $svg === Yoona_SEO_Icons::service( home_url( $path ) ), 'Relative and absolute URLs resolve to identical artwork: ' . $path );
	$check( false === strpos( $svg, '<script' ) && false === strpos( $svg, 'href=' ) && false === strpos( $svg, 'id=' ) && false !== strpos( $svg, 'aria-hidden="true"' ) && false !== strpos( $svg, 'focusable="false"' ), 'Repeated SVG has no active content or duplicate IDs: ' . $path );
}
$check( false === strpos( Yoona_SEO_Icons::service( '/bad/\"><script>alert(1)</script>' ), '<script' ), 'Unknown service input cannot inject SVG markup.' );
foreach ( array( 'Facebook', 'Instagram', 'Tiktok', 'Youtube' ) as $name ) {
	$svg = Yoona_SEO_Icons::social( $name );
	$check( 1 === substr_count( $svg, '<svg ' ) && false !== strpos( $svg, 'yn-icon-' . strtolower( $name ) ) && false !== strpos( $svg, 'aria-hidden="true"' ), 'Recognizable fixed decorative social SVG: ' . $name );
}
$check( '' === Yoona_SEO_Icons::social( '<img src=x onerror=alert(1)>' ), 'Unsupported social input produces no markup.' );

if ( 'query' === $args[0] ) {
	global $wp_the_query, $wp_query, $wpdb;
	$previous_main = $wp_the_query;
	$previous_query = $wp_query;
	try {
		$query = new WP_Query();
		$wp_the_query = $query;
		$wp_query = $query;
		$query->query( array( 'post_type' => 'post', 'posts_per_page' => 3 ) );
		$check( $query->is_home() && $query->get( Yoona_SEO_Project_Gallery::QUERY_FLAG ), 'The real posts index receives the native collection query filter.' );
		$check( 24 === $query->get( 'posts_per_page' ) && 24 === $query->get( 'posts_per_archive_page' ), 'The native public query requests 24 projects per page.' );
		$check( 'publish' === $query->get( 'post_status' ) && false === $query->get( 'has_password' ) && false === $query->get( 'no_found_rows' ), 'Published visibility, password exclusion and native found rows remain enabled.' );
		$check( array( 'date' => 'DESC', 'ID' => 'DESC' ) === $query->get( 'orderby' ) && true === $query->get( 'ignore_sticky_posts' ), 'Stable newest-date/ID ordering ignores sticky-post promotion.' );
		$check( count( $query->posts ) <= 24 && count( $query->posts ) === min( 24, (int) $query->found_posts ), 'The first page is filled up to the native 24-item limit.' );
		$first_ids = wp_list_pluck( $query->posts, 'ID' );
		foreach ( $query->posts as $project ) {
			$check( 'publish' === $project->post_status && '' === (string) $project->post_password && '' !== Yoona_SEO_Project_Gallery::image( $project->ID ), 'Every visible native result is public and has a qualifying photograph.' );
		}
		$where = Yoona_SEO_Project_Gallery::photograph_predicate( '1=1', $query );
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish' AND post_password='' AND " . $where ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Fixed columns and the production helper's prepared predicate.
		$check( (int) $query->found_posts === $total && (int) $query->max_num_pages === (int) ceil( $total / 24 ), 'SQL photograph exclusions agree with native total and page count.' );
		if ( $total > 24 ) {
			$query->query( array( 'post_type' => 'post', 'posts_per_page' => 3, 'paged' => 2 ) );
			$second_ids = wp_list_pluck( $query->posts, 'ID' );
			$check( count( $second_ids ) === min( 24, $total - 24 ) && ! array_intersect( $first_ids, $second_ids ), 'Native page two contains only the next unique photographs.' );
		}
		$secondary_args = array( 'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 24, 'ignore_sticky_posts' => true, 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), Yoona_SEO_Project_Gallery::QUERY_FLAG => 'native' );
		$secondary = new WP_Query( $secondary_args );
		$check( ! $secondary->is_main_query() && false !== strpos( $secondary->request, 'yn_project_thumb' ) && $first_ids === wp_list_pluck( $secondary->posts, 'ID' ) && $total === (int) $secondary->found_posts, 'Explicitly public native Divi secondary query reuses the same photograph SQL, order and total.' );
		$secondary_second = new WP_Query( array_merge( $secondary_args, array( 'paged' => 2 ) ) );
		$check( wp_list_pluck( $query->posts, 'ID' ) === wp_list_pluck( $secondary_second->posts, 'ID' ), 'Explicit native secondary page two matches the native posts-index page two.' );
		foreach ( array( array( 'post_type' => 'page' ), array( 'post_status' => 'private' ), array( 'has_password' => true ), array( 'has_password' => '' ), array( Yoona_SEO_Project_Gallery::QUERY_FLAG => 'unreviewed' ) ) as $invalid ) {
			$probe = new WP_Query();
			foreach ( array_merge( $secondary_args, $invalid ) as $key => $value ) { $probe->set( $key, $value ); }
			$check( 'UNCHANGED_WHERE' === Yoona_SEO_Project_Gallery::photograph_predicate( 'UNCHANGED_WHERE', $probe ), 'Unapproved secondary query properties cannot activate the public-photo predicate.' );
		}
	} finally {
		$wp_the_query = $previous_main;
		$wp_query = $previous_query;
	}
}
WP_CLI::success( $count . ' read-only collection/icon assertions passed.' );
