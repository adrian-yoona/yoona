<?php
/** Hash-guarded presentation of the existing offer-page photograph. */
defined( 'ABSPATH' ) || exit;

final class Yoona_SEO_Offer_Hero {

	const MAX_SOURCE_BYTES = 5242880;

	/** Return a curated hero only for an unchanged, public service-page header. */
	public static function context( $post_id ) {
		$post_id = (int) $post_id;
		$post = get_post( $post_id );
		if ( ! $post || 'page' !== $post->post_type || 'publish' !== $post->post_status
			|| '' !== (string) $post->post_password || $post_id === (int) get_option( 'page_on_front' )
			|| ! is_page() || is_front_page() || $post_id !== (int) get_queried_object_id() ) {
			return false;
		}
		$path = '/' . trim( (string) get_page_uri( $post_id ), '/' ) . '/';
		$data = Yoona_SEO_Design::data();
		if ( ! isset( $data['offer_heroes'][ $path ] ) || ! is_array( $data['offer_heroes'][ $path ] ) ) {
			return false;
		}
		$definition = $data['offer_heroes'][ $path ];
		if ( empty( $definition['image'] ) || empty( $definition['lead'] )
			|| '' === Yoona_SEO_Design::url( $definition['image'] ) ) {
			return false;
		}
		$body = self::prepare_content( $post->post_content, $definition );
		if ( false === $body ) {
			return false;
		}
		// Core typography runs before shortcodes. Derive its exact render variant
		// only AFTER the raw, stored section has passed the curated source hash.
		$section = substr( $post->post_content, 0, strlen( $post->post_content ) - strlen( $body ) );
		$definition['render_sha256'] = array_values( array_unique( array(
			hash( 'sha256', $section ),
			hash( 'sha256', wptexturize( $section ) ),
		) ) );
		$definition['post_id'] = $post_id;
		$definition['path'] = $path;
		$definition['has_body_h1'] = 1 === preg_match( '/<h1(?:\s|>)/i', $body );
		return $definition;
	}

	/**
	 * Inspect the exact first section without changing any stored content.
	 * The returned body is used for heading semantics only, never for rendering.
	 */
	public static function prepare_content( $source, $definition ) {
		if ( ! is_string( $source ) || strlen( $source ) > self::MAX_SOURCE_BYTES
			|| 1 !== preg_match( '/\A\[et_pb_section(?:\s|\])/', $source ) ) {
			return false;
		}
		$closing = '[/et_pb_section]';
		$end = strpos( $source, $closing );
		if ( false === $end ) {
			return false;
		}
		$end += strlen( $closing );
		$section = substr( $source, 0, $end );
		return self::matches( $section, $definition ) ? substr( $source, $end ) : false;
	}

	private static function matches( $section, $definition, $hash_key = 'section_sha256' ) {
		if ( ! is_array( $definition ) || ! isset( $definition[ $hash_key ] )
			|| ! is_array( $definition[ $hash_key ] ) ) {
			return false;
		}
		$actual = hash( 'sha256', $section );
		foreach ( $definition[ $hash_key ] as $expected ) {
			if ( is_string( $expected ) && 1 === preg_match( '/\A[a-f0-9]{64}\z/', $expected )
				&& hash_equals( $expected, $actual ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Render the complete original source through the standard WordPress chain.
	 * Hide the known banner only AFTER its shortcode callback has rendered it,
	 * so Divi retains its section/module indices and corresponding generated CSS.
	 *
	 * Returns whether the old banner was removed. Callers can buffer this output
	 * and keep the previous heading if a third-party filter changed the shortcode.
	 */
	public static function the_content( $context ) {
		$removed = false;
		$post_id = isset( $context['post_id'] ) ? (int) $context['post_id'] : 0;
		$filter = static function ( $output, $tag, $attributes, $match ) use ( $context, $post_id, &$removed ) {
			if ( $removed || ! $post_id || 'et_pb_section' !== $tag
				|| $post_id !== (int) get_the_ID() || $post_id !== (int) get_queried_object_id()
				|| ! is_array( $match ) || ! isset( $match[0] ) || ! is_string( $match[0] )
				|| ! self::matches( $match[0], $context, 'render_sha256' ) ) {
				return $output;
			}
			$removed = true;
			return '';
		};
		add_filter( 'do_shortcode_tag', $filter, 10, 4 );
		try {
			the_content();
		} finally {
			remove_filter( 'do_shortcode_tag', $filter, 10 );
		}
		return $removed;
	}
}
