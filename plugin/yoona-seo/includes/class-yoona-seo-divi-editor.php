<?php
/** Explicit, reversible conversion of generated pages into native Divi modules. */
defined( 'ABSPATH' ) || exit;

final class Yoona_SEO_Divi_Editor {

	const META = '_yoona_divi_authoritative';
	const BACKUP = '_yoona_divi_editor_backup';
	const RECOVERY = '_yoona_divi_editor_recovery';
	const LOCK = 'yoona_divi_editor_lock_';
	const MAX_BYTES = 5242880;
	private static $meta_keys = array( '_et_pb_use_builder', '_et_pb_page_layout', '_yoona_divi_authoritative' );

	public static function register() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_yoona_seo_divi_convert', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_yoona_seo_divi_restore', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_yoona_seo_divi_recover', array( __CLASS__, 'handle' ) );
		add_shortcode( 'yoona_realizacje', array( __CLASS__, 'gallery_shortcode' ) );
		add_filter( 'do_shortcode_tag', array( __CLASS__, 'code_shortcode' ), 30, 2 );
		add_filter( 'et_module_shortcode_output', array( __CLASS__, 'code_shortcode' ), 30, 2 );
	}

	/** Divi versions that leave a Code module's shortcode literal get one expansion. */
	public static function code_shortcode( $output, $tag ) {
		if ( 'et_pb_code' !== $tag || ! is_string( $output ) || false === strpos( $output, '[yoona_realizacje]' ) ) return $output;
		return str_replace( '[yoona_realizacje]', self::gallery_shortcode(), $output );
	}

	/** Dynamic collection remains movable/copyable as one native Divi Code module. */
	public static function gallery_shortcode() {
		global $wp_query;
		$paged = max( 1, (int) get_query_var( 'paged' ) );
		if ( $wp_query instanceof WP_Query && $wp_query->is_home() && $wp_query->get( Yoona_SEO_Project_Gallery::QUERY_FLAG )
			&& 24 === (int) $wp_query->get( 'posts_per_page' ) ) {
			$query = $wp_query;
		} else {
			$query = new WP_Query( array( Yoona_SEO_Project_Gallery::QUERY_FLAG => 'native', 'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false,
				'posts_per_page' => 24, 'paged' => $paged, 'ignore_sticky_posts' => true, 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'no_found_rows' => false ) );
		}
		$html = '<div class="yn-main yn-project-gallery yn-divi-dynamic-gallery"><div class="yn-project-gallery-grid">';
		foreach ( $query->posts as $index => $project ) {
			$image = Yoona_SEO_Project_Gallery::image( $project->ID, 0 === $index );
			if ( ! $image ) continue;
			$html .= '<article class="yn-project-gallery-card" data-project-id="' . (int) $project->ID . '"><a href="' . esc_url( get_permalink( $project ) ) . '"><div class="yn-project-gallery-image">' . $image . '</div><h2>' . esc_html( get_the_title( $project ) ) . '</h2></a></article>';
		}
		$html .= '</div>';
		if ( ! $query->posts ) $html .= '<p>Realizacje pojawią się tutaj po opublikowaniu.</p>';
		$pagination = paginate_links( array( 'total' => (int) $query->max_num_pages, 'current' => $paged, 'prev_text' => '← Poprzednie', 'next_text' => 'Następne →', 'mid_size' => 1 ) );
		if ( $pagination ) $html .= '<nav class="pagination" aria-label="Nawigacja realizacji"><div class="nav-links">' . $pagination . '</div></nav>';
		return $html . '</div>';
	}

	/** Divi owns both the stored content and subsequent edits after conversion. */
	public static function authoritative( $post_id ) {
		return '1' === (string) get_post_meta( (int) $post_id, self::META, true );
	}

	/** Keep the real builder, its previews and its requests outside presentation. */
	public static function builder_request() {
		if ( ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() )
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) ) return true;
		foreach ( array( 'et_fb', 'et_bfb', 'et_pb_preview', 'et_pb_preview_nonce', 'et_pb_layout', 'elementor-preview' ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) return true; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route exclusion.
		}
		foreach ( array( 'et_fb_is_enabled', 'et_builder_is_frontend_editor', 'et_builder_is_preview', 'et_core_is_fb_enabled' ) as $function ) {
			if ( function_exists( $function ) && 0 === ( new ReflectionFunction( $function ) )->getNumberOfRequiredParameters() && call_user_func( $function ) ) return true;
		}
		return false;
	}

	public static function available() {
		return defined( 'ET_BUILDER_VERSION' ) || shortcode_exists( 'et_pb_section' ) || function_exists( 'et_pb_is_pagebuilder_used' );
	}

	public static function meta_boxes() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		foreach ( array( 'page', 'post' ) as $type ) {
			add_meta_box( 'yoona-divi-editor', 'YOONA — edycja układu w Divi', array( __CLASS__, 'meta_box' ), $type, 'side' );
		}
	}

	public static function meta_box( $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) return;
		echo '<p>' . ( self::authoritative( $post->ID ) ? 'Ten układ jest zapisany jako moduły Divi. Twoje zmiany nie są zastępowane przez szablon wtyczki.' : 'Zapisz nowy wygląd tej strony jako prawdziwe sekcje, wiersze, kolumny i moduły Divi. Przed zapisem powstanie kopia obecnego układu.' ) . '</p>';
		echo '<p><a class="button" href="' . esc_url( self::screen_url( $post->ID ) ) . '">' . ( self::authoritative( $post->ID ) ? 'Edytuj układ / przywróć kopię' : 'Edytuj układ w Divi' ) . '</a></p>';
	}

	public static function menu() {
		add_submenu_page( null, 'Edycja YOONA w Divi', 'Edycja YOONA w Divi', 'manage_options', 'yoona-divi-editor', array( __CLASS__, 'screen' ) );
	}

	private static function screen_url( $post_id ) {
		return add_query_arg( array( 'page' => 'yoona-divi-editor', 'post_id' => (int) $post_id ), admin_url( 'tools.php' ) );
	}

	public static function screen() {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview.
		$post = get_post( $post_id );
		if ( ! self::allowed( $post ) ) wp_die( 'Nie masz uprawnień do edycji tej strony.', '', array( 'response' => 403 ) );
		echo '<div class="wrap"><h1>Edycja układu w Divi: ' . esc_html( $post->post_title ) . '</h1>';
		$backup = get_post_meta( $post_id, self::BACKUP, true );
		if ( self::authoritative( $post_id ) || $backup ) {
			echo self::authoritative( $post_id ) ? '<div class="notice notice-success inline"><p>Układ jest zapisany w treści strony. Edytuj i kopiuj moduły w standardowym edytorze Divi.</p></div>' : '<div class="notice notice-warning inline"><p>Istnieje kopia układu. Najpierw zakończ przywracanie; ponowna konwersja jest zablokowana.</p></div>';
			echo '<p><a class="button button-primary" href="' . esc_url( get_edit_post_link( $post_id, 'raw' ) ) . '">Otwórz edycję strony</a> ';
			if ( self::available() && 'publish' === $post->post_status && '' === $post->post_password ) {
				echo '<a class="button" href="' . esc_url( add_query_arg( array( 'et_fb' => '1', 'PageSpeed' => 'off' ), get_permalink( $post_id ) ) ) . '">Otwórz Visual Builder</a>';
			}
			echo '</p><p>Przywrócenie zastąpi obecną treść poprzednim układem. Obecny układ otrzyma niezależną kopię odzyskiwania, również gdy rewizje WordPressa są wyłączone.</p>';
			if ( self::valid_state( $backup ) ) {
				self::source_comparison( $post->post_content, $backup['content'] );
				self::form( $post, 'restore', 'Przywróć poprzedni układ' );
			}
		} else {
			$content = self::materialize( $post );
			if ( is_wp_error( $content ) ) {
				echo '<div class="notice notice-error inline"><p>' . esc_html( $content->get_error_message() ) . '</p></div>';
			} else {
				echo '<p>Nowa treść będzie zapisana jako natywne moduły Divi. Teksty, zdjęcia i przyciski można potem zmieniać i kopiować w edytorze. Oryginalne galerie, formularze i pozostałe moduły zachowują swoje drzewo Divi.</p>';
				echo '<p>Zmiana dotyczy wyłącznie tej strony. Nie zapisuje zmian Rank Math, tytułu strony ani danych pomiarowych. Późniejsze aktualizacje wyglądu YOONA nie nadpiszą tego układu.</p>';
				preg_match_all( '/\[(et_pb_[a-z_]+)(?:\s|\])/', $content, $matches );
				echo '<p><strong>Do zapisania: ' . (int) count( $matches[1] ) . ' elementów Divi.</strong></p><details><summary>Podgląd zapisywanego źródła</summary><textarea readonly rows="18" class="large-text code" aria-label="Natywne moduły Divi">' . esc_textarea( $content ) . '</textarea></details>';
				if ( self::available() ) self::form( $post, 'convert', 'Zapisz układ i edytuj w Divi' );
				else echo '<div class="notice notice-warning inline"><p>Włącz motyw Divi lub wtyczkę Divi Builder, aby zapisać i edytować te moduły.</p></div>';
			}
		}
		$recoveries = get_post_meta( $post_id, self::RECOVERY, false );
		$recovery = $recoveries ? end( $recoveries ) : false;
		if ( self::valid_state( $recovery ) && ! self::matches_state( $post_id, $recovery ) ) {
			echo '<h2>Odzyskaj ostatni układ sprzed przywracania</h2><p>Ta kopia zachowuje poprzednie ręczne zmiany niezależnie od rewizji WordPressa. Zastąpi obecną treść i ustawienia Divi; przed zastąpieniem zapiszemy także obecną wersję.</p>';
			self::source_comparison( $post->post_content, $recovery['content'] );
			self::form( $post, 'recover', 'Odzyskaj układ sprzed przywracania' );
		}
		echo '</div>';
	}

	private static function source_comparison( $current, $destination ) {
		echo '<details><summary>Porównaj źródła przed przywróceniem</summary><p><label>Obecna treść<textarea readonly rows="10" class="large-text code">' . esc_textarea( $current ) . '</textarea></label></p><p><label>Treść z kopii<textarea readonly rows="10" class="large-text code">' . esc_textarea( $destination ) . '</textarea></label></p></details>';
	}

	private static function form( $post, $mode, $label ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="yoona_seo_divi_' . esc_attr( $mode ) . '"><input type="hidden" name="post_id" value="' . (int) $post->ID . '"><input type="hidden" name="source_fingerprint" value="' . esc_attr( self::fingerprint( $post ) ) . '">';
		wp_nonce_field( 'yoona_seo_divi_' . $mode . '_' . $post->ID );
		submit_button( $label );
		echo '</form>';
	}

	private static function allowed( $post ) {
		return $post instanceof WP_Post && in_array( $post->post_type, array( 'page', 'post' ), true )
			&& ! in_array( $post->post_status, array( 'auto-draft', 'trash', 'inherit' ), true )
			&& current_user_can( 'manage_options' ) && current_user_can( 'edit_post', $post->ID );
	}

	public static function fingerprint( $post ) {
		$post = get_post( $post );
		if ( ! $post ) return '';
		$meta = array();
		foreach ( array_merge( self::$meta_keys, array( self::BACKUP, self::RECOVERY ) ) as $key ) $meta[ $key ] = get_post_meta( $post->ID, $key, false );
		return hash( 'sha256', wp_json_encode( array( $post->ID, $post->post_content, $post->post_title, $post->post_modified_gmt, $meta, YOONA_SEO_VERSION ) ) );
	}

	public static function handle() {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) wp_die( 'Ta operacja wymaga formularza POST.', '', array( 'response' => 405 ) );
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$mode = 'convert';
		foreach ( array( 'restore', 'recover' ) as $candidate ) if ( isset( $_POST['action'] ) && 'yoona_seo_divi_' . $candidate === $_POST['action'] ) $mode = $candidate;
		if ( ! self::allowed( get_post( $post_id ) ) ) wp_die( 'Nie masz uprawnień do edycji tej strony.', '', array( 'response' => 403 ) );
		check_admin_referer( 'yoona_seo_divi_' . $mode . '_' . $post_id );
		$expected = isset( $_POST['source_fingerprint'] ) && is_string( $_POST['source_fingerprint'] ) ? wp_unslash( $_POST['source_fingerprint'] ) : '';
		$result = self::save( $post_id, $expected, $mode );
		if ( is_wp_error( $result ) ) wp_die( esc_html( $result->get_error_message() ), 'Układ nie został zmieniony', array( 'response' => 409, 'back_link' => true ) );
		wp_safe_redirect( self::screen_url( $post_id ) );
		exit;
	}

	/** Called only by the explicit nonce-protected form; never by a page view. */
	public static function save( $post_id, $expected, $mode = 'convert' ) {
		$post = get_post( $post_id );
		if ( ! self::allowed( $post ) ) return new WP_Error( 'forbidden', 'Nie masz uprawnień do edycji tej strony.' );
		if ( ! in_array( $mode, array( 'convert', 'restore', 'recover' ), true ) || ! is_string( $expected ) || ! preg_match( '/\A[a-f0-9]{64}\z/', $expected )
			|| ! hash_equals( self::fingerprint( $post ), $expected ) ) return new WP_Error( 'source_changed', 'Treść lub ustawienia zmieniły się od otwarcia podglądu. Otwórz podgląd ponownie.' );
		if ( function_exists( 'wp_check_post_lock' ) && wp_check_post_lock( $post_id ) ) return new WP_Error( 'locked', 'Inna osoba edytuje tę stronę. Zapisz układ po zakończeniu jej pracy.' );
		$token = self::acquire_lock( $post_id );
		if ( is_wp_error( $token ) ) return $token;
		try {
			clean_post_cache( $post_id );
			$post = get_post( $post_id );
			if ( ! hash_equals( self::fingerprint( $post ), $expected ) ) return new WP_Error( 'source_changed', 'Treść strony zmieniła się. Otwórz podgląd ponownie.' );
			$before = self::state( $post );
			$backup = get_post_meta( $post_id, self::BACKUP, true );
			if ( 'convert' === $mode ) {
				if ( ! self::available() ) return new WP_Error( 'no_divi', 'Włącz motyw Divi lub wtyczkę Divi Builder.' );
				if ( self::authoritative( $post_id ) || metadata_exists( 'post', $post_id, self::BACKUP ) ) return new WP_Error( 'already_converted', 'Ta strona ma już zapisany układ lub kopię Divi. Nie zastępuj ręcznych zmian ponowną konwersją.' );
				$content = self::materialize( $post );
				if ( is_wp_error( $content ) ) return $content;
				$target = $before;
				$target['content'] = $content;
				$target['content_sha256'] = hash( 'sha256', $content );
				$target['meta']['_et_pb_use_builder'] = array( 'on' );
				$target['meta']['_et_pb_page_layout'] = array( 'et_full_width_page' );
				$target['meta'][ self::META ] = array( '1' );
			} else {
				$recoveries = get_post_meta( $post_id, self::RECOVERY, false );
				$target = 'restore' === $mode ? $backup : ( $recoveries ? end( $recoveries ) : false );
				if ( ! self::valid_state( $target ) ) return new WP_Error( 'bad_backup', 'Brak prawidłowej kopii układu. Treść pozostaje bez zmian.' );
			}
			// A limited account must be able to restore both sides without KSES loss.
			if ( ! self::can_preserve( $post, $before['content'] ) || ! self::can_preserve( $post, $target['content'] ) ) return new WP_Error( 'html_permissions', 'To konto nie może zapisać HTML tego układu bez zmian. Użyj konta z uprawnieniem unfiltered_html; treść pozostaje bez zmian.' );
			if ( ! self::valid_state( $target ) ) return new WP_Error( 'invalid_destination', 'Nie można zapisać pełnego układu. Treść pozostaje bez zmian.' );
			clean_post_cache( $post_id );
			if ( ! hash_equals( self::fingerprint( get_post( $post_id ) ), $expected ) ) return new WP_Error( 'source_changed', 'Treść strony zmieniła się podczas przygotowania układu. Otwórz podgląd ponownie.' );
			if ( 'convert' === $mode || ( 'recover' === $mode && ! $backup ) ) {
				if ( ! add_post_meta( $post_id, self::BACKUP, wp_slash( $before ), true ) || get_post_meta( $post_id, self::BACKUP, true ) !== $before ) return new WP_Error( 'backup_failed', 'Nie udało się zweryfikować kopii. Treść pozostaje bez zmian.' );
			}
			if ( 'restore' === $mode || 'recover' === $mode ) {
				// Independent of WordPress revisions: manual Divi edits remain recoverable.
				$recovery_id = add_post_meta( $post_id, self::RECOVERY, wp_slash( $before ), false );
				$stored_recovery = $recovery_id ? get_metadata_by_mid( 'post', $recovery_id ) : false;
				if ( ! $stored_recovery || $stored_recovery->meta_value !== $before ) return new WP_Error( 'recovery_failed', 'Nie udało się zweryfikować kopii obecnego układu. Przywracanie zostało zatrzymane przed zmianą treści.' );
			}
			if ( function_exists( 'wp_save_post_revision' ) ) wp_save_post_revision( $post_id );
			if ( ! self::write_state( $post_id, $target ) ) {
				$rolled_back = self::write_state( $post_id, $before );
				// Keep every recovery copy until an administrator completes restoration.
				return new WP_Error( 'save_failed', $rolled_back ? 'Zapis nie powiódł się. Zweryfikowano przywrócenie treści i ustawień sprzed operacji; kopie zachowano.' : 'Zapis i pełne przywrócenie nie powiodły się. Zachowano kopię pierwotnego układu; użyj przywracania kopii przed dalszą edycją.' );
			}
			if ( 'restore' === $mode && ! delete_post_meta( $post_id, self::BACKUP ) ) return new WP_Error( 'backup_cleanup_failed', 'Poprzedni układ został przywrócony i zweryfikowany. Kopia nadal istnieje; nie uruchamiaj ponownej konwersji, dopóki nie usuniesz przyczyny błędu zapisu.' );
			clean_post_cache( $post_id );
			do_action( 'litespeed_purge_post', $post_id );
			return $post_id;
		} finally {
			self::release_lock( $post_id, $token );
		}
	}

	private static function state( $post ) {
		$meta = array();
		foreach ( self::$meta_keys as $key ) $meta[ $key ] = get_post_meta( $post->ID, $key, false );
		return array( 'schema' => 1, 'content' => (string) $post->post_content, 'content_sha256' => hash( 'sha256', $post->post_content ), 'created_gmt' => current_time( 'mysql', true ), 'meta' => $meta );
	}

	private static function valid_state( $state ) {
		if ( ! is_array( $state ) || ! isset( $state['schema'], $state['content'], $state['content_sha256'], $state['meta'] ) || 1 !== $state['schema']
			|| ! is_string( $state['content'] ) || strlen( $state['content'] ) > self::MAX_BYTES || ! is_string( $state['content_sha256'] )
			|| ! hash_equals( $state['content_sha256'], hash( 'sha256', $state['content'] ) ) || ! is_array( $state['meta'] ) ) return false;
		foreach ( self::$meta_keys as $key ) if ( ! isset( $state['meta'][ $key ] ) || ! is_array( $state['meta'][ $key ] ) ) return false;
		return true;
	}

	private static function can_preserve( $post, $content ) {
		return current_user_can( 'unfiltered_html' ) || ( wp_kses_post( $content ) === $content && wp_kses_post( $post->post_content ) === $post->post_content
			&& wp_kses_post( $post->post_excerpt ) === $post->post_excerpt && wp_kses_post( $post->post_content_filtered ) === $post->post_content_filtered && wp_kses( $post->post_title, 'title_save_pre' ) === $post->post_title );
	}

	private static function write_state( $post_id, $state ) {
		if ( ! self::valid_state( $state ) ) return false;
		if ( $state['content'] !== get_post_field( 'post_content', $post_id, 'raw' ) ) {
			$saved = wp_update_post( wp_slash( array( 'ID' => $post_id, 'post_content' => $state['content'] ) ), true );
			if ( is_wp_error( $saved ) || ! $saved || $state['content'] !== get_post_field( 'post_content', $post_id, 'raw' ) ) return false;
		}
		foreach ( self::$meta_keys as $key ) {
			if ( get_post_meta( $post_id, $key, false ) === $state['meta'][ $key ] ) continue;
			if ( metadata_exists( 'post', $post_id, $key ) && ! delete_post_meta( $post_id, $key ) ) return false;
			foreach ( $state['meta'][ $key ] as $value ) if ( ! add_post_meta( $post_id, $key, wp_slash( $value ), false ) ) return false;
			if ( get_post_meta( $post_id, $key, false ) !== $state['meta'][ $key ] ) return false;
		}
		return self::matches_state( $post_id, $state );
	}

	private static function matches_state( $post_id, $state ) {
		clean_post_cache( $post_id );
		if ( $state['content'] !== get_post_field( 'post_content', $post_id, 'raw' ) ) return false;
		foreach ( self::$meta_keys as $key ) if ( get_post_meta( $post_id, $key, false ) !== $state['meta'][ $key ] ) return false;
		return true;
	}

	/** option_name has a real UNIQUE index; postmeta's unique flag does not. */
	private static function acquire_lock( $post_id ) {
		global $wpdb;
		$key = self::LOCK . (int) $post_id;
		$token = wp_generate_uuid4();
		$value = array( 'token' => $token, 'expires' => time() + 900 );
		$old = maybe_unserialize( $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ) ) );
		if ( is_array( $old ) && isset( $old['expires'] ) && (int) $old['expires'] < time() ) self::delete_lock_value( $key, $old );
		// add_option() uses ON DUPLICATE KEY UPDATE in WordPress and can replace
		// a competing token after both workers read a missing option. Do not upsert.
		$inserted = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", $key, maybe_serialize( $value ) ) );
		if ( 1 !== $inserted ) return new WP_Error( 'busy', 'Nie udało się uzyskać blokady strony. Może trwać inny zapis; odśwież podgląd po jego zakończeniu.' );
		wp_cache_delete( $key, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		return $token;
	}

	private static function release_lock( $post_id, $token ) {
		$key = self::LOCK . (int) $post_id;
		$value = get_option( $key );
		if ( is_array( $value ) && isset( $value['token'] ) && hash_equals( $value['token'], $token ) ) self::delete_lock_value( $key, $value );
	}

	private static function delete_lock_value( $key, $value ) {
		global $wpdb;
		$deleted = $wpdb->delete( $wpdb->options, array( 'option_name' => $key, 'option_value' => maybe_serialize( $value ) ), array( '%s', '%s' ) );
		if ( $deleted ) { wp_cache_delete( $key, 'options' ); wp_cache_delete( 'notoptions', 'options' ); }
	}

	/** Native module source; no shortcode is executed while preparing a layout. */
	public static function materialize( $post ) {
		$post = get_post( $post );
		if ( ! $post || ! in_array( $post->post_type, array( 'page', 'post' ), true ) || strlen( $post->post_content ) > self::MAX_BYTES ) return new WP_Error( 'unsupported', 'Nie można przygotować układu tej strony.' );
		if ( self::authoritative( $post->ID ) ) return $post->post_content;
		if ( (int) get_option( 'page_on_front' ) === (int) $post->ID && 'page' === get_option( 'show_on_front' ) ) return self::home( $post );
		if ( (int) get_option( 'page_for_posts' ) === (int) $post->ID && 'page' === get_option( 'show_on_front' ) ) {
			return self::retain_functional( $post->post_content, self::heading( $post->post_title, 'Nasze realizacje' ) . self::section( self::row( array( self::module( 'et_pb_code', array( 'admin_label' => 'Realizacje — 24 zdjęcia i paginacja', 'module_class' => 'yn-divi-project-index' ), '[yoona_realizacje]' ) ), 'yn-divi-collection-row' ) ) );
		}
		$path = '/' . trim( (string) get_page_uri( $post->ID ), '/' ) . '/';
		if ( '/pelna-oferta/' === $path ) return self::retain_functional( $post->post_content, self::heading( $post->post_title, 'Od pomysłu do realizacji' ) . self::cards( Yoona_SEO_Design::offer_cards(), 'yn-divi-offer-catalog', 6 ) );
		if ( in_array( $path, array( '/o-nas/', '/kontakt/' ), true ) ) return self::company( $post, $path );
		$guides = Yoona_SEO_Service_Guides::data();
		if ( 'page' === $post->post_type && isset( $guides[ $path ] ) ) return self::service( $post, $path, $guides[ $path ] );
		$projects = Yoona_SEO_Project_Guides::data();
		if ( 'post' === $post->post_type ) {
			$project_path = '/' . trim( (string) wp_parse_url( get_permalink( $post ), PHP_URL_PATH ), '/' ) . '/';
			if ( isset( $projects[ $project_path ] ) ) {
				$guide = $projects[ $project_path ];
				if ( isset( $guide['title'], $guide['source_sha256'] ) && $post->post_title === $guide['title'] && in_array( hash( 'sha256', $post->post_content ), $guide['source_sha256'], true ) ) {
					$copy = self::text( '<p>' . self::html( $guide['summary'] ) . '</p>' );
					foreach ( array( 'location' => 'Miejsce realizacji', 'scope' => 'Co wykonaliśmy', 'materials' => 'Materiały' ) as $key => $label ) {
						if ( empty( $guide[ $key ] ) ) continue;
						$copy .= self::text( '<h2>' . self::html( $label ) . '</h2>' . ( is_array( $guide[ $key ] ) ? self::listing( $guide[ $key ] ) : '<p>' . self::html( $guide[ $key ] ) . '</p>' ) );
					}
					$after = '';
					if ( ! empty( $guide['planning'] ) ) $after .= self::text( '<h2>Planujesz podobną realizację?</h2><p>' . self::html( $guide['planning'] ) . '</p>' );
					foreach ( Yoona_SEO_Project_Guides::services( $guide ) as $service ) $after .= self::button( $service['title'], $service['url'] );
					$after .= self::button( 'Zapytaj o podobną realizację', Yoona_SEO_Design::route( 'contact' ) );
					return self::heading( $post->post_title, 'Nasze realizacje / Olsztyn / Ostróda / Warmia i Mazury', '', preg_match( '/<h1(?:\s|>)/i', $post->post_content ) ? 'p' : 'h1' ) . self::section( self::row( array( $copy ) ) ) . self::original( $post->post_content ) . self::section( self::row( array( $after ) ) );
				}
			}
		}
		return self::heading( $post->post_title, 'YOONA Creative Group', '', preg_match( '/<h1(?:\s|>)/i', $post->post_content ) ? 'p' : 'h1' ) . self::original( $post->post_content );
	}

	private static function service( $post, $path, $guide ) {
		$design = Yoona_SEO_Design::data();
		$source_path = isset( $guide['image_source_path'] ) ? $guide['image_source_path'] : $path;
		if ( empty( $design['offer_heroes'][ $source_path ] ) ) return self::original( $post->post_content );
		$image = $design['offer_heroes'][ $source_path ];
		if ( ! empty( $guide['hero_image'] ) ) $image['image'] = $guide['hero_image'];
		if ( ! empty( $guide['hero_alt'] ) ) $image['alt'] = $guide['hero_alt'];
		$definition = isset( $design['offer_heroes'][ $path ] ) ? $design['offer_heroes'][ $path ] : array();
		$owned = (bool) get_post_meta( $post->ID, '_yoona_growth_owned', true );
		if ( $owned ) {
			$hash = get_post_meta( $post->ID, '_yoona_growth_content_sha256', true );
			if ( ! is_string( $hash ) || ! hash_equals( $hash, hash( 'sha256', $post->post_content ) ) || $guide['title'] !== $post->post_title ) return self::original( $post->post_content );
			$body = '';
		} else {
			$body = Yoona_SEO_Offer_Hero::prepare_content( $post->post_content, $definition );
			if ( false === $body ) return self::original( $post->post_content );
		}
		$details = self::guide( $guide, isset( $definition['service_intro_mode'] ) && 'append' === $definition['service_intro_mode'] );
		if ( $owned ) $body = $details;
		elseif ( preg_match( '/\A\s*(\[et_pb_section(?:\s|\])[\s\S]*?\[\/et_pb_section\])/', $body, $first )
			&& ! empty( $definition['service_intro_sha256'] ) && in_array( hash( 'sha256', $first[1] ), $definition['service_intro_sha256'], true ) ) {
			$replacement = isset( $definition['service_intro_mode'] ) && 'append' === $definition['service_intro_mode'] ? $first[1] . $details : $details;
			$body = substr_replace( $body, $replacement, strpos( $body, $first[1] ), strlen( $first[1] ) );
		}
		$heading = preg_match( '/<h1(?:\s|>)/i', $body ) ? 'p' : 'h1';
		$copy = self::text( '<p>OFERTA YOONA</p>', 'Etykieta oferty', true ) . self::title( $post->post_title, $heading, true ) . self::text( '<p>' . self::html( $guide['lead'] ) . '</p>', 'Wprowadzenie oferty', true );
		$copy .= self::button( 'Zapytaj o wycenę', Yoona_SEO_Design::route( 'contact' ) );
		return self::section( self::row( array( $copy, self::image( $image ) ) ), true ) . self::original( $body );
	}

	private static function guide( $guide, $append ) {
		$intro = $append ? '<h2>Szczegóły zamówienia</h2>' : '<h2>' . self::html( $guide['title'] ) . '</h2><p>' . self::html( $guide['overview'] ) . '</p>';
		$rows = self::row( array( self::text( $intro, 'Wprowadzenie do oferty' ) ) );
		$columns = array();
		foreach ( array( 'applications' => 'Zastosowanie', 'materials' => 'Materiały i warianty', 'process' => 'Jak realizujemy zamówienie', 'pricing' => 'Od czego zależy cena', 'quote' => 'Co przesłać do wyceny' ) as $key => $label ) {
			if ( ! empty( $guide[ $key ] ) ) $columns[] = self::text( '<h2>' . self::html( $label ) . '</h2>' . self::listing( $guide[ $key ] ), $label );
		}
		foreach ( array_chunk( $columns, 3 ) as $chunk ) $rows .= self::row( $chunk );
		if ( ! empty( $guide['faq'] ) ) {
			$faq = '';
			foreach ( $guide['faq'] as $item ) $faq .= self::module( 'et_pb_accordion_item', array( 'title' => $item['q'] ), '<p>' . self::html( $item['a'] ) . '</p>' );
			$rows .= self::row( array( self::text( '<h2>Pytania przed zamówieniem</h2>' ) . self::module( 'et_pb_accordion', array( 'module_class' => 'yn-divi-faq' ), $faq ) ) );
		}
		$links = array_unique( array_merge( isset( $guide['children'] ) ? $guide['children'] : array(), isset( $guide['related'] ) ? $guide['related'] : array() ) );
		$definitions = Yoona_SEO_Service_Guides::data();
		$related = '';
		foreach ( $links as $path ) {
			$url = Yoona_SEO_Service_Guides::link( $path );
			if ( $url && isset( $definitions[ $path ]['title'] ) ) $related .= self::button( $definitions[ $path ]['title'], $url );
		}
		if ( $related ) $rows .= self::row( array( self::text( '<h2>Sprawdź także</h2>' ) . $related ) );
		$rows .= self::row( array( self::button( 'Prześlij projekt do wyceny', Yoona_SEO_Design::route( 'contact' ) ) ) );
		return self::section( $rows );
	}

	private static function company( $post, $path ) {
		$data = Yoona_SEO_Company_Pages::data();
		$body = $post->post_content;
		$removed = false;
		foreach ( isset( $data[ $path ]['omit'] ) ? $data[ $path ]['omit'] : array() as $definition ) {
			if ( ! isset( $definition['tag'], $definition['sha256'] ) || ! in_array( $definition['tag'], array( 'et_pb_section', 'et_pb_row', 'et_pb_code' ), true ) ) continue;
			$body = preg_replace_callback( '/' . get_shortcode_regex( array( $definition['tag'] ) ) . '/s', static function ( $match ) use ( $definition, &$removed ) {
				if ( in_array( hash( 'sha256', $match[0] ), $definition['sha256'], true ) ) { $removed = true; return ''; }
				return $match[0];
			}, $body );
		}
		if ( ! $removed && preg_match( '/<h[12](?:\s|>)/i', $body ) ) return self::original( $body );
		$contact = '/kontakt/' === $path;
		$title = $contact ? 'Kontakt. Jesteśmy blisko.' : 'Od projektu do montażu.';
		$lead = $contact ? 'Napisz lub zadzwoń. Prześlij projekt, wymiary albo zdjęcie miejsca. Poniedziałek–piątek, 7:30–15:30.' : 'Projekt, produkcja i montaż reklamy. Olsztyn, Ostróda i cała Polska.';
		$tag = preg_match( '/<h1(?:\s|>)/i', $body ) ? 'p' : 'h1';
		$copy = self::text( '<p>YOONA CREATIVE GROUP / ' . ( $contact ? 'OLSZTYN / OSTRÓDA' : 'O NAS' ) . '</p>', 'Etykieta strony' ) . self::title( $title, $tag ) . self::text( '<p>' . self::html( $lead ) . '</p>', 'Wprowadzenie strony' );
		if ( ! $contact ) $copy .= self::button( 'Realizacje', Yoona_SEO_Design::route( 'projects' ) ) . self::button( 'Kontakt', Yoona_SEO_Design::route( 'contact' ) );
		return self::section( self::row( array( $copy ) ) ) . self::original( $body );
	}

	private static function home( $post ) {
		$data = Yoona_SEO_Design::data();
		$cards = Yoona_SEO_Design::offer_cards();
		$slides = self::module( 'et_pb_slide', array( 'heading' => 'Robimy marki widoczne.', 'button_text' => 'Zobacz, co robimy', 'button_link' => '#oferta', 'background_image' => Yoona_SEO_Design::url( $data['hero']['image'] ), 'use_bg_overlay' => 'on', 'bg_overlay_color' => 'rgba(0,0,0,0.68)', 'heading_level' => 'h1' ), '<p>Projektujemy, produkujemy i montujemy reklamę, która działa w realnym świecie. Od pomysłu do efektu WOW.</p>' );
		$definitions = array(
			'/banery/' => array( 'Większy format. Wyraźny przekaz.', 'Banery i billboardy dopasowane do miejsca ekspozycji. Od projektu i druku po wykończenie oraz montaż.', 'Poznaj banery' ),
			'/litery-3d/' => array( 'Nadaj marce nowy wymiar.', 'Litery 3D, które budują charakter elewacji i wnętrza. Dobieramy materiał, wielkość i sposób montażu.', 'Zobacz litery 3D' ),
			'/oklejanie-samochodow/' => array( 'Twoja marka. W dobrym ruchu.', 'Reklama na samochodzie, którą łatwo zauważyć. Projekt i aplikacja grafiki dopasowanej do Twojego auta.', 'Poznaj oklejanie' ),
			'/neony/' => array( 'Pomysł, który świeci własnym światłem.', 'Świetlne napisy i znaki do lokali, biur oraz przestrzeni marki. Forma, kolor i wykonanie zgodne z Twoim pomysłem.', 'Zobacz neony' ),
		);
		foreach ( $definitions as $path => $slide ) {
			foreach ( $cards as $card ) {
				if ( rtrim( (string) wp_parse_url( $card['url'], PHP_URL_PATH ), '/' ) !== rtrim( $path, '/' ) ) continue;
				$slides .= self::module( 'et_pb_slide', array( 'heading' => $slide[0], 'button_text' => $slide[2], 'button_link' => $card['url'], 'background_image' => Yoona_SEO_Design::url( $card['image'] ), 'use_bg_overlay' => 'on', 'bg_overlay_color' => 'rgba(0,0,0,0.68)', 'heading_level' => 'h2' ), '<p>' . self::html( $slide[1] ) . '</p>' );
			}
		}
		$content = self::module( 'et_pb_section', array( 'fullwidth' => 'on', 'module_class' => 'yn-divi-authoring yn-divi-home-slider', 'admin_label' => 'Główny slider' ), self::module( 'et_pb_fullwidth_slider', array( 'show_arrows' => 'on', 'show_pagination' => 'on', 'auto' => 'off', 'body_font' => 'Manrope', 'header_font' => 'Archivo Black', 'header_font_size' => '54px', 'header_font_size_phone' => '32px', 'header_font_size_last_edited' => 'on|phone' ), $slides ) );
		$content .= self::section( self::row( array( self::text( '<h2>Projekt</h2><p>Pomysł nabiera kształtu</p>', '', true ), self::text( '<h2>Druk</h2><p>Kolor, format, detal</p>', '', true ), self::text( '<h2>Produkcja</h2><p>Własne wykonanie</p>', '', true ), self::text( '<h2>Montaż</h2><p>Efekt w Twojej przestrzeni</p>', '', true ) ) ), true );
		$content .= self::heading( 'Od pomysłu do efektu WOW.', 'Nasze możliwości', 'oferta', 'h2' ) . self::cards( array_slice( $cards, 0, 8 ), '', 4 );
		if ( count( $cards ) > 8 ) {
			$rows = self::row( array( self::text( '<h3>Wszystkie możliwości dla Twojej marki</h3>' ) ) );
			foreach ( array_chunk( array_slice( $cards, 8 ), 4 ) as $chunk ) {
				$columns = array();
				foreach ( $chunk as $card ) $columns[] = self::button( $card['name'], $card['url'] );
				$rows .= self::row( $columns );
			}
			$content .= self::section( $rows );
		}
		$content .= self::section( self::row( array( self::button( 'Poznaj pełną ofertę', Yoona_SEO_Design::route( 'services' ) ) ) ) );
		$process = array();
		foreach ( array( 'Rozmawiamy' => 'Poznajemy Twoje cele, potrzeby i miejsce realizacji.', 'Projektujemy' => 'Tworzymy koncepcję, która pasuje do Twojej marki.', 'Produkujemy' => 'W naszym zapleczu projekt zamienia się w gotową reklamę.', 'Montujemy' => 'Docieramy na miejsce i dbamy o prawidłowe wykonanie montażu.', 'Robimy efekt' => 'Twoja marka zyskuje widoczność w realnej przestrzeni.' ) as $label => $copy ) $process[] = self::text( '<h3>' . self::html( $label ) . '</h3><p>' . self::html( $copy ) . '</p>', $label, true );
		$content .= self::section( self::row( array( self::text( '<h2>Jeden zespół. Cały proces.</h2>', '', true ) ) ) . self::row( $process ), true, 'proces' );
		$content .= self::heading( 'Nie opowiadamy. Pokazujemy.', 'Nasze realizacje', 'realizacje', 'h2' );
		$projects = array();
		foreach ( isset( $data['projects'] ) ? $data['projects'] : array() as $project ) { $project['url'] = Yoona_SEO_Design::url( $project['url'] ); $projects[] = $project; }
		$content .= self::cards( $projects ) . self::section( self::row( array( self::button( 'Wszystkie realizacje', Yoona_SEO_Design::route( 'projects' ) ) ) ) );
		$content .= self::section( self::row( array( self::text( '<h2>Agencja? Nie tylko.</h2><p>Tworzymy reklamę nie tylko na ekranie. Łączymy pomysł, projekt i własną produkcję. Realizujemy to, co wspólnie zaplanujemy.</p>' ) . self::button( 'Poznaj nas', Yoona_SEO_Design::route( 'about' ) ) ) ) );
		$about = self::text( '<h2>Agencja reklamowa. Olsztyn i Ostróda.</h2><p>Reklama w Olsztynie i Ostródzie — od projektu do montażu. Wykonujemy reklamę świetlną, kasetony reklamowe, litery 3D, tablice, ledony i neony.</p><p>Druk wielkoformatowy i banery reklamowe łączymy z oklejaniem samochodów i szyb. Oferujemy też zmianę koloru samochodu, beczki reklamowe, markizy oraz frezowanie CNC.</p><p>Dobieramy rozwiązanie do miejsca, sposobu użytkowania i budżetu. Obsługujemy firmy z Warmii i Mazur oraz realizujemy zlecenia w całej Polsce.</p>' ) . self::button( 'Poznaj YOONA', Yoona_SEO_Design::route( 'about' ) );
		$content .= self::section( self::row( array( $about, self::image( $data['about'] ) ) ) );
		// A deliberately editorial opt-in may be extended by site-owned modules.
		return self::retain_functional( $post->post_content, apply_filters( 'yoona_seo_divi_home_content', $content, $post ) );
	}

	/** Preserve whole functional subtrees; reject ungrouped embeds rather than lose them. */
	private static function retain_functional( $source, $generated ) {
		$kept = '';
		$remainder = preg_replace_callback( '/' . get_shortcode_regex( array( 'et_pb_section' ) ) . '/s', static function ( $match ) use ( &$kept ) {
			if ( self::functional( $match[0] ) ) $kept .= $match[0];
			return '';
		}, $source );
		if ( self::functional( $remainder ) ) return new WP_Error( 'unmapped_functional_content', 'Treść zawiera dodatkowy formularz, kod lub osadzenie poza sekcją Divi. Najpierw przenieś ten element do sekcji Divi; niczego nie usunięto.' );
		return $generated . $kept;
	}

	private static function functional( $source ) {
		if ( preg_match( '~<(?:form|iframe|script|video|audio)\b~i', $source ) ) return true;
		preg_match_all( '/\[(?!\/)([a-z][a-z0-9_-]*)(?=[\s\]])/i', $source, $matches );
		$visual = array( 'et_pb_section', 'et_pb_row', 'et_pb_row_inner', 'et_pb_column', 'et_pb_column_inner', 'et_pb_text', 'et_pb_image', 'et_pb_fullwidth_image', 'et_pb_fullwidth_header', 'et_pb_button', 'et_pb_blurb', 'et_pb_divider', 'et_pb_slider', 'et_pb_fullwidth_slider', 'et_pb_slide', 'et_pb_toggle', 'et_pb_accordion', 'et_pb_accordion_item', 'et_pb_social_media_follow', 'et_pb_social_media_follow_network', 'et_pb_team_member', 'et_pb_testimonial', 'et_pb_number_counter', 'et_pb_circle_counter', 'et_pb_counters', 'et_pb_counter', 'caption' );
		foreach ( $matches[1] as $tag ) if ( ! in_array( $tag, $visual, true ) ) return true;
		return false;
	}

	private static function original( $content ) {
		if ( '' === trim( $content ) ) return '';
		return false !== strpos( $content, '[et_pb_section' ) ? $content : self::section( self::row( array( self::text( $content, 'Zachowana treść strony' ) ) ) );
	}

	private static function heading( $title, $eyebrow, $id = '', $tag = 'h1' ) {
		return self::section( self::row( array( self::text( '<p>' . self::html( $eyebrow ) . '</p>', 'Etykieta strony' ) . self::title( $title, $tag ) ) ), false, $id );
	}

	private static function cards( $cards, $class = '', $per_row = 3 ) {
		$rows = '';
		foreach ( array_chunk( $cards, $per_row ) as $chunk ) {
			$columns = array();
			foreach ( $chunk as $card ) $columns[] = self::image( $card, isset( $card['url'] ) ? $card['url'] : '' ) . self::text( '<h2><a href="' . esc_url( $card['url'] ) . '">' . self::html( $card['name'] ) . '</a></h2>', $card['name'] );
			$rows .= self::row( $columns, $class ? 'yn-divi-collection-row ' . $class : '' );
		}
		return self::section( $rows );
	}

	private static function section( $rows, $dark = false, $id = '' ) {
		$attributes = array( 'module_class' => 'yn-divi-authoring', 'background_color' => $dark ? '#111210' : '#f7f6f2', 'custom_padding' => '32px|0px|32px|0px', 'custom_padding_phone' => '22px|0px|22px|0px', 'custom_padding_last_edited' => 'on|phone' );
		if ( $id ) $attributes['module_id'] = $id;
		return self::module( 'et_pb_section', $attributes, $rows );
	}

	private static function row( $columns, $class = '' ) {
		$count = count( $columns );
		if ( ! $count ) return '';
		$type = $count > 1 && $count <= 6 ? '1_' . $count : '4_4';
		$body = '';
		foreach ( $columns as $column ) $body .= self::module( 'et_pb_column', array( 'type' => $type ), $column );
		return self::module( 'et_pb_row', array( 'module_class' => $class, 'width' => $class ? '100%' : '92%', 'max_width' => $class ? 'none' : '1600px', 'custom_padding' => '12px|0px|12px|0px', 'use_custom_gutter' => 'on', 'gutter_width' => '2' ), $body );
	}

	private static function text( $html, $label = '', $dark = false ) {
		return self::module( 'et_pb_text', array( 'admin_label' => $label ? $label : 'Tekst', 'text_font' => 'Manrope', 'text_text_color' => $dark ? '#f4f4ee' : '#292c28', 'text_font_size' => '15px', 'text_line_height' => '1.65em', 'header_font' => 'Archivo Black', 'header_text_color' => $dark ? '#ffffff' : '#121410', 'header_font_size' => '40px', 'header_font_size_phone' => '28px', 'header_font_size_last_edited' => 'on|phone', 'header_2_font' => 'Manrope|800', 'header_2_text_color' => $dark ? '#ffffff' : '#121410', 'header_2_font_size' => '22px', 'header_2_font_size_phone' => '20px', 'header_2_font_size_last_edited' => 'on|phone', 'header_3_font' => 'Manrope|800', 'header_3_text_color' => $dark ? '#ffffff' : '#121410', 'header_3_font_size' => '18px' ), $html );
	}

	private static function title( $title, $tag = 'h1', $dark = false ) {
		$attributes = array( 'admin_label' => 'Tytuł sekcji', 'text_font' => 'Archivo Black', 'text_text_color' => $dark ? '#ffffff' : '#121410', 'text_font_size' => '40px', 'text_font_size_phone' => '28px', 'text_font_size_last_edited' => 'on|phone', 'text_line_height' => '1.18em' );
		if ( in_array( $tag, array( 'h1', 'h2' ), true ) ) {
			$prefix = 'h2' === $tag ? 'header_2_' : 'header_';
			$attributes[ $prefix . 'font' ] = 'Archivo Black';
			$attributes[ $prefix . 'text_color' ] = $dark ? '#ffffff' : '#121410';
			$attributes[ $prefix . 'font_size' ] = '40px';
			$attributes[ $prefix . 'font_size_phone' ] = '28px';
			$attributes[ $prefix . 'font_size_last_edited' ] = 'on|phone';
		}
		return self::module( 'et_pb_text', $attributes, '<' . $tag . '>' . self::html( $title ) . '</' . $tag . '>' );
	}

	private static function image( $item, $url = '' ) {
		return self::module( 'et_pb_image', array( 'src' => Yoona_SEO_Design::url( $item['image'] ), 'alt' => isset( $item['alt'] ) ? $item['alt'] : '', 'url' => $url, 'show_in_lightbox' => $url ? 'off' : 'on', 'force_fullwidth' => 'on', 'admin_label' => isset( $item['name'] ) ? $item['name'] : 'Zdjęcie' ) );
	}

	private static function button( $label, $url ) {
		return self::module( 'et_pb_button', array( 'button_text' => $label, 'button_url' => $url, 'custom_button' => 'on', 'button_font' => 'Manrope|800', 'button_text_size' => '13px', 'button_text_color' => '#111210', 'button_bg_color' => '#ff7900', 'button_border_color' => '#ff7900', 'button_border_radius' => '0px', 'custom_padding' => '13px|18px|13px|18px', 'admin_label' => $label ) );
	}

	private static function listing( $items ) {
		$html = '<ul>';
		foreach ( $items as $item ) $html .= '<li>' . self::html( $item ) . '</li>';
		return $html . '</ul>';
	}

	private static function html( $text ) {
		return str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), esc_html( (string) $text ) );
	}

	private static function module( $tag, $attributes = array(), $content = '' ) {
		$opening = '[' . $tag;
		foreach ( $attributes as $name => $value ) $opening .= ' ' . $name . '="' . str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), esc_attr( (string) $value ) ) . '"';
		return $opening . ']' . $content . '[/' . $tag . ']';
	}
}
