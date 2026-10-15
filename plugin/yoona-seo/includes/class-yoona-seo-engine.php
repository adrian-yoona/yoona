<?php
/**
 * Reviewed content deployment, diagnostics and optimistic rollback.
 *
 * No changes are made on activation. All public operations require an
 * administrator; an individual post additionally requires edit_post.
 *
 * @package Yoona_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Yoona_SEO_Engine {
	const IMPORT_OPTION = 'yoona_seo_imported_bundle';
	const BATCH_PREFIX  = 'yoona_seo_batch_';
	const INDEX_OPTION  = 'yoona_seo_batches';
	const LOCK_OPTION   = 'yoona_seo_operation_lock';
	const TITLE_META    = '_yoona_seo_title';
	const DESC_META     = '_yoona_seo_description';

	/** Read the last imported bundle, falling back to the bundled JSON. */
	public static function load_bundle() {
		$allowed = self::require_admin();
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$imported = get_option( self::IMPORT_OPTION, null );
		if ( null !== $imported ) {
			return self::validate_bundle( $imported );
		}
		$file = dirname( __DIR__ ) . '/content/changes.json';
		if ( ! is_readable( $file ) || filesize( $file ) > 5 * MB_IN_BYTES ) {
			return new WP_Error( 'yoona_bundle_file', 'Nie można odczytać paczki zmian albo plik przekracza 5 MB.' );
		}
		$json = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( $json, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'yoona_bundle_json', 'Paczka zmian nie jest poprawnym plikiem JSON.' );
		}
		return self::validate_bundle( $data );
	}

	/** Validate without resolving URLs or modifying the submitted HTML. */
	public static function validate_bundle( $bundle ) {
		$allowed = self::require_admin();
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		if ( ! is_array( $bundle ) || ! isset( $bundle['schema_version'] ) || 1 !== $bundle['schema_version'] ) {
			return new WP_Error( 'yoona_bundle_version', 'Paczka musi używać schema_version: 1.' );
		}
		if ( ! isset( $bundle['site_url'] ) || ! is_string( $bundle['site_url'] ) || ! self::site_identity( $bundle['site_url'] ) ) {
			return new WP_Error( 'yoona_bundle_site', 'site_url musi być adresem HTTP(S) bez loginu, zapytania i fragmentu.' );
		}
		if ( ! isset( $bundle['audit_verified'] ) || ! is_bool( $bundle['audit_verified'] ) || ! isset( $bundle['pages'] ) || ! is_array( $bundle['pages'] ) ) {
			return new WP_Error( 'yoona_bundle_shape', 'Paczka wymaga audit_verified (true/false) i tablicy pages.' );
		}
		if ( count( $bundle['pages'] ) > 500 ) {
			return new WP_Error( 'yoona_bundle_limit', 'Jedna paczka może zawierać maksymalnie 500 stron.' );
		}
		$normalized = array(
			'schema_version' => 1,
			'site_url'       => untrailingslashit( $bundle['site_url'] ),
			'audit_verified' => $bundle['audit_verified'],
			'pages'          => array(),
		);
		$seen = array();
		foreach ( $bundle['pages'] as $page ) {
			if ( ! is_array( $page ) || ! isset( $page['path'] ) ) {
				return new WP_Error( 'yoona_page_shape', 'Każda zmiana musi mieć pole path.' );
			}
			$path = self::normalize_path( $page['path'] );
			if ( is_wp_error( $path ) ) {
				return $path;
			}
			if ( isset( $seen[ $path ] ) ) {
				return new WP_Error( 'yoona_duplicate_path', 'Ta sama ścieżka występuje w paczce więcej niż raz: ' . $path );
			}
			$seen[ $path ] = true;
			$item          = array( 'path' => $path );
			$allowed_keys  = array( 'path', 'seo_title', 'meta_description', 'post_title', 'content', 'expected_content_sha256', 'expected_post_title' );
			if ( array_diff( array_keys( $page ), $allowed_keys ) ) {
				return new WP_Error( 'yoona_page_fields', 'Nieobsługiwane pola zmiany dla: ' . $path );
			}
			foreach ( array( 'seo_title' => 500, 'meta_description' => 2000 ) as $field => $limit ) {
				if ( ! isset( $page[ $field ] ) || ! is_string( $page[ $field ] ) || '' === trim( $page[ $field ] ) || strlen( $page[ $field ] ) > $limit * 4 ) {
					return new WP_Error( 'yoona_seo_field', 'Brak poprawnego pola ' . $field . ' dla: ' . $path );
				}
				$clean = sanitize_text_field( $page[ $field ] );
				if ( trim( $page[ $field ] ) !== $clean ) {
					return new WP_Error( 'yoona_seo_html', 'Pole ' . $field . ' musi zawierać zwykły tekst w jednym wierszu: ' . $path );
				}
				$item[ $field ] = $clean;
			}
			if ( array_key_exists( 'post_title', $page ) ) {
				if ( ! is_string( $page['post_title'] ) || '' === trim( $page['post_title'] ) || sanitize_text_field( $page['post_title'] ) !== trim( $page['post_title'] ) ) {
					return new WP_Error( 'yoona_post_title', 'Nowy tytuł strony musi być niepustym zwykłym tekstem: ' . $path );
				}
				$item['post_title'] = trim( $page['post_title'] );
			}
			if ( array_key_exists( 'expected_post_title', $page ) ) {
				if ( ! is_string( $page['expected_post_title'] ) || strlen( $page['expected_post_title'] ) > 16000 ) {
					return new WP_Error( 'yoona_title_baseline', 'Niepoprawny expected_post_title dla: ' . $path );
				}
				$item['expected_post_title'] = $page['expected_post_title'];
			}
			if ( array_key_exists( 'content', $page ) ) {
				if ( ! is_string( $page['content'] ) || '' === trim( $page['content'] ) || strlen( $page['content'] ) > 2 * MB_IN_BYTES ) {
					return new WP_Error( 'yoona_content', 'Treść musi być niepustym tekstem do 2 MB: ' . $path );
				}
				if ( wp_kses_post( $page['content'] ) !== $page['content'] ) {
					return new WP_Error( 'yoona_unsafe_html', 'Treść zawiera HTML niedopuszczony przez WordPress. Wymagana jest poprawiona paczka: ' . $path );
				}
				$item['content'] = $page['content'];
			}
			if ( array_key_exists( 'expected_content_sha256', $page ) ) {
				if ( ! is_string( $page['expected_content_sha256'] ) || ! preg_match( '/\A[a-f0-9]{64}\z/i', $page['expected_content_sha256'] ) ) {
					return new WP_Error( 'yoona_content_hash', 'Niepoprawny expected_content_sha256 dla: ' . $path );
				}
				$item['expected_content_sha256'] = strtolower( $page['expected_content_sha256'] );
			}
			$normalized['pages'][] = $item;
		}
		return $normalized;
	}

	/** Return a fresh, read-only plan. Content is never rewritten in preview. */
	public static function preview( $bundle ) {
		$bundle = self::validate_bundle( $bundle );
		if ( is_wp_error( $bundle ) ) {
			return $bundle;
		}
		$match    = self::site_identity( $bundle['site_url'] ) === self::site_identity( home_url( '/' ) );
		$warnings = array();
		if ( ! $bundle['audit_verified'] ) {
			$warnings[] = 'Audyt nie został zweryfikowany. Wdrożenie jest zablokowane.';
		}
		if ( ! $match ) {
			$warnings[] = 'Domena lub katalog paczki nie odpowiada home_url() tej instalacji. Wdrożenie jest zablokowane.';
		}
		if ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
			$warnings[] = 'Aktywny The SEO Framework nie ma integracji z tą wtyczką. Zmiany SEO są zablokowane do przygotowania integracji.';
		}
		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			$warnings[] = 'Witryna używa prostych adresów z parametrami. Wdrożenie ścieżek poza statyczną stroną główną wymaga przyjaznych adresów; wtyczka nie zmienia ich automatycznie.';
		}
		$pages = array();
		foreach ( $bundle['pages'] as $page ) {
			$pages[] = self::plan_page( $page, $bundle['audit_verified'] && $match );
		}
		return array(
			'valid'          => true,
			'audit_verified' => $bundle['audit_verified'],
			'site_match'     => $match,
			'warnings'       => $warnings,
			'pages'          => $pages,
			'history'        => self::list_batches(),
		);
	}

	/** Back up all selected ready pages before making the first write. */
	public static function apply( $bundle, $selected_paths ) {
		$bundle = self::validate_bundle( $bundle );
		if ( is_wp_error( $bundle ) ) {
			return $bundle;
		}
		if ( ! $bundle['audit_verified'] || ! $bundle['pages'] ) {
			return new WP_Error( 'yoona_unverified', 'Nie można wdrożyć pustej paczki lub paczki bez zweryfikowanego audytu.' );
		}
		if ( self::site_identity( $bundle['site_url'] ) !== self::site_identity( home_url( '/' ) ) ) {
			return new WP_Error( 'yoona_site_mismatch', 'Paczka dotyczy innej domeny lub katalogu niż bieżąca instalacja.' );
		}
		if ( ! is_array( $selected_paths ) || ! $selected_paths ) {
			return new WP_Error( 'yoona_no_selection', 'Wybierz co najmniej jedną stronę.' );
		}
		$selected = array();
		foreach ( $selected_paths as $path ) {
			$path = self::normalize_path( $path );
			if ( is_wp_error( $path ) ) {
				return $path;
			}
			$selected[ $path ] = true;
		}
		$known = array_column( $bundle['pages'], 'path' );
		if ( array_diff( array_keys( $selected ), $known ) ) {
			return new WP_Error( 'yoona_unknown_selection', 'Wybrano ścieżkę, której nie ma w aktualnej paczce.' );
		}
		$lock = self::acquire_lock();
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}
		try {
			return self::apply_locked( $bundle, $selected, $lock );
		} finally {
			self::release_lock( $lock );
		}
	}

	/** Restore only a state which still matches the saved applied state. */
	public static function rollback( $batch_id ) {
		$allowed = self::require_admin();
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		if ( ! is_string( $batch_id ) || ! preg_match( '/\A[a-f0-9-]{36}\z/', $batch_id ) ) {
			return new WP_Error( 'yoona_batch_id', 'Niepoprawny identyfikator kopii.' );
		}
		$lock = self::acquire_lock();
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}
		try {
			return self::rollback_locked( $batch_id, $lock );
		} finally {
			self::release_lock( $lock );
		}
	}

	/** An index contains no content; backups are separate non-autoload options. */
	public static function list_batches() {
		$allowed = self::require_admin();
		if ( is_wp_error( $allowed ) ) {
			return array();
		}
		$index = get_option( self::INDEX_OPTION, array() );
		return is_array( $index ) ? array_values( array_reverse( $index ) ) : array();
	}

	/** Export a whitelist only, never users, credentials, drafts or all options. */
	public static function export_diagnostics() {
		$allowed = self::require_admin();
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$posts = get_posts(
			array(
				'post_type'        => array( 'page', 'post' ),
				'post_status'      => 'publish',
				'has_password'     => false,
				'numberposts'      => 1001,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);
		$truncated = count( $posts ) > 1000;
		$posts     = array_slice( $posts, 0, 1000 );
		$pages     = array();
		$seo_keys  = array(
			self::TITLE_META, self::DESC_META,
			'_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_canonical', '_yoast_wpseo_meta-robots-noindex',
			'rank_math_title', 'rank_math_description', 'rank_math_canonical_url', 'rank_math_robots',
			'_seopress_titles_title', '_seopress_titles_desc', '_seopress_robots_index', '_seopress_robots_canonical',
		);
		foreach ( $posts as $post ) {
			if ( ! self::is_public_post( $post ) ) {
				continue;
			}
			$url = get_permalink( $post );
			if ( ! $url || ! self::url_on_current_site( $url ) ) {
				continue;
			}
			$metadata = array();
			foreach ( $seo_keys as $key ) {
				if ( metadata_exists( 'post', $post->ID, $key ) ) {
					$metadata[ $key ] = get_post_meta( $post->ID, $key, true );
				}
			}
			$path = self::relative_permalink_path( $url );
			$path_supported = ! is_wp_error( $path ) && ! wp_parse_url( $url, PHP_URL_QUERY );
			$page = array(
				'post_id'               => (int) $post->ID,
				'post_type'             => $post->post_type,
				'url'                   => $url,
				'path'                  => $path_supported ? $path : null,
				'path_supported'        => $path_supported,
				'path_note'             => $path_supported ? '' : 'Adres zawiera parametry lub nieobsługiwaną ścieżkę. Nie można zamienić go automatycznie na path paczki zmian.',
				'post_title'            => $post->post_title,
				'post_content'          => $post->post_content,
				'content_sha256'        => hash( 'sha256', $post->post_content ),
				'seo_metadata'          => $metadata,
				'builders'              => self::detect_builders( $post ),
				'elementor_public_text' => self::elementor_text( get_post_meta( $post->ID, '_elementor_data', true ) ),
			);
			$pages[] = $page;
		}
		return array(
			'schema_version' => 1,
			'exported_at'    => gmdate( 'c' ),
			'site_url'       => home_url( '/' ),
			'purpose'        => 'Publiczne treści i ustawienia potrzebne do audytu SEO; nie jest to paczka gotowa do wdrożenia.',
			'truncated'      => $truncated,
			'limit'          => 1000,
			'configuration'  => array(
				'blog_public'         => (string) get_option( 'blog_public' ),
				'permalink_structure' => (string) get_option( 'permalink_structure' ),
				'show_on_front'       => (string) get_option( 'show_on_front' ),
				'page_on_front'       => (int) get_option( 'page_on_front' ),
				'page_for_posts'      => (int) get_option( 'page_for_posts' ),
				'wp_version'          => get_bloginfo( 'version' ),
			),
			'pages'          => $pages,
		);
	}

	private static function require_admin() {
		return current_user_can( 'manage_options' ) ? true : new WP_Error( 'yoona_forbidden', 'Ta operacja wymaga uprawnień administratora.' );
	}

	/** Canonical identity deliberately includes the port and home subdirectory. */
	private static function site_identity( $url ) {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) ) {
			return false;
		}
		$scheme = strtolower( $parts['scheme'] );
		$port   = isset( $parts['port'] ) ? (int) $parts['port'] : ( 'https' === $scheme ? 443 : 80 );
		$path   = isset( $parts['path'] ) ? rtrim( $parts['path'], '/' ) : '';
		return $scheme . '://' . strtolower( $parts['host'] ) . ':' . $port . $path;
	}

	private static function normalize_path( $path ) {
		if ( ! is_string( $path ) || '' === $path || '/' !== substr( $path, 0, 1 ) || '//' === substr( $path, 0, 2 ) || preg_match( '/[\x00-\x20\x7f?#\\\\]/', $path ) || preg_match( '/%(?:2e|2f|5c|00|0a|0d|25)/i', $path ) || preg_match( '#(?:^|/)\.{1,2}(?:/|$)#', $path ) ) {
			return new WP_Error( 'yoona_path', 'Ścieżka musi być lokalnym adresem /sciezka/ bez parametrów, fragmentu i sekwencji specjalnych.' );
		}
		return '/' === $path ? '/' : '/' . trim( $path, '/' ) . '/';
	}

	private static function url_on_current_site( $url ) {
		$parts = wp_parse_url( $url );
		$home  = wp_parse_url( home_url( '/' ) );
		if ( ! is_array( $parts ) || ! is_array( $home ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || strtolower( $parts['host'] ) !== strtolower( $home['host'] ) || strtolower( $parts['scheme'] ) !== strtolower( $home['scheme'] ) ) {
			return false;
		}
		$base = rtrim( isset( $home['path'] ) ? $home['path'] : '', '/' );
		$path = isset( $parts['path'] ) ? $parts['path'] : '/';
		if ( $base && $path !== $base && 0 !== strpos( $path, $base . '/' ) ) {
			return false;
		}
		$port      = isset( $parts['port'] ) ? (int) $parts['port'] : ( isset( $parts['scheme'] ) && 'https' === strtolower( $parts['scheme'] ) ? 443 : 80 );
		$home_port = isset( $home['port'] ) ? (int) $home['port'] : ( 'https' === strtolower( $home['scheme'] ) ? 443 : 80 );
		return $port === $home_port;
	}

	private static function relative_permalink_path( $url ) {
		$path      = (string) wp_parse_url( $url, PHP_URL_PATH );
		$home_path = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( $home_path && ( $path === $home_path || 0 === strpos( $path, $home_path . '/' ) ) ) {
			$path = substr( $path, strlen( $home_path ) );
		}
		return self::normalize_path( '' === $path ? '/' : $path );
	}

	private static function is_public_post( $post ) {
		return $post instanceof WP_Post && in_array( $post->post_type, array( 'page', 'post' ), true ) && 'publish' === $post->post_status && '' === $post->post_password;
	}

	private static function resolve_post( $path ) {
		if ( '/' === $path ) {
			if ( 'page' !== get_option( 'show_on_front' ) || ! get_option( 'page_on_front' ) ) {
				return new WP_Error( 'yoona_blog_home', 'Strona główna pokazuje listę wpisów. Wtyczka wymaga statycznej strony głównej; nie zmieni tej konfiguracji.' );
			}
			$id = (int) get_option( 'page_on_front' );
		} else {
			if ( '' === (string) get_option( 'permalink_structure' ) ) {
				return new WP_Error( 'yoona_pretty_permalinks', 'Ta ścieżka wymaga przyjaznych adresów WordPress. Wtyczka nie zmienia adresów ani slugów automatycznie.' );
			}
			$id = url_to_postid( trailingslashit( home_url( '/' ) ) . ltrim( $path, '/' ) );
		}
		$post = $id ? get_post( $id ) : null;
		if ( ! self::is_public_post( $post ) ) {
			return new WP_Error( 'yoona_not_public', 'Nie znaleziono opublikowanej publicznej strony lub wpisu pod tą ścieżką.' );
		}
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return new WP_Error( 'yoona_cannot_edit', 'Brak uprawnień do edycji tego wpisu.' );
		}
		$url = get_permalink( $post );
		if ( ! $url || ! self::url_on_current_site( $url ) || self::relative_permalink_path( $url ) !== $path ) {
			return new WP_Error( 'yoona_permalink', 'Adres kanoniczny wpisu nie odpowiada dokładnie wybranej ścieżce na tej domenie.' );
		}
		return $post;
	}

	private static function plan_page( $page, $global_ready ) {
		$row = array(
			'path' => $page['path'], 'post_id' => 0, 'post_title' => '', 'url' => '',
			'status' => 'blocked', 'messages' => array(), 'builders' => array(),
			'current' => array(), 'current_source' => array(), 'new' => array(), 'can_apply' => false,
		);
		foreach ( array( 'seo_title', 'meta_description', 'post_title', 'content' ) as $field ) {
			if ( array_key_exists( $field, $page ) ) {
				$row['new'][ $field ] = $page[ $field ];
			}
		}
		$post = self::resolve_post( $page['path'] );
		if ( is_wp_error( $post ) ) {
			$row['messages'][] = $post->get_error_message();
			return $row;
		}
		$row['post_id']    = (int) $post->ID;
		$row['post_title'] = $post->post_title;
		$row['url']        = get_permalink( $post );
		$row['builders']   = self::detect_builders( $post );
		if ( isset( $page['content'] ) ) {
			$proposed               = clone $post;
			$proposed->post_content = $page['content'];
			$row['builders']        = array_values( array_unique( array_merge( $row['builders'], self::detect_builders( $proposed ) ) ) );
		}
		$effective        = self::current_seo( $post );
		$row['current_source'] = $effective['source'];
		$row['messages']  = array_merge( $row['messages'], $effective['messages'] );
		$row['current']    = array(
			'seo_title'        => $effective['seo_title'],
			'meta_description' => $effective['meta_description'],
			'post_title'       => $post->post_title,
			'content'          => $post->post_content,
			'content_sha256'   => hash( 'sha256', $post->post_content ),
		);
		$blocked = ! $global_ready;
		if ( ! $global_ready ) {
			$row['messages'][] = 'Wymagany zweryfikowany audyt i zgodna domena paczki.';
		}
		if ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
			$blocked           = true;
			$row['messages'][] = 'Brak integracji z aktywnym The SEO Framework; nie można bezpiecznie wdrożyć obu pól SEO.';
		}
		if ( $row['builders'] ) {
			$row['messages'][] = 'Wykryty edytor: ' . implode( ', ', $row['builders'] ) . '. Metadane SEO można zmienić, jeśli paczka nie zawiera content.';
		}
		if ( isset( $page['post_title'] ) && ( ! array_key_exists( 'expected_post_title', $page ) || $page['expected_post_title'] !== $post->post_title ) ) {
			$blocked           = true;
			$row['messages'][] = 'Zmiana tytułu strony wymaga expected_post_title identycznego z bieżącym tytułem. Nie nadpisano późniejszej edycji.';
		}
		if ( ( isset( $page['post_title'] ) || isset( $page['content'] ) ) && ! current_user_can( 'unfiltered_html' ) && ( wp_kses_post( $post->post_content ) !== $post->post_content || wp_kses_post( $post->post_excerpt ) !== $post->post_excerpt || wp_kses_post( $post->post_content_filtered ) !== $post->post_content_filtered || wp_kses( $post->post_title, 'title_save_pre' ) !== $post->post_title ) ) {
			$blocked           = true;
			$row['messages'][] = 'Bieżące pola wpisu zawierają HTML, którego to konto nie mogłoby odtworzyć przy przywracaniu kopii. Zmiana tytułu lub treści jest zablokowana.';
		}
		if ( isset( $page['content'] ) ) {
			if ( empty( $page['expected_content_sha256'] ) || ! hash_equals( $row['current']['content_sha256'], $page['expected_content_sha256'] ) ) {
				$blocked           = true;
				$row['messages'][] = 'Brak zgodnego SHA-256 obecnej treści. Wymagany aktualny eksport i ponowny przegląd tekstu.';
			}
			if ( $row['builders'] || (int) get_option( 'page_for_posts' ) === (int) $post->ID ) {
				$blocked           = true;
				$row['messages'][] = 'Wymiana treści tego edytora lub strony listy wpisów nie jest obsługiwana. Przygotuj osobną zmianę w jego natywnym formacie.';
			}
		}
		if ( $blocked ) {
			return $row;
		}
		$before = self::capture_state( $post->ID, self::fields_for_page( $page ) );
		$after  = self::desired_state( $before, $page );
		if ( $before === $after ) {
			$row['status']     = 'unchanged';
			$row['messages'][] = 'Wszystkie wskazane wartości są już zapisane.';
			return $row;
		}
		$row['status']    = 'ready';
		$row['can_apply'] = true;
		return $row;
	}

	private static function display_meta( $value ) {
		return is_scalar( $value ) ? (string) $value : '[wartość o nieobsługiwanym typie]';
	}

	/** Stored templates are useful evidence, but are not a rendered <head>. */
	private static function current_seo( $post ) {
		$result = array( 'seo_title' => '', 'meta_description' => '', 'source' => array(), 'messages' => array() );
		$providers = array();
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' ) ) {
			$providers['Yoast SEO'] = array( '_yoast_wpseo_title', '_yoast_wpseo_metadesc' );
		} elseif ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
			$providers['Rank Math'] = array( 'rank_math_title', 'rank_math_description' );
		} elseif ( defined( 'SEOPRESS_VERSION' ) ) {
			$providers['SEOPress'] = array( '_seopress_titles_title', '_seopress_titles_desc' );
		}
		foreach ( array( 'seo_title' => self::TITLE_META, 'meta_description' => self::DESC_META ) as $field => $own_key ) {
			$value = get_post_meta( $post->ID, $own_key, true );
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				$result[ $field ] = $value;
				$result['source'][ $field ] = 'Yoona SEO: własna nadpisana wartość';
				continue;
			}
			foreach ( $providers as $provider => $keys ) {
				$value = get_post_meta( $post->ID, 'seo_title' === $field ? $keys[0] : $keys[1], true );
				if ( is_string( $value ) && '' !== trim( $value ) ) {
					$result[ $field ] = $value;
					$result['source'][ $field ] = $provider . ': zapisana wartość lub szablon';
					$result['messages'][] = 'Podgląd ' . $provider . ' pokazuje zapisane metadane lub szablon; wynik w HTML może różnić się po rozwinięciu zmiennych.';
					break;
				}
			}
			if ( ! isset( $result['source'][ $field ] ) ) {
				$result[ $field ] = 'seo_title' === $field ? $post->post_title : '';
				$result['source'][ $field ] = 'seo_title' === $field ? 'Tytuł wpisu WordPress; szablon witryny może go zmieniać' : 'Brak odczytanej wartości; opis może powstawać dynamicznie';
			}
		}
		if ( defined( 'AIOSEO_VERSION' ) || function_exists( 'aioseo' ) ) {
			$result['messages'][] = 'Aktywny AIOSEO używa własnych tabel. Ten podgląd nie odczytuje jego wartości; sprawdź obecne title i description w HTML.';
		}
		$result['messages'] = array_values( array_unique( $result['messages'] ) );
		return $result;
	}

	private static function detect_builders( $post ) {
		$builders = array();
		$meta     = array(
			'Elementor' => array( '_elementor_edit_mode', '_elementor_data' ),
			'Divi' => array( '_et_pb_use_builder' ),
			'WPBakery' => array( '_wpb_vc_js_status' ),
			'Beaver Builder' => array( '_fl_builder_enabled', '_fl_builder_data' ),
			'Bricks' => array( '_bricks_page_content_2', '_bricks_page_header_2', '_bricks_page_footer_2' ),
			'Oxygen' => array( 'ct_builder_shortcodes', '_ct_builder_shortcodes', 'ct_builder_json' ),
			'Breakdance' => array( '_breakdance_data' ),
		);
		foreach ( $meta as $name => $keys ) {
			foreach ( $keys as $key ) {
				$value = get_post_meta( $post->ID, $key, true );
				if ( ! empty( $value ) && ! in_array( $value, array( 'off', 'false', '0', '[]' ), true ) ) {
					$builders[] = $name;
					break;
				}
			}
		}
		if ( preg_match( '/\[(?:\/)?et_pb_/i', $post->post_content ) ) {
			$builders[] = 'Divi';
		}
		if ( preg_match( '/\[(?:\/)?vc_/i', $post->post_content ) ) {
			$builders[] = 'WPBakery';
		}
		if ( preg_match_all( '/\[(?!\[|\/)([A-Za-z_][A-Za-z0-9_-]*)(?:\s|\]|\/)/', $post->post_content, $matches ) ) {
			foreach ( array_unique( $matches[1] ) as $tag ) {
				if ( ! in_array( $tag, array( 'gallery', 'caption', 'audio', 'video', 'playlist', 'embed' ), true ) ) {
					$builders[] = 'shortcode:' . $tag;
				}
			}
		}
		self::find_custom_blocks( parse_blocks( $post->post_content ), $builders );
		return array_values( array_unique( $builders ) );
	}

	private static function find_custom_blocks( $blocks, &$builders ) {
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && ( 0 !== strpos( $block['blockName'], 'core/' ) || ( class_exists( 'WP_Block_Type_Registry' ) && ! WP_Block_Type_Registry::get_instance()->is_registered( $block['blockName'] ) ) ) ) {
				$builders[] = 'blok:' . $block['blockName'];
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				self::find_custom_blocks( $block['innerBlocks'], $builders );
			}
		}
	}

	private static function fields_for_page( $page ) {
		// wp_update_post processes all these fields even for a title-only change.
		// Keeping their baseline also catches filters which would alter a slug or
		// sanitize legacy body/excerpt content as a side effect.
		return array_key_exists( 'post_title', $page ) || array_key_exists( 'content', $page )
			? array( 'post_title', 'post_content', 'post_excerpt', 'post_content_filtered', 'post_name' ) : array();
	}

	/** Keep every meta value and its exact absence/presence, including duplicates. */
	private static function capture_state( $post_id, $fields ) {
		$post  = get_post( $post_id );
		$state = array( 'fields' => array(), 'meta' => array() );
		foreach ( $fields as $field ) {
			$state['fields'][ $field ] = $post ? $post->$field : null;
		}
		foreach ( array( self::TITLE_META, self::DESC_META ) as $key ) {
			$state['meta'][ $key ] = array(
				'exists' => metadata_exists( 'post', $post_id, $key ),
				'values' => get_post_meta( $post_id, $key, false ),
			);
		}
		return $state;
	}

	private static function desired_state( $before, $page ) {
		$state = $before;
		foreach ( array( 'post_title' => 'post_title', 'content' => 'post_content' ) as $source => $field ) {
			if ( array_key_exists( $source, $page ) ) {
				$state['fields'][ $field ] = $page[ $source ];
			}
		}
		foreach ( array( 'seo_title' => self::TITLE_META, 'meta_description' => self::DESC_META ) as $source => $key ) {
			$count = max( 1, count( $before['meta'][ $key ]['values'] ) );
			$state['meta'][ $key ] = array( 'exists' => true, 'values' => array_fill( 0, $count, $page[ $source ] ) );
		}
		return $state;
	}

	/** Standard WP APIs, followed by exact read-back; never assume false is success. */
	private static function write_state( $post_id, $state, $restore = false ) {
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			foreach ( $state['fields'] as $field => $value ) {
				if ( 'post_title' === $field && wp_kses( $value, 'title_save_pre' ) !== $value ) {
					return new WP_Error( 'yoona_restore_permissions', 'To konto nie może bez zmiany HTML zapisać tytułu z kopii. Użyj konta z odpowiednimi uprawnieniami; nie zmieniono pól strony.' );
				}
				if ( in_array( $field, array( 'post_content', 'post_excerpt', 'post_content_filtered' ), true ) && wp_kses_post( $value ) !== $value ) {
					return new WP_Error( 'yoona_restore_permissions', 'To konto nie może bez zmiany HTML zapisać treści z kopii. Użyj konta z odpowiednimi uprawnieniami; nie zmieniono pól strony.' );
				}
			}
		}
		if ( $state['fields'] ) {
			$data   = array_merge( array( 'ID' => $post_id ), $state['fields'] );
			$result = wp_update_post( wp_slash( $data ), true );
			if ( is_wp_error( $result ) || ! $result ) {
				return is_wp_error( $result ) ? $result : new WP_Error( 'yoona_post_write', 'WordPress odmówił zapisu wpisu.' );
			}
		}
		foreach ( $state['meta'] as $key => $snapshot ) {
			if ( $restore ) {
				if ( metadata_exists( 'post', $post_id, $key ) && ! delete_post_meta( $post_id, $key ) ) {
					return new WP_Error( 'yoona_meta_delete', 'Nie udało się odtworzyć metadanych: ' . $key );
				}
				foreach ( $snapshot['values'] as $value ) {
					if ( ! add_post_meta( $post_id, $key, wp_slash( $value ), false ) ) {
						return new WP_Error( 'yoona_meta_restore', 'Nie udało się odtworzyć wartości metadanych: ' . $key );
					}
				}
			} else {
				$value  = reset( $snapshot['values'] );
				$result = update_post_meta( $post_id, $key, wp_slash( $value ) );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
			$current = array( 'exists' => metadata_exists( 'post', $post_id, $key ), 'values' => get_post_meta( $post_id, $key, false ) );
			if ( $current !== $snapshot ) {
				return new WP_Error( 'yoona_meta_verify', 'Zapis metadanych nie przeszedł weryfikacji: ' . $key );
			}
		}
		if ( self::capture_state( $post_id, array_keys( $state['fields'] ) ) !== $state ) {
			return new WP_Error( 'yoona_state_verify', 'Odczyt po zapisie różni się od oczekiwanych wartości.' );
		}
		return true;
	}

	private static function save_option( $name, $value ) {
		$existing = get_option( $name, null );
		if ( null === $existing ) {
			$result = add_option( $name, $value, '', false );
		} else {
			$result = update_option( $name, $value, false );
		}
		return $result || get_option( $name, null ) === $value;
	}

	private static function save_batch( $batch ) {
		if ( ! self::save_option( self::BATCH_PREFIX . $batch['batch_id'], $batch ) ) {
			return false;
		}
		$index = get_option( self::INDEX_OPTION, array() );
		$index = is_array( $index ) ? $index : array();
		$index[ $batch['batch_id'] ] = array(
			'batch_id'   => $batch['batch_id'],
			'created_at' => $batch['created_at'],
			'count'      => count( $batch['pages'] ),
			'status'     => $batch['status'],
			'can_rollback' => false,
			'recovery_needed' => false,
		);
		foreach ( $batch['pages'] as $record ) {
			if ( in_array( $record['status'], array( 'applied', 'applying', 'recovery_needed', 'rolling_back' ), true ) ) {
				$index[ $batch['batch_id'] ]['can_rollback'] = true;
			}
			if ( in_array( $record['status'], array( 'applying', 'recovery_needed', 'rolling_back', 'conflict_needs_review' ), true ) ) {
				$index[ $batch['batch_id'] ]['recovery_needed'] = true;
			}
		}
		return self::save_option( self::INDEX_OPTION, $index );
	}

	/** INSERT IGNORE avoids add_option's upsert semantics and cached absence. */
	private static function acquire_lock() {
		$lock = array( 'token' => wp_generate_uuid4(), 'expires' => time() + 1800 );
		if ( self::insert_lock( $lock ) ) {
			return $lock;
		}
		$old = self::read_lock();
		if ( is_array( $old ) && isset( $old['expires'] ) && (int) $old['expires'] < time() ) {
			self::release_lock( $old );
			if ( self::insert_lock( $lock ) ) {
				return $lock;
			}
		}
		return new WP_Error( 'yoona_operation_locked', 'Trwa inne wdrożenie lub przywracanie. Spróbuj ponownie po jego zakończeniu; przerwana operacja blokuje maksymalnie 30 minut.' );
	}

	private static function insert_lock( $lock ) {
		global $wpdb;
		$result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", self::LOCK_OPTION, maybe_serialize( $lock ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_cache_delete( self::LOCK_OPTION, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		return 1 === $result;
	}

	private static function read_lock() {
		global $wpdb;
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::LOCK_OPTION ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return null === $value ? null : maybe_unserialize( $value );
	}

	/** Compare-and-delete cannot release a newer worker's lock. */
	private static function release_lock( $lock ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", self::LOCK_OPTION, maybe_serialize( $lock ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_cache_delete( self::LOCK_OPTION, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	private static function owns_lock( $lock ) {
		return time() < $lock['expires'] && self::read_lock() === $lock;
	}

	/** Interrupted apply may have completed only some atomic field/meta writes. */
	private static function known_mixture( $current, $before, $after ) {
		foreach ( array( 'fields', 'meta' ) as $section ) {
			foreach ( $before[ $section ] as $key => $value ) {
				if ( $current[ $section ][ $key ] !== $value && $current[ $section ][ $key ] !== $after[ $section ][ $key ] ) {
					return false;
				}
			}
		}
		return true;
	}

	private static function apply_locked( $bundle, $selected, $lock ) {
		$batch = array(
			'batch_id' => wp_generate_uuid4(), 'created_at' => gmdate( 'c' ),
			'site_url' => home_url( '/' ), 'status' => 'prepared', 'pages' => array(),
		);
		$results = array();
		foreach ( $bundle['pages'] as $page ) {
			if ( ! isset( $selected[ $page['path'] ] ) ) {
				continue;
			}
			$plan = self::plan_page( $page, true );
			if ( ! $plan['can_apply'] ) {
				$results[] = self::page_result( $page['path'], $plan['post_id'], $plan['status'], implode( ' ', $plan['messages'] ) );
				continue;
			}
			$before = self::capture_state( $plan['post_id'], self::fields_for_page( $page ) );
			$batch['pages'][] = array(
				'path' => $page['path'], 'post_id' => $plan['post_id'], 'status' => 'prepared',
				'before' => $before, 'after' => self::desired_state( $before, $page ),
			);
		}
		if ( ! $batch['pages'] ) {
			return self::operation_result( '', 'failed', $results, 'Nie zapisano zmian. Wybrane strony są zablokowane lub nie wymagają zmian.' );
		}
		if ( ! self::save_batch( $batch ) ) {
			return new WP_Error( 'yoona_backup_failed', 'Nie udało się trwale zapisać kopii i jej indeksu. Żadna strona nie została zmieniona.' );
		}
		$applied = 0;
		$errors  = false;
		foreach ( $batch['pages'] as $number => $record ) {
			$post = self::resolve_post( $record['path'] );
			if ( ! self::owns_lock( $lock ) || is_wp_error( $post ) || (int) $post->ID !== $record['post_id'] || self::capture_state( $record['post_id'], array_keys( $record['before']['fields'] ) ) !== $record['before'] ) {
				$errors = true;
				$batch['pages'][ $number ]['status'] = 'conflict';
				$results[] = self::page_result( $record['path'], $record['post_id'], 'blocked', 'Stan strony lub blokada operacji zmieniły się po utworzeniu kopii. Pominięto zapis.' );
				continue;
			}
			$batch['pages'][ $number ]['status'] = 'applying';
			if ( ! self::save_batch( $batch ) ) {
				$errors = true;
				$results[] = self::page_result( $record['path'], $record['post_id'], 'failed', 'Nie udało się zapisać stanu kopii. Przerwano dalsze wdrażanie.' );
				break;
			}
			$written = self::write_state( $record['post_id'], $record['after'] );
			if ( is_wp_error( $written ) ) {
				$errors   = true;
				$actual   = self::capture_state( $record['post_id'], array_keys( $record['before']['fields'] ) );
				$restored = self::owns_lock( $lock ) && self::known_mixture( $actual, $record['before'], $record['after'] )
					? self::write_state( $record['post_id'], $record['before'], true )
					: new WP_Error( 'yoona_recovery_conflict', 'W trakcie zapisu pojawiła się nieoczekiwana wartość lub utracono blokadę; automatyczne odtwarzanie zostało wstrzymane.' );
				if ( is_wp_error( $restored ) ) {
					$observed = self::capture_state( $record['post_id'], array_keys( $record['before']['fields'] ) );
					if ( self::known_mixture( $observed, $record['before'], $record['after'] ) ) {
						$batch['pages'][ $number ]['status'] = 'recovery_needed';
						$batch['pages'][ $number ]['after']  = $observed;
						$advice = ' Zachowano kopię; użyj przywracania i sprawdź tę stronę.';
					} else {
						$batch['pages'][ $number ]['status']   = 'conflict_needs_review';
						$batch['pages'][ $number ]['observed'] = $observed;
						$advice = ' Wykryto wartości spoza wdrażanej zmiany. Nie uznano ich za własny zapis. Kopia wymaga ręcznego porównania przez administratora; automatyczne przywracanie jest wyłączone.';
					}
					$message = 'Błąd zapisu: ' . $written->get_error_message() . ' Przywracanie również nie powiodło się: ' . $restored->get_error_message() . $advice;
				} else {
					$batch['pages'][ $number ]['status'] = 'restored_after_failure';
					$message = 'Błąd zapisu: ' . $written->get_error_message() . ' Poprzednie wartości zostały odtworzone.';
				}
				$results[] = self::page_result( $record['path'], $record['post_id'], 'failed', $message );
			} else {
				++$applied;
				$batch['pages'][ $number ]['status'] = 'applied';
				$results[] = self::page_result( $record['path'], $record['post_id'], 'applied', 'Zapisano i zweryfikowano zmiany.' );
			}
			if ( ! self::save_batch( $batch ) ) {
				$errors = true;
				$results[] = self::page_result( $record['path'], $record['post_id'], 'warning', 'Nie udało się zaktualizować statusu kopii. Kopia sprzed zapisu istnieje; przerwano dalsze wdrażanie.' );
				break;
			}
		}
		$results = self::report_unprocessed( $batch['pages'], $results );
		foreach ( $results as $result ) {
			if ( in_array( $result['status'], array( 'blocked', 'failed', 'warning', 'skipped' ), true ) ) {
				$errors = true;
			}
		}
		$batch['status'] = $applied ? ( $errors ? 'partial' : 'success' ) : 'failed';
		$saved = self::save_batch( $batch );
		$message = sprintf( 'Zapisano %d z %d wybranych stron. Kopia: %s.', $applied, count( $selected ), $batch['batch_id'] );
		if ( ! $saved ) {
			$batch['status'] = $applied ? 'partial' : 'failed';
			$message .= ' Nie udało się zapisać końcowego statusu kopii; sprawdź wyniki poszczególnych stron.';
		}
		return self::operation_result( $batch['batch_id'], $batch['status'], $results, $message );
	}

	private static function rollback_locked( $batch_id, $lock ) {
		$batch = get_option( self::BATCH_PREFIX . $batch_id );
		if ( ! is_array( $batch ) || empty( $batch['pages'] ) || empty( $batch['site_url'] ) || self::site_identity( $batch['site_url'] ) !== self::site_identity( home_url( '/' ) ) ) {
			return new WP_Error( 'yoona_backup_missing', 'Nie znaleziono kopii dla tej instalacji.' );
		}
		$results  = array();
		$restored = 0;
		$errors   = false;
		foreach ( $batch['pages'] as $number => $record ) {
			if ( 'conflict_needs_review' === $record['status'] ) {
				$errors = true;
				$results[] = self::page_result( $record['path'], $record['post_id'], 'conflict', 'Kopia zawiera konflikt z nieznaną późniejszą wartością. Wymaga ręcznego porównania przez administratora; automatyczne przywracanie jest wyłączone.' );
				continue;
			}
			if ( in_array( $record['status'], array( 'rolled_back', 'restored_after_failure', 'prepared', 'conflict' ), true ) ) {
				$results[] = self::page_result( $record['path'], $record['post_id'], 'unchanged', 'Ta strona nie wymaga przywracania.' );
				continue;
			}
			$post = self::resolve_post( $record['path'] );
			$current = self::capture_state( $record['post_id'], array_keys( $record['after']['fields'] ) );
			if ( self::owns_lock( $lock ) && ! is_wp_error( $post ) && (int) $post->ID === $record['post_id'] && $current === $record['before'] ) {
				$batch['pages'][ $number ]['status'] = 'rolled_back';
				$results[] = self::page_result( $record['path'], $record['post_id'], 'unchanged', 'Poprzednie wartości są już odtworzone.' );
				continue;
			}
			$recoverable_mixture = 'applying' === $record['status'] && self::known_mixture( $current, $record['before'], $record['after'] );
			if ( ! self::owns_lock( $lock ) || is_wp_error( $post ) || (int) $post->ID !== $record['post_id'] || ( $current !== $record['after'] && ! $recoverable_mixture ) ) {
				$errors = true;
				$results[] = self::page_result( $record['path'], $record['post_id'], 'conflict', 'Bieżące wartości różnią się od wdrożonej kopii albo zmienił się adres/uprawnienia. Nie nadpisano późniejszej edycji.' );
				continue;
			}
			$batch['pages'][ $number ]['status'] = 'rolling_back';
			if ( ! self::save_batch( $batch ) ) {
				$errors = true;
				$results[] = self::page_result( $record['path'], $record['post_id'], 'failed', 'Nie udało się zapisać stanu kopii przed przywracaniem. Przerwano dalszą operację.' );
				break;
			}
			$written = self::write_state( $record['post_id'], $record['before'], true );
			if ( is_wp_error( $written ) ) {
				$errors = true;
				$observed = self::capture_state( $record['post_id'], array_keys( $record['before']['fields'] ) );
				if ( self::known_mixture( $observed, $record['before'], $record['after'] ) ) {
					$batch['pages'][ $number ]['status'] = 'recovery_needed';
					$batch['pages'][ $number ]['after']  = $observed;
					$advice = ' Kopia została zachowana.';
				} else {
					$batch['pages'][ $number ]['status']   = 'conflict_needs_review';
					$batch['pages'][ $number ]['observed'] = $observed;
					$advice = ' Pojawiły się wartości spoza kopii. Automatyczne przywracanie jest wyłączone; wymagane jest ręczne porównanie.';
				}
				$results[] = self::page_result( $record['path'], $record['post_id'], 'failed', 'Nie udało się w pełni przywrócić strony: ' . $written->get_error_message() . $advice );
			} else {
				++$restored;
				$batch['pages'][ $number ]['status'] = 'rolled_back';
				$results[] = self::page_result( $record['path'], $record['post_id'], 'rolled_back', 'Przywrócono poprzednie wartości i dokładną obecność metadanych.' );
			}
			if ( ! self::save_batch( $batch ) ) {
				$errors = true;
				$results[] = self::page_result( $record['path'], $record['post_id'], 'warning', 'Nie udało się zapisać postępu przywracania. Przerwano dalszą operację; sprawdź stronę.' );
				break;
			}
		}
		$results = self::report_unprocessed( $batch['pages'], $results );
		$batch['status'] = $errors ? 'partial_rollback' : 'rolled_back';
		$saved = self::save_batch( $batch );
		$message = sprintf( 'Przywrócono %d stron. %s', $restored, $errors ? 'Pozostałe wyniki wymagają sprawdzenia.' : 'Kopia pozostaje w historii.' );
		if ( ! $saved ) {
			$batch['status'] = 'partial_rollback';
			$message .= ' Nie udało się zapisać końcowego statusu kopii.';
		}
		return self::operation_result( $batch_id, $batch['status'], $results, $message );
	}

	private static function report_unprocessed( $records, $results ) {
		$reported = array_column( $results, 'path' );
		foreach ( $records as $record ) {
			if ( ! in_array( $record['path'], $reported, true ) ) {
				$results[] = self::page_result( $record['path'], $record['post_id'], 'skipped', 'Nie podjęto operacji po przerwaniu wcześniejszego kroku. Sprawdź poprzedni błąd; wartości tej strony nie były zapisywane w tym kroku.' );
			}
		}
		return $results;
	}

	private static function page_result( $path, $post_id, $status, $message ) {
		return array( 'path' => $path, 'post_id' => (int) $post_id, 'status' => $status, 'message' => $message );
	}

	private static function operation_result( $batch_id, $status, $pages, $message ) {
		return array( 'batch_id' => $batch_id, 'status' => $status, 'pages' => $pages, 'message' => $message );
	}

	/** Elementor content only; integration/form/webhook settings are omitted. */
	private static function elementor_text( $raw ) {
		$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
		if ( ! is_array( $data ) ) {
			return array();
		}
		$result = array();
		$keys   = array( 'title', 'title_text', 'editor', 'text', 'description', 'button_text', 'heading', 'caption', 'alt', 'item_title', 'item_description', 'testimonial_content', 'testimonial_name', 'testimonial_job', 'tab_title', 'tab_content', 'accordion_title', 'accordion_content' );
		$walk = function ( $nodes, $depth ) use ( &$walk, &$result, $keys ) {
			if ( $depth > 30 || count( $result ) > 3000 ) {
				return;
			}
			foreach ( $nodes as $node ) {
				if ( ! is_array( $node ) ) {
					continue;
				}
				$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
				$text     = array();
				foreach ( $keys as $key ) {
					if ( isset( $settings[ $key ] ) && is_string( $settings[ $key ] ) ) {
						$text[ $key ] = $settings[ $key ];
					}
				}
				if ( $text ) {
					$result[] = array(
						'id' => isset( $node['id'] ) ? sanitize_key( $node['id'] ) : '',
						'widget' => isset( $node['widgetType'] ) ? sanitize_key( $node['widgetType'] ) : '',
						'text' => $text,
					);
				}
				if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
					$walk( $node['elements'], $depth + 1 );
				}
			}
		};
		$walk( $data, 0 );
		return $result;
	}
}
