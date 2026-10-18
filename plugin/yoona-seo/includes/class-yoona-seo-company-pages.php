<?php
/** Scoped presentation of company pages, retaining the complete Divi tree. */
defined( 'ABSPATH' ) || exit;

final class Yoona_SEO_Company_Pages {

	const MAX_SOURCE_BYTES = 5242880;
	private static $data;

	public static function data() {
		if ( null === self::$data ) {
			$file = dirname( __DIR__ ) . '/content/company-pages.json';
			$value = is_readable( $file ) ? json_decode( file_get_contents( $file ), true ) : array();
			self::$data = is_array( $value ) ? $value : array();
		}
		return self::$data;
	}

	/** Validate stored modules before deriving WordPress's typography variants. */
	public static function context( $post_id ) {
		if ( class_exists( 'Yoona_SEO_Divi_Editor' ) && Yoona_SEO_Divi_Editor::builder_request() ) return false;
		$post_id = (int) $post_id;
		$post = get_post( $post_id );
		if ( ! Yoona_SEO_Design::enabled() || ! $post || 'page' !== $post->post_type
			|| 'publish' !== $post->post_status || '' !== (string) $post->post_password
			|| ! is_page() || is_front_page() || is_admin() || is_preview()
			|| $post_id !== (int) get_queried_object_id() || strlen( $post->post_content ) > self::MAX_SOURCE_BYTES ) {
			return false;
		}
		foreach ( array( 'et_fb', 'et_bfb', 'et_pb_preview', 'elementor-preview' ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) return false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only exclusion.
		}
		$path = '/' . trim( (string) get_page_uri( $post_id ), '/' ) . '/';
		$data = self::data();
		if ( ! in_array( $path, array( '/o-nas/', '/kontakt/' ), true ) || ! isset( $data[ $path ] ) ) return false;
		$context = array( 'post_id' => $post_id, 'path' => $path, 'kind' => '/kontakt/' === $path ? 'contact' : 'about', 'modules' => array() );
		foreach ( array( 'omit', 'annotate' ) as $action ) {
			foreach ( isset( $data[ $path ][ $action ] ) ? $data[ $path ][ $action ] : array() as $definition ) {
				if ( ! in_array( $definition['tag'], array( 'et_pb_section', 'et_pb_row', 'et_pb_code' ), true )
					|| ! preg_match( '/\A[a-z][a-z-]{0,35}\z/', $definition['role'] ) ) continue;
				$tag = $definition['tag'];
				if ( ! preg_match_all( '/' . get_shortcode_regex( array( $tag ) ) . '/s', $post->post_content, $matches, PREG_SET_ORDER ) ) continue;
				foreach ( $matches as $match ) {
					if ( ! in_array( hash( 'sha256', $match[0] ), $definition['sha256'], true ) ) continue;
					foreach ( array_unique( array( $match[0], wptexturize( $match[0] ) ) ) as $variant ) {
						$context['modules'][ $tag ][ hash( 'sha256', $variant ) ] = array( 'action' => $action, 'role' => $definition['role'] );
					}
				}
			}
		}
		return $context;
	}

	/** Render every original callback once; never move media or rewrite the DB. */
	public static function render( $context ) {
		$result = array( 'html' => '', 'hero_omitted' => false, 'roles' => array() );
		$post_id = is_array( $context ) && isset( $context['post_id'] ) ? (int) $context['post_id'] : 0;
		$filter = static function ( $output, $tag, $attributes, $match ) use ( $context, $post_id, &$result ) {
			if ( ! $post_id || $post_id !== (int) get_the_ID() || $post_id !== (int) get_queried_object_id()
				|| ! isset( $context['modules'][ $tag ], $match[0] ) || ! is_string( $match[0] ) ) return $output;
			$hash = hash( 'sha256', $match[0] );
			if ( ! isset( $context['modules'][ $tag ][ $hash ] ) ) $hash = hash( 'sha256', unescape_invalid_shortcodes( $match[0] ) );
			// A nested do_shortcode() pass pads core's literal-bracket entities.
			// Normalize only those two proven core escapes for hash comparison;
			// the stored source was already approved above. Never rerun the HTML.
			if ( ! isset( $context['modules'][ $tag ][ $hash ] ) ) {
				$hash = hash( 'sha256', strtr( unescape_invalid_shortcodes( $match[0] ), array( '&#091;' => '[', '&#093;' => ']' ) ) );
			}
			if ( ! isset( $context['modules'][ $tag ][ $hash ] ) ) return $output;
			$module = $context['modules'][ $tag ][ $hash ];
			$result['roles'][] = $module['role'];
			if ( 'omit' === $module['action'] ) {
				$result['hero_omitted'] = true;
				return '';
			}
			// The old closing CTA is not the page title. Keep its words and styles.
			if ( 'about' === $context['kind'] && 'closing' === $module['role'] ) {
				$output = preg_replace( '~(</?)h1(?=[\s>])~i', '$1h2', $output );
			}
			return self::add_class( $output, 'yn-company-' . $module['role'] );
		};
		add_filter( 'do_shortcode_tag', $filter, 20, 4 );
		ob_start();
		try {
			Yoona_SEO_Content_Cleanup::the_content();
			$result['html'] = ob_get_contents();
		} finally {
			ob_end_clean();
			remove_filter( 'do_shortcode_tag', $filter, 20 );
		}
		return $result;
	}

	/** Add one class to the existing module root without changing its ancestry. */
	private static function add_class( $html, $class ) {
		if ( ! is_string( $html ) || ! preg_match( '~\A\s*<(?:div|section)\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*>~i', $html, $root ) ) return $html;
		$opening = $root[0];
		if ( preg_match( '~\bclass\s*=\s*(["\'])(.*?)\1~is', $opening ) ) {
			$opening = preg_replace_callback( '~\bclass\s*=\s*(["\'])(.*?)\1~is', static function ( $match ) use ( $class ) {
				return 'class=' . $match[1] . $match[2] . ' ' . esc_attr( $class ) . $match[1];
			}, $opening, 1 );
		} else {
			$opening = substr( $opening, 0, -1 ) . ' class="' . esc_attr( $class ) . '">';
		}
		return substr_replace( $html, $opening, 0, strlen( $root[0] ) );
	}
}
