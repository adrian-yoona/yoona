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
    public static function render( $guide ) {
        if ( ! is_array( $guide ) || empty( $guide['summary'] ) ) return;
        ?>
        <section class="yn-section yn-guide yn-project-guide" aria-label="Zakres realizacji"><div class="yn-container">
          <div class="yn-guide-intro"><p><?php echo esc_html( $guide['summary'] ); ?></p></div>
          <div class="yn-guide-grid">
          <?php if ( ! empty( $guide['location'] ) ) : ?><section class="yn-guide-panel"><h2>Miejsce realizacji</h2><p><?php echo esc_html( $guide['location'] ); ?></p></section><?php endif; ?>
          <?php foreach ( array( 'scope' => 'Co wykonaliśmy', 'materials' => 'Materiały' ) as $field => $label ) : if ( empty( $guide[ $field ] ) ) continue; ?><section class="yn-guide-panel"><h2><?php echo esc_html( $label ); ?></h2><ul><?php foreach ( $guide[ $field ] as $fact ) : ?><li><?php echo esc_html( $fact ); ?></li><?php endforeach; ?></ul></section><?php endforeach; ?>
          </div>
          <?php $link = empty( $guide['service_path'] ) ? '' : Yoona_SEO_Service_Guides::link( $guide['service_path'] ); $services = Yoona_SEO_Service_Guides::data(); if ( $link && ! empty( $services[ $guide['service_path'] ]['title'] ) ) : ?><p><a class="yn-text-link" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $services[ $guide['service_path'] ]['title'] ); ?> <span aria-hidden="true">↗</span></a></p><?php endif; ?>
        </div></section>
        <?php
    }
}
