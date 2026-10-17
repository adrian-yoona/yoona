<?php
/** Presentation-only removal of the reviewed, repeated Divi contact section. */
defined( 'ABSPATH' ) || exit;

final class Yoona_SEO_Content_Cleanup {

	const MAX_SOURCE_BYTES = 5242880;

	/**
	 * Render the complete source, then suppress only the reviewed section output.
	 *
	 * Divi must execute every shortcode to keep its section/module counters and
	 * generated styles intact. No source text or stored content is rewritten.
	 * When a hero context is supplied, retain its boolean fallback contract.
	 */
	public static function the_content( $hero_context = false ) {
		$context = self::context();
		if ( false === $context ) {
			return self::render( $hero_context );
		}
		$post_id = $context['post_id'];
		$filter = static function ( $output, $tag, $attributes, $match ) use ( $context, $post_id ) {
			if ( 'et_pb_section' !== $tag || $post_id !== (int) get_the_ID()
				|| $post_id !== (int) get_queried_object_id() || ! is_array( $match )
				|| ! isset( $match[0] ) || ! is_string( $match[0] )
				|| ! self::matches( $match[0], $context['render_sha256'] ) ) {
				return $output;
			}
			return '';
		};
		add_filter( 'do_shortcode_tag', $filter, 10, 4 );
		try {
			return self::render( $hero_context );
		} finally {
			remove_filter( 'do_shortcode_tag', $filter, 10 );
		}
	}

	/** Admit typography variants only after the stored section hash is verified. */
	private static function context() {
		$post_id = (int) get_queried_object_id();
		$post = get_post( $post_id );
		if ( ! $post || ! is_singular( array( 'page', 'post' ) ) || is_front_page()
			|| $post_id !== (int) get_the_ID() || 'publish' !== $post->post_status
			|| '' !== (string) $post->post_password || ! is_string( $post->post_content )
			|| strlen( $post->post_content ) > self::MAX_SOURCE_BYTES ) {
			return false;
		}
		// The reviewed repeated component occurs on pages, never on project posts.
		if ( 'page' !== $post->post_type ) {
			return false;
		}
		$path = '/' . trim( (string) get_page_uri( $post_id ), '/' ) . '/';
		$data = Yoona_SEO_Design::data();
		if ( ! isset( $data['removed_contact_sections'][ $path ]['section_sha256'] )
			|| ! is_array( $data['removed_contact_sections'][ $path ]['section_sha256'] ) ) {
			return false;
		}
		$expected = $data['removed_contact_sections'][ $path ]['section_sha256'];
		preg_match_all( '/\[et_pb_section(?:\s|\])[\s\S]*?\[\/et_pb_section\]/', $post->post_content, $sections );
		$render_hashes = array();
		foreach ( $sections[0] as $section ) {
			if ( self::matches( $section, $expected ) ) {
				$render_hashes[] = hash( 'sha256', $section );
				$render_hashes[] = hash( 'sha256', wptexturize( $section ) );
			}
		}
		return $render_hashes ? array(
			'post_id' => $post_id,
			'render_sha256' => array_values( array_unique( $render_hashes ) ),
		) : false;
	}

	private static function matches( $section, $expected_hashes ) {
		$actual = hash( 'sha256', $section );
		foreach ( $expected_hashes as $expected ) {
			if ( is_string( $expected ) && 1 === preg_match( '/\A[a-f0-9]{64}\z/', $expected )
				&& hash_equals( $expected, $actual ) ) {
				return true;
			}
		}
		return false;
	}

	private static function render( $hero_context ) {
		if ( is_array( $hero_context ) ) {
			return Yoona_SEO_Offer_Hero::the_content( $hero_context );
		}
		the_content();
		return false;
	}
}
