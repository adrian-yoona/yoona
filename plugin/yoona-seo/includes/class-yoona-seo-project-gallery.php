<?php
/** Native, read-only pagination and photographs for the public project index. */
defined( 'ABSPATH' ) || exit;

final class Yoona_SEO_Project_Gallery {

	const QUERY_FLAG = 'yoona_project_gallery';
	const CONTACT_IMAGES = array( 'agencja-reklamowa-olsztyn-1.png', 'agencja-reklamowa-olsztyn.png' );
	private static $source_pattern;

	public static function register() {
		add_action( 'pre_get_posts', array( __CLASS__, 'tune_query' ), 20 );
		add_filter( 'posts_where', array( __CLASS__, 'photograph_predicate' ), 20, 2 );
	}

	/** Alter the real posts-index query, keeping WordPress's count and pagination. */
	public static function tune_query( $query ) {
		if ( ! $query->is_main_query() || ! $query->is_home() || ! self::eligible( $query ) ) {
			return;
		}
		$query->set( self::QUERY_FLAG, true );
		$query->set( 'posts_per_page', 30 );
		$query->set( 'posts_per_archive_page', 30 );
		$query->set( 'post_type', 'post' );
		$query->set( 'post_status', 'publish' );
		$query->set( 'has_password', false );
		$query->set( 'ignore_sticky_posts', true );
		$query->set( 'orderby', array( 'date' => 'DESC', 'ID' => 'DESC' ) );
		$query->set( 'no_found_rows', false );
	}

