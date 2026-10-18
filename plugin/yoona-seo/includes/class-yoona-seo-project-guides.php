<?php
/** Evidence-based project summaries, guarded against newer editorial changes. */
defined( 'ABSPATH' ) || exit;
final class Yoona_SEO_Project_Guides {
    private static $data;
    public static function data() {
        if ( null === self::$data ) {
            $file = dirname( __DIR__ ) . '/content/project-guides.json';
            $decoded = is_readable( $file ) ? json_decode( file_get_contents( $file ), true ) : array();
            self::$data = is_array( $decoded ) ? $decoded : array();
        }
        return self::$data;
    }
    public static function context( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== (string) $post->post_password
            || ! is_singular( 'post' ) || (int) $post_id !== (int) get_queried_object_id() ) return false;
        $path = wp_parse_url( get_permalink( $post ), PHP_URL_PATH );
        $home_path = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
        if ( $home_path && 0 === strpos( $path, $home_path . '/' ) ) $path = substr( $path, strlen( $home_path ) );
        $path = '/' . trim( $path, '/' ) . '/';
        $guides = self::data();
        if ( empty( $guides[ $path ]['summary'] ) || empty( $guides[ $path ]['source_sha256'] ) ) return false;
        $guide = $guides[ $path ];
        if ( ! isset( $guide['title'] ) || $guide['title'] !== $post->post_title ) return false;
        $actual = hash( 'sha256', (string) $post->post_content );
        foreach ( $guide['source_sha256'] as $expected ) {
            if ( is_string( $expected ) && preg_match( '/\A[a-f0-9]{64}\z/', $expected ) && hash_equals( $expected, $actual ) ) {
                $guide['path'] = $path;
                return $guide;
            }
        }
        return false;
    }
    /**
     * Render the complete source once, keeping Divi parent rows, native gallery
     * captions, links, forms and later modules. Unknown/editorial sources use
     * this same preservation path without receiving stale curated project facts.
     */
    public static function original( $post_id ) {
        $result = array( 'html' => '', 'photo_count' => 0, 'has_h1' => false );
        $post = get_post( $post_id );
        if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== (string) $post->post_password
            || (int) $post_id !== (int) get_the_ID() || (int) $post_id !== (int) get_queried_object_id() ) return $result;
        ob_start();
        try {
            Yoona_SEO_Content_Cleanup::the_content();
            $result['html'] = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        // A count controls presentation only. Never use it to discard content.
        if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
            $tags = new WP_HTML_Tag_Processor( $result['html'] );
            while ( $tags->next_tag() ) {
                if ( 'IMG' === $tags->get_tag() ) ++$result['photo_count'];
                if ( 'H1' === $tags->get_tag() ) $result['has_h1'] = true;
            }
        } else {
            // Keep WordPress 6.0 compatibility; raw-text examples are not media.
            $visible = preg_replace( '~<!--[\s\S]*?-->|<(script|style|textarea|title|template)\b[^>]*>[\s\S]*?</\1\s*>~i', '', $result['html'] );
            $result['photo_count'] = preg_match_all( '~<img\b~i', $visible );
            $result['has_h1'] = 1 === preg_match( '~<h1(?:\s|>)~i', $visible );
        }
        return $result;
    }
    /** Published internal services only; no dead or guessed destination URLs. */
    public static function services( $guide ) {
        if ( ! is_array( $guide ) ) return array();
        $paths = array();
        if ( ! empty( $guide['service_path'] ) ) $paths[] = $guide['service_path'];
        if ( ! empty( $guide['related_paths'] ) && is_array( $guide['related_paths'] ) ) $paths = array_merge( $paths, $guide['related_paths'] );
        $definitions = Yoona_SEO_Service_Guides::data();
        $links = array();
        foreach ( array_unique( $paths ) as $path ) {
            $url = Yoona_SEO_Service_Guides::link( $path );
            $title = isset( $definitions[ $path ]['title'] ) ? $definitions[ $path ]['title'] : ( '/pelna-oferta/' === $path ? 'Pełna oferta' : '' );
            if ( $url && $title ) $links[] = array( 'url' => $url, 'title' => $title );
        }
        return $links;
    }
    public static function render( $guide ) {
        if ( ! is_array( $guide ) || empty( $guide['summary'] ) ) return;
        ?>
        <section class="yn-project-summary" aria-label="Zakres realizacji"><div class="yn-container yn-project-summary-inner">
          <div class="yn-project-brief"><p class="yn-eyebrow">Projekt w szczegółach</p><p class="yn-project-lead"><?php echo esc_html( $guide['summary'] ); ?></p></div>
          <div class="yn-project-facts">
          <?php foreach ( array( 'scope' => 'Co wykonaliśmy', 'materials' => 'Materiały i wykończenie' ) as $field => $label ) : if ( empty( $guide[ $field ] ) ) continue; ?><section class="yn-project-fact"><h2><?php echo esc_html( $label ); ?></h2><ul><?php foreach ( $guide[ $field ] as $fact ) : ?><li><?php echo esc_html( $fact ); ?></li><?php endforeach; ?></ul></section><?php endforeach; ?>
          </div>
        </div></section>
        <?php
    }
    /** General planning guidance is kept distinct from the completed job's facts. */
    public static function render_planning( $guide ) {
        if ( ! is_array( $guide ) || empty( $guide['planning'] ) ) return;
        $links = self::services( $guide );
        ?>
        <section class="yn-project-planning" aria-labelledby="yn-project-planning-title"><div class="yn-container yn-project-planning-inner">
          <div><p class="yn-eyebrow">Olsztyn / Ostróda / Warmia i Mazury</p><h2 id="yn-project-planning-title">Planujesz podobną realizację?</h2></div>
          <div class="yn-project-planning-copy"><p><?php echo esc_html( $guide['planning'] ); ?></p>
          <?php if ( $links ) : ?><nav class="yn-project-service-links" aria-label="Usługi związane z realizacją"><?php foreach ( $links as $link ) : ?><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['title'] ); ?><span aria-hidden="true">↗</span></a><?php endforeach; ?></nav><?php endif; ?>
          </div>
        </div></section>
        <?php
    }
}
