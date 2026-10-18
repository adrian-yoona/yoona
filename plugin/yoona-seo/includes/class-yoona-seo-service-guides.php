<?php
/** Curated, reversible service content; stored Divi pages remain editable. */
defined( 'ABSPATH' ) || exit;
final class Yoona_SEO_Service_Guides {
    private static $data;
    public static function data() {
        if ( null === self::$data ) {
            $file = dirname( __DIR__ ) . '/content/service-guides.json';
            $decoded = is_readable( $file ) ? json_decode( file_get_contents( $file ), true ) : array();
            self::$data = is_array( $decoded ) ? $decoded : array();
        }
        return self::$data;
    }
    public static function context( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || ! is_page() || is_front_page() || (int) $post_id !== (int) get_queried_object_id()
            || 'page' !== $post->post_type || 'publish' !== $post->post_status || '' !== (string) $post->post_password ) return false;
        $owned_hash = get_post_meta( $post_id, '_yoona_growth_content_sha256', true );
        if ( get_post_meta( $post_id, '_yoona_growth_owned', true ) && ( ! is_string( $owned_hash )
            || ! preg_match( '/\A[a-f0-9]{64}\z/', $owned_hash ) || ! hash_equals( $owned_hash, hash( 'sha256', (string) $post->post_content ) ) ) ) return false;
        $path = '/' . trim( get_page_uri( $post_id ), '/' ) . '/';
        $guides = self::data();
        if ( empty( $guides[ $path ]['title'] ) || empty( $guides[ $path ]['lead'] ) ) return false;
        if ( get_post_meta( $post_id, '_yoona_growth_owned', true ) && $guides[ $path ]['title'] !== $post->post_title ) return false;
        $guide = $guides[ $path ];
        $design = Yoona_SEO_Design::data();
        $image_path = isset( $guide['image_source_path'] ) ? $guide['image_source_path'] : $path;
        $hero_image = ! empty( $guide['hero_image'] ) ? $guide['hero_image'] : ( isset( $design['offer_heroes'][ $image_path ]['image'] ) ? $design['offer_heroes'][ $image_path ]['image'] : '' );
        if ( '' === Yoona_SEO_Design::url( $hero_image ) || '' === $hero_image ) return false;
        $guide['image'] = $hero_image;
        $guide['alt'] = ! empty( $guide['hero_alt'] ) ? $guide['hero_alt'] : $design['offer_heroes'][ $image_path ]['alt'];
        $guide['path'] = $path;
        $guide['layout_mode'] = isset( $design['offer_heroes'][ $path ]['service_intro_mode'] ) ? $design['offer_heroes'][ $path ]['service_intro_mode'] : 'replace';
        return $guide;
    }
    public static function child_definitions() {
        $design = Yoona_SEO_Design::data();
        $children = array();
        foreach ( self::data() as $path => $guide ) {
            if ( ! empty( $guide['image_source_path'] ) && isset( $design['offer_heroes'][ $guide['image_source_path'] ] ) ) {
                $children[ $path ] = $design['offer_heroes'][ $guide['image_source_path'] ];
                if ( ! empty( $guide['card_image'] ) && '' !== Yoona_SEO_Design::url( $guide['card_image'] ) ) {
                    $children[ $path ]['image'] = $guide['card_image'];
                    $children[ $path ]['alt'] = ! empty( $guide['card_alt'] ) ? $guide['card_alt'] : $children[ $path ]['alt'];
                }
            }
        }
        return $children;
    }
    public static function link( $path ) {
        if ( ! is_string( $path ) || ! preg_match( '~\A/[a-z0-9-]+/\z~', $path ) ) return '';
        $page = get_page_by_path( trim( $path, '/' ), OBJECT, 'page' );
        return $page && 'publish' === $page->post_status && '' === (string) $page->post_password ? get_permalink( $page ) : '';
    }
    /**
     * Keep the complete Divi tree: sections, rows, columns and every lower module.
     * Only a reviewed introduction may change, after its callback has run. Never
     * rebuild an offer's gallery from detached media modules.
     */
    public static function legacy_content( $post_id, $guide, $details ) {
        $result = array( 'html' => '', 'hero_applied' => false, 'intro_applied' => false );
        $post = get_post( $post_id );
        if ( ! $post || (int) $post_id !== (int) get_the_ID() || (int) $post_id !== (int) get_queried_object_id() ) return $result;
        if ( get_post_meta( $post_id, '_yoona_growth_owned', true ) ) {
            return array( 'html' => $details, 'hero_applied' => true, 'intro_applied' => true );
        }
        $hero = Yoona_SEO_Offer_Hero::context( $post_id );
        $design = Yoona_SEO_Design::data();
        $definition = isset( $guide['path'], $design['offer_heroes'][ $guide['path'] ] ) ? $design['offer_heroes'][ $guide['path'] ] : array();
        $render_hashes = array();
        $mode = isset( $definition['service_intro_mode'] ) ? $definition['service_intro_mode'] : '';
        if ( $hero && in_array( $mode, array( 'replace', 'append' ), true ) && ! empty( $definition['service_intro_sha256'] ) && is_array( $definition['service_intro_sha256'] ) ) {
            $body = Yoona_SEO_Offer_Hero::prepare_content( $post->post_content, $definition );
            if ( is_string( $body ) && preg_match( '/\A\s*\[et_pb_section(?:\s|\])/', $body ) ) {
                $start = strpos( $body, '[et_pb_section' );
                $end = strpos( $body, '[/et_pb_section]', $start );
                if ( false !== $end ) {
                    $intro = substr( $body, $start, $end + strlen( '[/et_pb_section]' ) - $start );
                    if ( in_array( hash( 'sha256', $intro ), $definition['service_intro_sha256'], true ) ) {
                        // Typography variants are accepted only after the stored
                        // introduction passed its exact, reviewed source hash.
                        $render_hashes = array_unique( array( hash( 'sha256', $intro ), hash( 'sha256', wptexturize( $intro ) ) ) );
                    }
                }
            }
        }
        $filter = static function ( $output, $tag, $attributes, $match ) use ( $post_id, $render_hashes, $details, $mode, &$result ) {
            if ( $result['intro_applied'] || 'et_pb_section' !== $tag || ! $render_hashes
                || (int) $post_id !== (int) get_the_ID() || (int) $post_id !== (int) get_queried_object_id()
                || ! isset( $match[0] ) || ! is_string( $match[0] ) ) return $output;
            // Core protects literal [] in HTML attributes before callbacks.
            // Undo only those two core escapes; the stored intro has already
            // passed its exact reviewed hash. Do not execute shortcodes again.
            if ( ! in_array( hash( 'sha256', $match[0] ), $render_hashes, true )
                && ! in_array( hash( 'sha256', unescape_invalid_shortcodes( $match[0] ) ), $render_hashes, true ) ) return $output;
            $result['intro_applied'] = true;
            // Flags and totems have media inside their original introduction.
            // Keep that whole section and add only the detailed service panels.
            return 'append' === $mode ? $output . $details : $details;
        };
        add_filter( 'do_shortcode_tag', $filter, 20, 4 );
        ob_start();
        try {
            $result['hero_applied'] = Yoona_SEO_Content_Cleanup::the_content( $hero );
            $result['html'] = ob_get_contents();
        } finally {
            ob_end_clean();
            remove_filter( 'do_shortcode_tag', $filter, 20 );
        }
        return $result;
    }
    /** Render every callback once, then retain complete media/form containers. */
    public static function legacy_media( $post_id, $guide ) {
        if ( get_post_meta( $post_id, '_yoona_growth_owned', true ) ) return array();
        ob_start();
        try { Yoona_SEO_Content_Cleanup::the_content(); $rendered = ob_get_contents(); }
        finally { ob_end_clean(); }
        $media = array();
        foreach ( self::rendered_media( $rendered ) as $item ) {
            $html = $item['html'];
            if ( preg_match( '~agencja-reklamowa-olsztyn(?:-1)?(?:-\d+x\d+)?\.(?:png|webp|avif)~i', $html ) ) continue;
            if ( 'image' === $item['kind'] && ! empty( $guide['image'] )
                && false !== strpos( $html, basename( wp_parse_url( $guide['image'], PHP_URL_PATH ) ) ) ) continue;
            $media[ hash( 'sha256', $html ) ] = $html;
        }
        return array_values( $media );
    }
    /** Balanced byte ranges preserve nested galleries and late WP image filters. */
    private static function rendered_media( $html ) {
        preg_match_all( '~<!--[\s\S]*?-->|<(script|style|textarea|template)\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*?>[\s\S]*?</\1\s*>|</?[a-z][a-z0-9:-]*\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*>~i', $html, $tokens, PREG_OFFSET_CAPTURE );
        $stack = array(); $selected = false; $media = array();
        $containers = array( 'div', 'section', 'figure', 'picture', 'video', 'audio', 'iframe', 'form' );
        $modules = array( 'et_pb_gallery', 'et_pb_image', 'et_pb_fullwidth_image', 'et_pb_slider', 'et_pb_fullwidth_slider', 'et_pb_video', 'et_pb_contact_form_container', 'wpcf7', 'wpforms-container', 'gform_wrapper' );
        foreach ( $tokens[0] as $token ) {
            if ( ! preg_match( '~\A<(/?)([a-z][a-z0-9:-]*)\b~i', $token[0], $tag ) ) continue;
            $name = strtolower( $tag[2] );
            if ( 'img' === $name && '' === $tag[1] && false === $selected ) { $media[] = array( 'html' => $token[0], 'kind' => 'native' ); continue; }
            if ( ! in_array( $name, $containers, true ) ) continue;
            if ( '' === $tag[1] ) {
                $attributes = wp_kses_hair( substr( $token[0], strlen( $tag[0] ), -1 ), wp_allowed_protocols() );
                $classes = isset( $attributes['class']['value'] ) ? preg_split( '/\s+/', $attributes['class']['value'] ) : array();
                $matched = array_intersect( $modules, $classes );
                $candidate = (bool) $matched || in_array( $name, array( 'figure', 'picture', 'video', 'audio', 'iframe', 'form' ), true );
                $stack[] = $name;
                if ( false === $selected && $candidate ) $selected = array( 'depth' => count( $stack ), 'start' => $token[1], 'kind' => array_intersect( array( 'et_pb_image', 'et_pb_fullwidth_image' ), $classes ) ? 'image' : 'native' );
            } elseif ( $stack && end( $stack ) === $name ) {
                if ( false !== $selected && count( $stack ) === $selected['depth'] ) {
                    $media[] = array( 'html' => substr( $html, $selected['start'], $token[1] + strlen( $token[0] ) - $selected['start'] ), 'kind' => $selected['kind'] );
                    $selected = false;
                }
                array_pop( $stack );
            } else { $stack = array(); $selected = false; }
        }
        return $media;
    }
}