	private static function eligible( $query ) {
		if ( ! Yoona_SEO_Design::enabled() || is_admin() || $query->is_feed() || $query->is_embed() || $query->is_preview()
			|| ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() )
			|| ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() )
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( function_exists( 'is_customize_preview' ) && is_customize_preview() )
			|| 'page' !== get_option( 'show_on_front' ) ) {
			return false;
		}
		foreach ( array( 'et_fb', 'et_bfb', 'et_pb_preview', 'elementor-preview' ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route exclusion.
				return false;
			}
		}
		$page = get_post( (int) get_option( 'page_for_posts' ) );
		return $page && 'page' === $page->post_type && 'publish' === $page->post_status && '' === (string) $page->post_password;
	}

	/**
	 * Exclude entries without photographs before SQL_CALC_FOUND_ROWS is evaluated.
	 * No PHP array slicing, second full-content query, or persistent metadata cache.
	 */
	public static function photograph_predicate( $where, $query ) {
		if ( ! $query->get( self::QUERY_FLAG ) || ! $query->is_main_query() || ! $query->is_home() ) {
			return $where;
		}
		global $wpdb;
		$thumbnail = $wpdb->prepare(
			"EXISTS (SELECT 1 FROM {$wpdb->postmeta} AS yn_project_thumb INNER JOIN {$wpdb->posts} AS yn_project_attachment ON yn_project_attachment.ID = CAST(yn_project_thumb.meta_value AS UNSIGNED) WHERE yn_project_thumb.post_id = {$wpdb->posts}.ID AND yn_project_thumb.meta_key = '_thumbnail_id' AND yn_project_thumb.meta_value REGEXP '^[1-9][0-9]*$' AND yn_project_attachment.post_type = 'attachment' AND yn_project_attachment.post_status IN ('inherit', 'publish') AND yn_project_attachment.post_mime_type LIKE 'image/%%' AND LOWER(yn_project_attachment.guid) NOT LIKE %s AND LOWER(yn_project_attachment.guid) NOT LIKE %s AND (yn_project_attachment.guid REGEXP '^https?://' OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} AS yn_project_file WHERE yn_project_file.post_id = yn_project_attachment.ID AND yn_project_file.meta_key = '_wp_attached_file' AND yn_project_file.meta_value <> '')) AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} AS yn_contact_file WHERE yn_contact_file.post_id = yn_project_attachment.ID AND yn_contact_file.meta_key = '_wp_attached_file' AND (LOWER(yn_contact_file.meta_value) LIKE %s OR LOWER(yn_contact_file.meta_value) LIKE %s)))",
			'%' . $wpdb->esc_like( self::CONTACT_IMAGES[0] ) . '%',
			'%' . $wpdb->esc_like( self::CONTACT_IMAGES[1] ) . '%',
			'%' . $wpdb->esc_like( self::CONTACT_IMAGES[0] ) . '%',
			'%' . $wpdb->esc_like( self::CONTACT_IMAGES[1] ) . '%'
		);
		// This exact src expression is also used by image(); an arbitrary <img>,
		// external image or the retired contact photograph does not qualify.
		$embedded = $wpdb->prepare(
			"REPLACE(REPLACE(LOWER({$wpdb->posts}.post_content), %s, ''), %s, '') REGEXP %s",
			self::CONTACT_IMAGES[0],
			self::CONTACT_IMAGES[1],
			self::source_pattern()
		);
		return $where . ' AND (' . $thumbnail . ' OR ' . $embedded . ')';
	}

	/** Only an existing media image or the first real embedded project photo. */
	public static function image( $post_id, $eager = false ) {
		$post = get_post( (int) $post_id );
		if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== (string) $post->post_password ) {
			return '';
		}
		$attributes = array( 'class' => 'yn-project-image', 'alt' => '', 'loading' => $eager ? 'eager' : 'lazy', 'decoding' => 'async' );
		$thumbnail_id = (int) get_post_thumbnail_id( $post->ID );
		if ( $thumbnail_id ) {
			$attachment = get_post( $thumbnail_id );
			$url = (string) wp_get_attachment_url( $thumbnail_id );
			if ( $attachment && 'attachment' === $attachment->post_type && in_array( $attachment->post_status, array( 'inherit', 'publish' ), true )
				&& wp_attachment_is_image( $thumbnail_id ) && $url !== '' && ! self::is_contact_image( $url ) ) {
				$image = wp_get_attachment_image( $thumbnail_id, 'large', false, $attributes );
				if ( $image ) {
					return $image;
				}
			}
		}
		$source = (string) $post->post_content;
		// Matching the same conservative URL grammar as SQL keeps visible cards
		// and the native total in agreement; no shortcode or content is executed.
		if ( ! preg_match_all( '~' . str_replace( '~', '\\~', self::source_pattern() ) . '~i', $source, $matches ) ) {
			return '';
		}
		foreach ( $matches[5] as $source_url ) {
			$url = html_entity_decode( $source_url, ENT_QUOTES, 'UTF-8' );
			if ( self::is_contact_image( $url ) ) {
				continue;
			}
			$url = Yoona_SEO_Design::url( $url );
			if ( '' === $url ) {
				continue;
			}
			$html = '<img src="' . esc_url( $url ) . '"';
			foreach ( $attributes as $name => $value ) {
				$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
			}
			return $html . '>';
		}
		return '';
	}

	private static function is_contact_image( $url ) {
		foreach ( self::CONTACT_IMAGES as $image ) {
			if ( false !== stripos( $url, $image ) ) {
				return true;
			}
		}
		return false;
	}

	/** Portable MySQL/MariaDB and PHP expression, limited to site upload photos. */
	private static function source_pattern() {
		if ( null === self::$source_pattern ) {
			$home = wp_parse_url( home_url( '/' ) );
			$authority = isset( $home['host'] ) ? strtolower( $home['host'] ) : '';
			if ( isset( $home['port'] ) ) {
				$authority .= ':' . (int) $home['port'];
			}
			$hosts = array_unique( array_filter( array( 'yoona.pl', 'www.yoona.pl', $authority ) ) );
			$hosts = array_map( static function ( $host ) { return preg_quote( $host ); }, $hosts );
			$paths = array( '/wp-content/uploads/' );
			if ( function_exists( 'wp_get_upload_dir' ) ) {
				$uploads = wp_get_upload_dir();
				$upload_path = isset( $uploads['baseurl'] ) ? wp_parse_url( $uploads['baseurl'], PHP_URL_PATH ) : '';
				if ( is_string( $upload_path ) && '/' === substr( $upload_path, 0, 1 ) ) {
					$paths[] = trailingslashit( $upload_path );
				}
			}
			$paths = array_map( static function ( $path ) { return preg_quote( strtolower( $path ) ); }, array_unique( $paths ) );
			// Segments permit normal media filenames without traversal, escapes or
			// markup. Featured images remain available for other media filenames.
			$segment = '[a-z0-9_()+~@!$&=,;-]+([.][a-z0-9_()+~@!$&=,;-]+)*';
			$quotes = '["' . chr( 39 ) . ']';
			$url = '((https?:)?//(' . implode( '|', $hosts ) . '))?(' . implode( '|', $paths ) . ')(' . $segment . '/)*' . $segment . '[.](jpe?g|png|webp|avif|gif)([?#][^"' . chr( 39 ) . '<>[:space:]]*)?';
			// Capture 5 is the src URL, after the complete supported tag prefix.
			self::$source_pattern = '(<img[[:space:]]+([^>]*[[:space:]])?|\\[et_pb_image[[:space:]]+([^]]*[[:space:]])?)src[[:space:]]*=[[:space:]]*(' . $quotes . ')(' . $url . ')' . $quotes;
		}
		return self::$source_pattern;
	}
}
