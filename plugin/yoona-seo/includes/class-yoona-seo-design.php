<?php
/** Reversible presentation layer. It never replaces stored page content. */
defined( 'ABSPATH' ) || exit;

final class Yoona_SEO_Design {

	const OPTION = 'yoona_seo_design_enabled';
	private static $selected = false;
	private static $data;

	public static function register() {
		add_action( 'admin_post_yoona_seo_design', array( __CLASS__, 'handle_action' ) );
		add_action( 'admin_init', array( __CLASS__, 'refresh_after_update' ) );
		add_filter( 'template_include', array( __CLASS__, 'template' ), 99 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 100 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	public static function enabled() {
		return '0' !== (string) get_option( self::OPTION, '1' );
	}

	private static function eligible() {
		if ( ! self::enabled() || is_admin() || is_feed() || is_embed() || is_preview()
			|| ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() )
			|| ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() )
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) ) {
			return false;
		}
		// Keep the theme's own visual builder and preview routes intact.
		foreach ( array( 'et_fb', 'et_bfb', 'et_pb_preview', 'elementor-preview' ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route exclusion.
				return false;
			}
		}
		if ( is_singular() && ! is_singular( array( 'page', 'post' ) ) ) {
			return false;
		}
		return is_front_page() || is_home() || is_singular( array( 'page', 'post' ) ) || is_archive() || is_search() || is_404();
	}

	public static function template( $original ) {
		self::$selected = false;
		if ( ! self::eligible() ) {
			return $original;
		}
		$name = 'archive.php';
		if ( is_front_page() && ! post_password_required() ) {
			$name = 'front-page.php';
		} elseif ( is_home() && get_query_var( Yoona_SEO_Project_Gallery::QUERY_FLAG ) ) {
			$name = 'projects.php';
		} elseif ( is_page( 'pelna-oferta' ) && ! post_password_required() ) {
			$name = 'full-offer.php';
		} elseif ( Yoona_SEO_Service_Guides::context( get_queried_object_id() ) ) {
			$name = 'service-guide.php';
		} elseif ( Yoona_SEO_Project_Guides::context( get_queried_object_id() ) ) {
			$name = 'project-guide.php';
		} elseif ( is_page( 'kontakt' ) && ! post_password_required() ) {
			$name = 'contact.php';
		} elseif ( is_singular( array( 'page', 'post' ) ) ) {
			$name = 'content.php';
		}
		$path = dirname( __DIR__ ) . '/templates/' . $name;
		if ( is_readable( $path ) ) {
			// Older themes print their title inside get_header(). Our header uses
			// wp_head(), so opt into WordPress's single, SEO-filtered title tag.
			if ( ! current_theme_supports( 'title-tag' ) ) {
				add_theme_support( 'title-tag' );
			}
			self::$selected = true;
			return $path;
		}
		return $original;
	}

	public static function enqueue() {
		if ( ! self::eligible() ) {
			return;
		}
		wp_enqueue_style( 'yoona-design', plugins_url( 'assets/design.css', YOONA_SEO_FILE ), array(), YOONA_SEO_VERSION );
		wp_enqueue_script( 'yoona-contact-events', plugins_url( 'assets/measurement.js', YOONA_SEO_FILE ), array(), YOONA_SEO_VERSION, true );
	}

	public static function body_class( $classes ) {
		if ( self::$selected ) {
			$classes[] = 'yn-design';
			$classes[] = is_front_page() && ! post_password_required() ? 'yn-front-page' : 'yn-inner-page';
			if ( is_page( 'pelna-oferta' ) ) {
				$classes[] = 'yn-offer-page';
			}
			if ( ( is_home() && get_query_var( Yoona_SEO_Project_Gallery::QUERY_FLAG ) )
				|| ( is_page( 'pelna-oferta' ) && ! post_password_required() ) ) {
				$classes[] = 'yn-collection-page';
			}
		}
		return $classes;
	}

	/** Only curated, already public image paths and contact facts are bundled. */
	public static function data() {
		if ( null === self::$data ) {
			$file = dirname( __DIR__ ) . '/assets/design-data.json';
			$raw = is_readable( $file ) ? file_get_contents( $file ) : '';
			$data = json_decode( (string) $raw, true );
			self::$data = is_array( $data ) ? $data : array();
		}
		return self::$data;
	}

	/** Source-domain media and routes follow WordPress when moved to staging. */
	public static function url( $path ) {
		$path = (string) $path;
		$parts = wp_parse_url( $path );
		if ( is_array( $parts ) && ! empty( $parts['host'] ) ) {
			if ( ! in_array( strtolower( $parts['host'] ), array( 'yoona.pl', 'www.yoona.pl', strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) ), true ) ) {
				return '';
			}
			$path = isset( $parts['path'] ) ? $parts['path'] : '/';
			if ( isset( $parts['query'] ) ) {
				$path .= '?' . $parts['query'];
			}
			if ( isset( $parts['fragment'] ) ) {
				$path .= '#' . $parts['fragment'];
			}
		}
		return home_url( '/' . ltrim( $path, '/' ) );
	}

	public static function route( $key ) {
		$data = self::data();
		$fallbacks = array( 'home' => '/', 'contact' => '/kontakt/', 'services' => '/pelna-oferta/', 'projects' => '/nasze-realizacje/', 'about' => '/o-nas/', 'privacy' => '/polityka-prywatnosci/' );
		$path = isset( $data['navigation'][ $key ]['path'] ) ? $data['navigation'][ $key ]['path'] : ( isset( $fallbacks[ $key ] ) ? $fallbacks[ $key ] : '/' );
		return self::url( $path );
	}

	/** Public service pages, with the same leading photographs as the homepage. */
	public static function offer_cards() {
		$data = self::data();
		$definitions = array_merge( isset( $data['offer_heroes'] ) ? $data['offer_heroes'] : array(), Yoona_SEO_Service_Guides::child_definitions() );
		$featured = array();
		foreach ( $data['services'] as $service ) {
			$featured[ $service['url'] ] = $service;
		}
		$paths = array_values( array_unique( array_merge( array_keys( $featured ), array_keys( $definitions ) ) ) );
		if ( ! $paths ) {
			return array();
		}
		$slugs = array_map( static function ( $path ) { return trim( $path, '/' ); }, $paths );
		$query = new WP_Query( array(
			'post_type' => 'page', 'post_status' => 'publish', 'post_parent' => 0,
			'has_password' => false, 'post_name__in' => $slugs, 'posts_per_page' => count( $paths ),
			'no_found_rows' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false,
		) );
		$pages = array();
		foreach ( $query->posts as $page ) {
			$pages[ '/' . $page->post_name . '/' ] = $page;
		}
		$cards = array();
		foreach ( $paths as $path ) {
			if ( ! isset( $pages[ $path ], $definitions[ $path ] ) ) {
				continue;
			}
			$item = isset( $featured[ $path ] ) ? $featured[ $path ] : $definitions[ $path ];
			$cards[] = array(
				'id' => $pages[ $path ]->ID, 'name' => get_the_title( $pages[ $path ] ),
				'url' => get_permalink( $pages[ $path ] ), 'image' => $item['image'],
				'alt' => isset( $item['alt'] ) ? $item['alt'] : '',
			);
		}
		return $cards;
	}

	public static function image( $item, $class = '', $eager = false ) {
		if ( empty( $item['image'] ) ) {
			return '';
		}
		$url = self::url( $item['image'] );
		if ( '' === $url ) {
			return '';
		}
		$attributes = array( 'class' => $class, 'alt' => isset( $item['alt'] ) ? $item['alt'] : '', 'loading' => $eager ? 'eager' : 'lazy', 'decoding' => 'async' );
		if ( $eager ) {
			$attributes['fetchpriority'] = 'high';
		}
		$attachment_id = attachment_url_to_postid( $url );
		if ( $attachment_id ) {
			$image = wp_get_attachment_image( $attachment_id, $eager ? 'full' : 'large', false, $attributes );
			if ( $image ) {
				return $image;
			}
		}
		$html = '<img src="' . esc_url( $url ) . '"';
		foreach ( $attributes as $name => $value ) {
			$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
		return $html . '>';
	}

	public static function brand() {
		$logo = '';
		$custom_logo = (int) get_theme_mod( 'custom_logo' );
		if ( $custom_logo ) {
			$logo = wp_get_attachment_image( $custom_logo, 'full', false, array( 'class' => 'yn-brand-image', 'alt' => 'YOONA Creative Group' ) );
		} elseif ( function_exists( 'et_get_option' ) ) {
			$divi_logo = et_get_option( 'divi_logo', '' );
			if ( is_string( $divi_logo ) && $divi_logo !== '' ) {
				$logo = '<img class="yn-brand-image" src="' . esc_url( $divi_logo ) . '" alt="YOONA Creative Group" decoding="async">';
			}
		}
		return $logo !== '' ? $logo : '<span class="yn-brand-word">YOONA<span class="yn-brand-sub">CREATIVE GROUP</span></span>';
	}

	public static function part( $name ) {
		if ( in_array( $name, array( 'header', 'footer' ), true ) ) {
			require dirname( __DIR__ ) . '/templates/' . $name . '.php';
		}
	}

	/** An explicit off switch restores the original theme without touching pages. */
	public static function handle_action() {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			wp_die( 'Ta operacja wymaga formularza POST.', '', array( 'response' => 405 ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nie masz uprawnień do zmiany wyglądu.', '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'yoona_seo_design' );
		$mode = isset( $_POST['mode'] ) && is_string( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';
		if ( ! in_array( $mode, array( 'enable', 'disable', 'refresh' ), true ) ) {
			wp_die( 'Nieprawidłowa operacja.', '', array( 'response' => 400 ) );
		}
		$expected = self::enabled() ? '1' : '0';
		if ( 'refresh' !== $mode ) {
			$expected = 'enable' === $mode ? '1' : '0';
			update_option( self::OPTION, $expected, false );
		}
		$actual = self::enabled() ? '1' : '0';
		if ( $expected !== $actual ) {
			wp_die( 'Nie udało się zapisać ustawienia wyglądu. Spróbuj ponownie.', '', array( 'response' => 500 ) );
		}
		self::purge_cache();
		wp_safe_redirect( add_query_arg( 'yoona_design', $mode, admin_url( 'tools.php?page=yoona-seo' ) ) );
		exit;
	}

	public static function refresh_after_update() {
		if ( ! current_user_can( 'manage_options' ) || ! self::enabled() || get_option( 'yoona_seo_design_cache_version', '' ) === YOONA_SEO_VERSION ) {
			return;
		}
		self::purge_cache();
		update_option( 'yoona_seo_design_cache_version', YOONA_SEO_VERSION, false );
	}

	private static function purge_cache() {
		// Page caches only. Never flush the site's global object cache.
		foreach ( array( 'rocket_clean_domain', 'w3tc_flush_posts', 'wp_cache_clear_cache' ) as $function ) {
			if ( function_exists( $function ) ) {
				try {
					call_user_func( $function );
				} catch ( Throwable $error ) {
					// A third-party cache failure must not undo the saved setting.
				}
			}
		}
		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' );
		}
	}

	public static function render_panel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$enabled = self::enabled();
		?>
		<section class="yoona-card" aria-labelledby="yoona-design-title">
			<h2 id="yoona-design-title">Wygląd strony — czarny, biały i pomarańczowy</h2>
			<p><strong><?php echo esc_html( $enabled ? 'Nowy wygląd jest włączony.' : 'Nowy wygląd jest wyłączony. Strona korzysta z dotychczasowego szablonu.' ); ?></strong> Strona główna ma nowy układ zgodny z przesłaną inspiracją. Podstrony otrzymują wspólny nagłówek, stopkę i styl, a ich treść Divi pozostaje zachowana.</p>
			<p>Zmiana wyglądu jest niezależna od wgrywania tekstów SEO. Przycisk poniżej pozwala wrócić do poprzedniego szablonu bez usuwania treści.</p>
			<?php if ( isset( $_GET['yoona_design'] ) && in_array( $_GET['yoona_design'], array( 'enable', 'disable', 'refresh' ), true ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Non-mutating success notice. ?>
				<div class="notice notice-success inline"><p>Ustawienie wyglądu zostało sprawdzone. Wysłano żądanie odświeżenia do obsługiwanych wtyczek pamięci podręcznej.</p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="yoona-inline-form">
				<input type="hidden" name="action" value="yoona_seo_design"><input type="hidden" name="mode" value="<?php echo $enabled ? 'disable' : 'enable'; ?>">
				<?php wp_nonce_field( 'yoona_seo_design' ); ?>
				<?php submit_button( $enabled ? 'Wyłącz nowy wygląd' : 'Włącz nowy wygląd', $enabled ? 'secondary' : 'primary', 'submit', false ); ?>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="yoona-inline-form">
				<input type="hidden" name="action" value="yoona_seo_design"><input type="hidden" name="mode" value="refresh">
				<?php wp_nonce_field( 'yoona_seo_design' ); ?>
				<?php submit_button( 'Odśwież wygląd i wyczyść cache', 'secondary', 'submit', false ); ?>
			</form>
			<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer">Otwórz stronę i sprawdź nowy wygląd ↗</a></p>
			<p class="description">Jeśli nadal widzisz poprzednią wersję, wyczyść również pamięć podręczną hostingu lub CDN i odśwież stronę w przeglądarce.</p>
		</section>
		<?php
	}
}
