<?php
/** Presentation-only removal of the retired Divi contact component. */
defined( 'ABSPATH' ) || exit;

final class Yoona_SEO_Content_Cleanup {

	const MAX_SOURCE_BYTES = 5242880;

	public static function register() {
		// Divi can render directly, through WP shortcodes, or from cached HTML.
		add_filter( 'do_shortcode_tag', array( __CLASS__, 'filter_shortcode' ), 1000, 4 );
		add_filter( 'et_module_shortcode_output', array( __CLASS__, 'filter_divi' ), 1000, 3 );
		add_filter( 'the_content', array( __CLASS__, 'filter_content' ), PHP_INT_MAX );
	}

	/** Execute every module first so Divi's counters and generated styles survive. */
	public static function filter_shortcode( $output, $tag, $attributes, $match ) {
		if ( 'et_pb_section' === $tag && self::eligible() ) {
			// Match the rendered section, including expanded global/library modules.
			return self::filter_content( $output );
		}
		return $output;
	}

	/** Divi's own renderer does not always pass through do_shortcode_tag. */
	public static function filter_divi( $output, $render_slug, $module = null ) {
		return 'et_pb_section' === $render_slug ? self::filter_content( $output ) : $output;
	}

	/** Remove only complete matching section ranges; never reserialize the page. */
	public static function filter_content( $content ) {
		if ( ! self::eligible() || ! is_string( $content ) || strlen( $content ) > self::MAX_SOURCE_BYTES
			|| ! self::has_contact_image( $content ) ) {
			return $content;
		}
		// Treat comments and raw-text elements as opaque. Quoted > characters
		// must not end a tag, and nested divs must not truncate a Divi section.
		$pattern = '~<!--[\s\S]*?-->|<(script|style|textarea|title|template)\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*?>[\s\S]*?</\1\s*>|</?[a-z][a-z0-9:-]*\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*>~i';
		if ( ! preg_match_all( $pattern, $content, $tokens, PREG_OFFSET_CAPTURE ) ) {
			return $content;
		}
		$stack = array();
		$ranges = array();
		foreach ( $tokens[0] as $token ) {
			if ( ! preg_match( '~\A<(/?)(div|section)\b~i', $token[0], $tag ) ) {
				continue;
			}
			$name = strtolower( $tag[2] );
			if ( '' === $tag[1] ) {
				$attributes = wp_kses_hair( substr( $token[0], strlen( $tag[0] ), -1 ), wp_allowed_protocols() );
				$classes = isset( $attributes['class']['value'] ) ? preg_split( '/\s+/', $attributes['class']['value'] ) : array();
				$stack[] = array( 'tag' => $name, 'start' => $token[1], 'section' => in_array( 'et_pb_section', $classes, true ), 'nested_section' => false );
				continue;
			}
			if ( ! $stack || end( $stack )['tag'] !== $name ) {
				// Broken markup is left untouched rather than guessing a boundary.
				$stack = array();
				continue;
			}
			$open = array_pop( $stack );
			$end = $token[1] + strlen( $token[0] );
			if ( $open['section'] && ! $open['nested_section']
				&& self::is_contact( substr( $content, $open['start'], $end - $open['start'] ) ) ) {
				$ranges[] = array( $open['start'], $end - $open['start'] );
			}
			if ( $open['section'] ) {
				foreach ( $stack as &$ancestor ) {
					$ancestor['nested_section'] = true;
				}
				unset( $ancestor );
			}
		}
		// Descending offsets keep all retained HTML byte-identical.
		usort( $ranges, static function ( $a, $b ) { return $b[0] <=> $a[0]; } );
		foreach ( $ranges as $range ) {
			$content = substr_replace( $content, '', $range[0], $range[1] );
		}
		return $content;
	}

