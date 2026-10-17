<?php
/** Explicit, reversible creation of six requested service pages. */
defined( 'ABSPATH' ) || exit;

final class Yoona_SEO_Growth_Pages {

	const STATE_OPTION = 'yoona_seo_growth_pages';
	const LOCK_OPTION = 'yoona_seo_growth_pages_lock';
	const OWNED_META = '_yoona_growth_owned';
	const OWNED_VERSION = '3.4.0';
	const PATHS = array(
		'/frezowanie-plexi/',
		'/frezowanie-dibondu/',
		'/frezowanie-elementow-reklamowych/',
		'/markizy-tarasowe/',
		'/markizy-wolnostojace-duo/',
		'/znakowanie-markiz/',
	);

	public static function register() {
		add_action( 'admin_post_yoona_seo_growth_install', array( __CLASS__, 'handle_install' ) );
		add_action( 'admin_post_yoona_seo_growth_rollback', array( __CLASS__, 'handle_rollback' ) );
	}

	/** Reading this panel does not create pages, metadata, locks or options. */
	public static function render_panel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$guides = self::definitions();
		$state = self::state();
		if ( is_wp_error( $state ) ) {
			echo '<section class="yoona-card"><h2>Nowe podstrony CNC i markiz</h2><p>' . esc_html( $state->get_error_message() ) . '</p></section>';
			return;
		}
		?>
		<section class="yoona-card" aria-labelledby="yoona-growth-pages">
			<h2 id="yoona-growth-pages">Nowe podstrony: frezowanie CNC i markizy</h2>
			<p>Przycisk opublikuje sześć osobnych podstron z opisami usług i tytułami oraz opisami Rank Math. Dotychczasowe strony i ich SEO pozostaną bez zmian. Istniejące adresy są pomijane.</p>
			<?php if ( is_wp_error( $guides ) ) : ?>
				<p><?php echo esc_html( $guides->get_error_message() ); ?></p>
			<?php else : ?>
				<ul><?php foreach ( $guides as $path => $guide ) : ?><li><strong><?php echo esc_html( $guide['title'] ); ?></strong> <code><?php echo esc_html( $path ); ?></code></li><?php endforeach; ?></ul>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="yoona_seo_growth_install">
					<?php wp_nonce_field( 'yoona_seo_growth_install' ); ?>
					<?php submit_button( 'Dodaj 6 podstron CNC i markiz', 'secondary', 'submit', false, current_user_can( 'publish_pages' ) ? array() : array( 'disabled' => 'disabled' ) ); ?>
				</form>
			<?php endif; ?>
			<?php if ( ! empty( $state['entries'] ) ) : ?>
				<p><strong>Zapisanych stron w historii: <?php echo esc_html( count( $state['entries'] ) ); ?>.</strong> Cofanie przenosi do kosza wyłącznie niezmienione strony utworzone tym przyciskiem. Zmiana treści, ustawień albo metadanych (również automatycznie przez inną wtyczkę) blokuje automatyczne cofnięcie danej strony; pozostaje ona dostępna do ręcznego sprawdzenia.</p>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="yoona_seo_growth_rollback">
					<?php wp_nonce_field( 'yoona_seo_growth_rollback' ); ?>
					<?php submit_button( 'Cofnij dodanie niezmienionych podstron', 'secondary', 'submit', false, current_user_can( 'publish_pages' ) ? array() : array( 'disabled' => 'disabled' ) ); ?>
				</form>
			<?php endif; ?>
			<?php if ( ! empty( $state['pending'] ) ) : ?>
				<p>Poprzednia operacja nie zapisała pełnej historii. Sprawdź listę stron przed ponowieniem; istniejące adresy nie zostaną nadpisane. Strony bez kompletnego zapisu kontrolnego wymagają ręcznego sprawdzenia.</p>
			<?php endif; ?>
		</section>
		<?php
	}

	public static function handle_install() {
		self::authorize( 'yoona_seo_growth_install' );
		self::finish( self::install(), false );
	}

	public static function handle_rollback() {
		self::authorize( 'yoona_seo_growth_rollback' );
		self::finish( self::rollback(), true );
	}

	/** Callable by integrations with an authenticated publishing administrator. */
	public static function install() {
		$permission = self::permission();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}
		$guides = self::definitions();
		if ( is_wp_error( $guides ) ) {
			return $guides;
		}
		$lock = self::acquire_lock();
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}
		try {
			$state = self::state();
			if ( is_wp_error( $state ) ) {
				return $state;
			}
			$result = array( 'created' => array(), 'skipped' => array(), 'conflicts' => array(), 'errors' => array() );
			foreach ( $guides as $path => $guide ) {
				$collision = self::collision( $path );
				if ( $collision ) {
					$result['skipped'][] = array( 'path' => $path, 'reason' => 'Adres jest już zajęty. Istniejąca strona lub odnośnik pozostały bez zmian.' );
					continue;
				}
				$operation = wp_generate_uuid4();
				// Persist intent before insertion, then the exact ID and fingerprint
				// before continuing. An interrupted response never authorizes an overwrite.
				$state['pending'][ $operation ] = array( 'path' => $path, 'started_at' => gmdate( 'c' ) );
				if ( ! self::save_state( $state ) ) {
					$result['errors'][] = 'Nie udało się zapisać historii przed dodaniem ' . $path . '. Przerwano operację.';
					break;
				}
				$description = wp_html_excerpt( trim( wp_strip_all_tags( $guide['lead'] ) ), 157, '…' );
				$seo_title = self::seo_title( $path, $guide );
				$content = self::content( $guide );
				$content_hash = hash( 'sha256', $content );
				$id = wp_insert_post( wp_slash( array(
					'post_type' => 'page',
					'post_status' => 'publish',
					'post_parent' => 0,
					'post_author' => get_current_user_id(),
					'post_title' => $guide['title'],
					'post_name' => trim( $path, '/' ),
					'post_content' => $content,
					'post_excerpt' => $guide['lead'],
					'comment_status' => 'closed',
					'ping_status' => 'closed',
					'meta_input' => array(
						self::OWNED_META => self::OWNED_VERSION,
						'_yoona_growth_operation' => $operation,
						'_yoona_growth_content_sha256' => $content_hash,
						'rank_math_title' => $seo_title,
						'rank_math_description' => $description,
					),
				) ), true );
				if ( is_wp_error( $id ) || ! $id ) {
					unset( $state['pending'][ $operation ] );
					self::save_state( $state );
					$result['errors'][] = 'Nie dodano ' . $path . '. ' . ( is_wp_error( $id ) ? $id->get_error_message() : 'WordPress nie zwrócił identyfikatora.' );
					break;
				}
				$id = (int) $id;
				$post = get_post( $id );
				$fingerprint = self::fingerprint( $id );
				$verified = $post && 'page' === $post->post_type && 'publish' === $post->post_status && 0 === (int) $post->post_parent
					&& trim( $path, '/' ) === $post->post_name && $guide['title'] === $post->post_title
					&& $content === $post->post_content && self::OWNED_VERSION === get_post_meta( $id, self::OWNED_META, true )
					&& $operation === get_post_meta( $id, '_yoona_growth_operation', true )
					&& $content_hash === get_post_meta( $id, '_yoona_growth_content_sha256', true )
					&& $seo_title === get_post_meta( $id, 'rank_math_title', true )
					&& $description === get_post_meta( $id, 'rank_math_description', true );
				$state['entries'][ $id ] = array(
					'id' => $id, 'path' => $path, 'operation' => $operation,
					'fingerprint' => $fingerprint, 'status' => $verified && $fingerprint ? 'created' : 'needs_review',
					'created_at' => gmdate( 'c' ),
				);
				unset( $state['pending'][ $operation ] );
				$result['created'][] = $id;
				if ( ! self::save_state( $state ) ) {
					$result['errors'][] = 'Dodano stronę ID ' . $id . ', ale nie udało się zapisać pełnej historii. Przerwano; sprawdź tę stronę ręcznie.';
					break;
				}
				if ( ! $verified || ! $fingerprint ) {
					$result['errors'][] = 'Dodana strona ID ' . $id . ' wymaga sprawdzenia: zapis różni się od przygotowanego. Istniejące strony pozostały bez zmian.';
					break;
				}
			}
			return $result;
		} finally {
			self::release_lock( $lock );
		}
	}

	/** Move only exact, unchanged created IDs to trash; never hard-delete media. */
	public static function rollback() {
		$permission = self::permission();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}
		$lock = self::acquire_lock();
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}
		try {
			$state = self::state();
			if ( is_wp_error( $state ) ) {
				return $state;
			}
			$result = array( 'trashed' => array(), 'skipped' => array(), 'conflicts' => array(), 'errors' => array() );
			if ( ! defined( 'EMPTY_TRASH_DAYS' ) || (int) EMPTY_TRASH_DAYS < 1 ) {
				return new WP_Error( 'yoona_growth_trash_disabled', 'Kosz WordPressa jest wyłączony. Nie usunięto żadnej strony; cofnięcie wymaga działającego kosza.' );
			}
			foreach ( $state['entries'] as $key => $entry ) {
				$id = isset( $entry['id'] ) ? (int) $entry['id'] : 0;
				$path = isset( $entry['path'] ) ? (string) $entry['path'] : '';
				if ( isset( $entry['status'] ) && 'rolled_back' === $entry['status'] ) {
					$result['skipped'][] = array( 'id' => $id, 'path' => $path, 'reason' => 'Dodanie tej strony zostało już cofnięte.' );
					continue;
				}
				$post = $id ? get_post( $id ) : null;
				$unchanged = $id && (int) $key === $id && in_array( $path, self::PATHS, true )
					&& isset( $entry['status'], $entry['operation'], $entry['fingerprint'] )
					&& in_array( $entry['status'], array( 'created', 'trashing' ), true )
					&& is_string( $entry['fingerprint'] ) && preg_match( '/^[a-f0-9]{64}$/D', $entry['fingerprint'] )
					&& $post && 'page' === $post->post_type && 'publish' === $post->post_status && 0 === (int) $post->post_parent
					&& trim( $path, '/' ) === $post->post_name && self::OWNED_VERSION === get_post_meta( $id, self::OWNED_META, true )
					&& $entry['operation'] === get_post_meta( $id, '_yoona_growth_operation', true )
					&& hash_equals( $entry['fingerprint'], self::fingerprint( $id ) );
				if ( ! $unchanged || ! current_user_can( 'delete_post', $id ) ) {
					$result['conflicts'][] = array( 'id' => $id, 'path' => $path, 'reason' => 'Strona lub jej metadane zmieniły się, zapis wymaga sprawdzenia albo brak uprawnień do kosza. Zachowano stronę.' );
					continue;
				}
				$state['entries'][ $key ]['status'] = 'trashing';
				if ( ! self::save_state( $state ) ) {
					$result['errors'][] = 'Nie zapisano historii przed cofnięciem. Przerwano bez usuwania kolejnych stron.';
					break;
				}
				$trashed = wp_trash_post( $id );
				if ( ! $trashed || 'trash' !== get_post_status( $id ) ) {
					$result['errors'][] = 'WordPress nie przeniósł strony ID ' . $id . ' do kosza. Sprawdź ją ręcznie.';
					break;
				}
				$state['entries'][ $key ]['status'] = 'rolled_back';
				$result['trashed'][] = $id;
				if ( ! self::save_state( $state ) ) {
					$result['errors'][] = 'Strona ID ' . $id . ' jest w koszu, ale nie zapisano końcowego stanu historii. Przerwano.';
					break;
				}
			}
			return $result;
		} finally {
			self::release_lock( $lock );
		}
	}

	/** Validate all six definitions before the first write. No arbitrary JSON URLs. */
	private static function definitions() {
		$file = dirname( __DIR__ ) . '/content/service-guides.json';
		$raw = is_readable( $file ) ? file_get_contents( $file ) : false;
		$data = is_string( $raw ) ? json_decode( $raw, true ) : null;
		$guides = array();
		foreach ( self::PATHS as $path ) {
			$guide = is_array( $data ) && isset( $data[ $path ] ) && is_array( $data[ $path ] ) ? $data[ $path ] : null;
			if ( ! $guide || ! isset( $guide['title'], $guide['lead'] ) || ! is_string( $guide['title'] ) || ! is_string( $guide['lead'] )
				|| '' === trim( $guide['title'] ) || '' === trim( $guide['lead'] ) || strlen( $guide['title'] ) > 300
				|| wp_strip_all_tags( $guide['title'] ) !== $guide['title'] || wp_strip_all_tags( $guide['lead'] ) !== $guide['lead'] ) {
				return new WP_Error( 'yoona_growth_definition', 'Niekompletne dane nowych podstron. Nic nie zostało dodane.' );
			}
			foreach ( array( 'materials', 'applications', 'process', 'pricing', 'quote', 'related' ) as $field ) {
				if ( ! isset( $guide[ $field ] ) || ! is_array( $guide[ $field ] ) ) {
					return new WP_Error( 'yoona_growth_definition', 'Brak listy w opisie usługi ' . $path . '. Nic nie zostało dodane.' );
				}
				foreach ( $guide[ $field ] as $value ) {
					if ( ! is_string( $value ) || '' === trim( $value ) ) {
						return new WP_Error( 'yoona_growth_definition', 'Nieprawidłowy opis usługi ' . $path . '. Nic nie zostało dodane.' );
					}
				}
			}
			if ( ! isset( $guide['overview'], $guide['faq'] ) || ! is_string( $guide['overview'] ) || ! is_array( $guide['faq'] ) ) {
				return new WP_Error( 'yoona_growth_definition', 'Niekompletny opis usługi ' . $path . '. Nic nie zostało dodane.' );
			}
			foreach ( $guide['faq'] as $faq ) {
				if ( ! is_array( $faq ) || ! isset( $faq['q'], $faq['a'] ) || ! is_string( $faq['q'] ) || ! is_string( $faq['a'] ) ) {
					return new WP_Error( 'yoona_growth_definition', 'Nieprawidłowe pytania w opisie ' . $path . '. Nic nie zostało dodane.' );
				}
			}
			$guides[ $path ] = $guide;
		}
		return $guides;
	}

	/** Local search titles are distinct while the visible page names stay concise. */
	private static function seo_title( $path, $guide ) {
		$titles = array(
			'/frezowanie-plexi/' => 'Frezowanie plexi Olsztyn i Ostróda | YOONA',
			'/frezowanie-dibondu/' => 'Frezowanie dibondu Olsztyn i Ostróda | YOONA',
			'/frezowanie-elementow-reklamowych/' => 'Frezowanie CNC do reklam Olsztyn i Ostróda | YOONA',
			'/markizy-tarasowe/' => 'Markizy tarasowe Olsztyn i Ostróda | YOONA',
			'/markizy-wolnostojace-duo/' => 'Markizy wolnostojące DUO Olsztyn i Ostróda | YOONA',
			'/znakowanie-markiz/' => 'Znakowanie markiz Olsztyn i Ostróda | YOONA',
		);
		return isset( $titles[ $path ] ) ? $titles[ $path ] : $guide['title'] . ' | YOONA';
	}

	private static function content( $guide ) {
		$html = '<div class="yn-growth-page-content"><p class="yn-growth-lead">' . esc_html( $guide['lead'] ) . '</p>' . "\n";
		$html .= '<p>' . esc_html( $guide['overview'] ) . '</p>' . "\n";
		$sections = array( 'materials' => 'Materiały i wykonanie', 'applications' => 'Zastosowanie', 'process' => 'Jak przebiega realizacja', 'pricing' => 'Od czego zależy cena', 'quote' => 'Co przygotować do wyceny' );
		foreach ( $sections as $key => $heading ) {
			if ( ! empty( $guide[ $key ] ) ) {
				$html .= '<h2>' . esc_html( $heading ) . '</h2><ul>';
				foreach ( $guide[ $key ] as $item ) {
					$html .= '<li>' . esc_html( $item ) . '</li>';
				}
				$html .= '</ul>' . "\n";
			}
		}
		if ( ! empty( $guide['faq'] ) ) {
			$html .= '<h2>Pytania przed zamówieniem</h2>' . "\n";
			foreach ( $guide['faq'] as $faq ) {
				$html .= '<h3>' . esc_html( $faq['q'] ) . '</h3><p>' . esc_html( $faq['a'] ) . '</p>' . "\n";
			}
		}
		$related = array();
		foreach ( $guide['related'] as $path ) {
			if ( preg_match( '~^/[a-z0-9-]+/$~D', $path ) ) {
				$label = ucwords( str_replace( '-', ' ', trim( $path, '/' ) ) );
				$related[] = '<li><a href="' . esc_url( home_url( $path ) ) . '">' . esc_html( $label ) . '</a></li>';
			}
		}
		if ( $related ) {
			$html .= '<h2>Powiązane usługi</h2><ul>' . implode( '', $related ) . '</ul>' . "\n";
		}
		$html .= '<p><a href="' . esc_url( home_url( '/kontakt/' ) ) . '">Prześlij materiały i zapytaj o wycenę</a></p></div>';
		return $html;
	}

	/** Conservative collision checks include unpublished objects and custom menu URLs. */
	private static function collision( $path ) {
		global $wpdb;
		$slug = trim( $path, '/' );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type <> 'revision' LIMIT 1", $slug ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $id || url_to_postid( home_url( $path ) ) ) {
			return true;
		}
		$links = $wpdb->get_col( $wpdb->prepare( "SELECT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'nav_menu_item' AND pm.meta_key = %s", '_menu_item_url' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$wanted = untrailingslashit( (string) wp_parse_url( home_url( $path ), PHP_URL_PATH ) );
		foreach ( $links as $link ) {
			$host = strtolower( (string) wp_parse_url( $link, PHP_URL_HOST ) );
			$link_path = untrailingslashit( rawurldecode( (string) wp_parse_url( $link, PHP_URL_PATH ) ) );
			if ( ( '' === $host || $home_host === $host ) && $wanted === $link_path ) {
				return true;
			}
		}
		return false;
	}

	private static function permission() {
		return current_user_can( 'manage_options' ) && current_user_can( 'publish_pages' ) ? true : new WP_Error( 'yoona_growth_permission', 'Wymagane są uprawnienia administratora oraz publikowania stron.' );
	}

	private static function authorize( $action ) {
		$permission = self::permission();
		if ( is_wp_error( $permission ) ) {
			wp_die( esc_html( $permission->get_error_message() ), '', array( 'response' => 403 ) );
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
			wp_die( esc_html( 'Ta czynność wymaga formularza POST z panelu Yoona SEO.' ), '', array( 'response' => 405 ) );
		}
		check_admin_referer( $action );
	}

	private static function state() {
		$state = get_option( self::STATE_OPTION, null );
		if ( null === $state ) {
			return array( 'version' => 1, 'entries' => array(), 'pending' => array() );
		}
		if ( ! is_array( $state ) || ! isset( $state['version'], $state['entries'], $state['pending'] )
			|| 1 !== $state['version'] || ! is_array( $state['entries'] ) || ! is_array( $state['pending'] ) ) {
			return new WP_Error( 'yoona_growth_history', 'Historia nowych podstron wymaga sprawdzenia. Nie nadpisano jej i nie uruchomiono dodawania ani cofania stron.' );
		}
		return $state;
	}

	private static function save_state( $state ) {
		$old = get_option( self::STATE_OPTION, null );
		$saved = null === $old ? add_option( self::STATE_OPTION, $state, '', false ) : update_option( self::STATE_OPTION, $state, false );
		return $saved || get_option( self::STATE_OPTION, null ) === $state;
	}

	private static function fingerprint( $id ) {
		$post = get_post( $id, ARRAY_A, 'raw' );
		if ( ! is_array( $post ) ) {
			return '';
		}
		$meta = get_post_meta( $id );
		ksort( $post, SORT_STRING );
		ksort( $meta, SORT_STRING );
		return hash( 'sha256', serialize( array( 'post' => $post, 'meta' => $meta ) ) );
	}

	/** No expired-lock stealing: a crashed worker requires administrator review. */
	private static function acquire_lock() {
		global $wpdb;
		$token = wp_generate_uuid4();
		// add_option uses an upsert on some supported WordPress versions.
		// INSERT IGNORE acquires exactly once, even with a cached absent option.
		$inserted = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", self::LOCK_OPTION, $token ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_cache_delete( self::LOCK_OPTION, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		if ( 1 !== $inserted ) {
			return new WP_Error( 'yoona_growth_locked', 'Trwa dodawanie lub cofanie nowych podstron. Jeśli poprzednia operacja została przerwana, administrator powinien sprawdzić historię i blokadę yoona_seo_growth_pages_lock przed jej ręcznym usunięciem. Nie uruchomiono kolejnej operacji.' );
		}
		return $token;
	}

	private static function release_lock( $token ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", self::LOCK_OPTION, $token ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_cache_delete( self::LOCK_OPTION, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	private static function finish( $result, $rollback ) {
		$type = 'success';
		if ( is_wp_error( $result ) ) {
			$type = 'error';
			$message = $result->get_error_message();
		} else {
			$count = count( $result[ $rollback ? 'trashed' : 'created' ] );
			$message = $rollback ? 'Przeniesiono do kosza niezmienione nowe podstrony: ' . $count . '.' : 'Dodano nowe podstrony: ' . $count . '.';
			if ( ! empty( $result['skipped'] ) ) {
				$message .= ' Pominięto: ' . count( $result['skipped'] ) . '.';
			}
			if ( ! empty( $result['conflicts'] ) ) {
				$type = 'warning';
				$message .= ' Zachowano strony wymagające ręcznego sprawdzenia: ' . count( $result['conflicts'] ) . '.';
			}
			if ( ! empty( $result['errors'] ) ) {
				$type = 'error';
				$message .= ' ' . implode( ' ', $result['errors'] );
			}
		}
		$pages = array();
		if ( is_array( $result ) ) {
			foreach ( array( 'skipped', 'conflicts' ) as $group ) {
				foreach ( $result[ $group ] as $row ) {
					$pages[] = array( 'path' => sanitize_text_field( $row['path'] ), 'message' => sanitize_text_field( $row['reason'] ) );
				}
			}
		}
		set_transient( 'yoona_seo_notice_' . get_current_user_id(), array( 'type' => $type, 'message' => sanitize_text_field( $message ), 'pages' => $pages ), 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'tools.php?page=yoona-seo' ) );
		exit;
	}
}