	/** Not tied to a path, a source hash, or the current Theme Builder post. */
	private static function eligible() {
		if ( ! Yoona_SEO_Design::enabled() || is_admin() || is_feed() || is_embed() || is_preview()
			|| ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() )
			|| ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() )
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) ) {
			return false;
		}
		foreach ( array( 'et_fb', 'et_bfb', 'et_pb_preview', 'elementor-preview' ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only builder exclusion.
				return false;
			}
		}
		$queried = get_queried_object();
		if ( $queried instanceof WP_Post && ( 'publish' !== $queried->post_status || '' !== (string) $queried->post_password ) ) {
			return false;
		}
		return is_singular( array( 'page', 'post' ) ) || is_home() || is_front_page() || is_archive() || is_search() || is_404();
	}

	private static function has_contact_image( $html ) {
		return is_string( $html ) && 1 === preg_match( '~(?:/|%2f)agencja-reklamowa-olsztyn(?:-1)?(?:-\d+x\d+)?\.(?:png|jpe?g|webp|avif)(?:\.(?:webp|avif))?(?=[?\#&\s"\'<>\)\]]|$)~i', $html );
	}

	private static function is_contact( $html ) {
		if ( ! is_string( $html ) || strlen( $html ) > self::MAX_SOURCE_BYTES || ! self::has_contact_image( $html ) ) {
			return false;
		}
		// Comments, scripts and serialized/template examples are not evidence
		// that the retired photograph or heading is actually displayed.
		$html = preg_replace( '~<!--[\s\S]*?-->|<(script|style|textarea|title|template)\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*?>[\s\S]*?</\1\s*>~i', '', $html );
		if ( ! self::has_rendered_contact_image( $html ) ) {
			return false;
		}
		$text = html_entity_decode( wp_strip_all_tags( preg_replace( '/<[^>]*>/', ' ', $html ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', $text );
		// The paired photo and contact labels identify this component. Paragraphs,
		// Divi settings, typography, phone numbers and URL paths may all change.
		if ( 1 === preg_match( '/masz\s+pytanie/iu', $text ) && 1 === preg_match( '/\bkontakt\b/iu', $text ) ) {
			return true;
		}
		// Also recognize an edited question heading using both original offices.
		$decoded = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return false !== stripos( $decoded, 'wyceny@yoona.pl' ) && false !== stripos( $decoded, 'biuro@yoona.pl' )
			&& ( 1 === preg_match( '/\bkontakt\b/iu', $text ) || ( 1 === preg_match( '/olsztyn/iu', $text ) && 1 === preg_match( '/ostr[oó]da/iu', $text ) ) );
	}

	private static function has_rendered_contact_image( $html ) {
		preg_match_all( '~<[a-z][a-z0-9:-]*\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*>~i', $html, $tags );
		foreach ( $tags[0] as $tag ) {
			preg_match( '~\A<([a-z][a-z0-9:-]*)\b~i', $tag, $name );
			$attributes = wp_kses_hair( substr( $tag, strlen( $name[0] ), -1 ), wp_allowed_protocols() );
			if ( in_array( strtolower( $name[1] ), array( 'img', 'source' ), true ) ) {
				foreach ( array( 'src', 'srcset', 'data-src', 'data-srcset', 'data-lazy-src', 'data-lazy-srcset' ) as $key ) {
					if ( isset( $attributes[ $key ]['value'] ) && self::has_contact_image( $attributes[ $key ]['value'] ) ) {
						return true;
					}
				}
			}
			if ( isset( $attributes['style']['value'] ) && preg_match( '/(?:\A|;)\s*background(?:-image)?\s*:/i', $attributes['style']['value'] )
				&& self::has_contact_image( $attributes['style']['value'] ) ) {
				return true;
			}
		}
		return false;
	}

	/** Preserve the offer hero's existing boolean fallback contract. */
	public static function the_content( $hero_context = false ) {
		if ( is_array( $hero_context ) ) {
			return Yoona_SEO_Offer_Hero::the_content( $hero_context );
		}
		the_content();
		return false;
	}
}
