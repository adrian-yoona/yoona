<?php
/**
 * Polish administration screen for reviewing and applying a local SEO bundle.
 *
 * @package Yoona_SEO
 */

defined( 'ABSPATH' ) || exit;

final class Yoona_SEO_Admin {

	const PAGE_SLUG = 'yoona-seo';
	const IMPORT_OPTION = 'yoona_seo_imported_bundle';
	const MAX_UPLOAD_BYTES = 2097152;

	/** Register the screen and authenticated, POST-only actions. */
	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_yoona_seo_import', array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_post_yoona_seo_apply', array( __CLASS__, 'handle_apply' ) );
		add_action( 'admin_post_yoona_seo_rollback', array( __CLASS__, 'handle_rollback' ) );
		add_action( 'admin_post_yoona_seo_diagnostics', array( __CLASS__, 'handle_diagnostics' ) );
		add_action( 'admin_post_yoona_seo_bundled', array( __CLASS__, 'handle_bundled' ) );
	}

	public static function add_menu() {
		add_management_page( 'Yoona SEO', 'Yoona SEO', 'manage_options', self::PAGE_SLUG, array( __CLASS__, 'render' ) );
	}

	/** Render a preview without changing site content. */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( 'Nie masz uprawnień do tej strony.' ), '', array( 'response' => 403 ) );
		}

		$bundle  = Yoona_SEO_Engine::load_bundle();
		$preview = is_wp_error( $bundle ) ? $bundle : Yoona_SEO_Engine::preview( $bundle );
		$notice  = get_transient( self::notice_key() );
		delete_transient( self::notice_key() );
		$pages   = ! is_wp_error( $preview ) && isset( $preview['pages'] ) && is_array( $preview['pages'] ) ? $preview['pages'] : array();
		$history = ! is_wp_error( $preview ) && isset( $preview['history'] ) && is_array( $preview['history'] ) ? $preview['history'] : Yoona_SEO_Engine::list_batches();
		$history = is_array( $history ) ? $history : array();
		$ready   = ! is_wp_error( $preview ) && ! empty( $preview['audit_verified'] ) && ! empty( $preview['site_match'] );
		?>
		<div class="wrap yoona-seo-admin">
			<h1>Yoona — wygląd i SEO <small style="font-size:14px">wersja <?php echo esc_html( YOONA_SEO_VERSION ); ?></small></h1>
			<p>Wygląd witryny i zapis tekstów mają osobne ustawienia. Tutaj sprawdzisz stan obu zmian.</p>
			<style>
				.yoona-seo-admin{max-width:1180px}.yoona-seo-admin .yoona-card{background:#fff;border:1px solid #c3c4c7;padding:20px;margin:20px 0}.yoona-seo-admin .yoona-step{margin-top:0}.yoona-seo-admin .yoona-page{border:1px solid #dcdcde;padding:16px;margin:16px 0}.yoona-seo-admin .yoona-page h3{margin:0 0 12px}.yoona-seo-admin .yoona-compare{table-layout:fixed;width:100%;border-collapse:collapse;margin:12px 0}.yoona-seo-admin .yoona-compare th,.yoona-seo-admin .yoona-compare td{border:1px solid #dcdcde;padding:10px;vertical-align:top;text-align:left;overflow-wrap:anywhere;white-space:pre-wrap}.yoona-seo-admin .yoona-compare th:first-child{width:18%}.yoona-seo-admin textarea{width:100%;min-height:150px;white-space:pre-wrap}.yoona-seo-admin .yoona-content-columns{display:grid;grid-template-columns:1fr 1fr;gap:16px}.yoona-seo-admin .yoona-inline-form{display:inline}.yoona-seo-admin .yoona-path{font-family:monospace;overflow-wrap:anywhere}.yoona-seo-admin .yoona-muted{color:#50575e}.yoona-seo-admin .yoona-history td{vertical-align:middle}.yoona-seo-admin details{margin:12px 0}.yoona-seo-admin summary{cursor:pointer;font-weight:600}@media(max-width:782px){.yoona-seo-admin .yoona-content-columns{grid-template-columns:1fr}.yoona-seo-admin .yoona-card{padding:12px}}
			</style>
			<?php self::render_notice( $notice ); ?>
			<?php Yoona_SEO_Design::render_panel(); ?>
			<?php Yoona_SEO_Growth_Pages::render_panel(); ?>
			<?php self::render_deployment_status( $preview ); ?>
			<?php if ( ! is_wp_error( $bundle ) && ! empty( $bundle['audit_verified'] ) ) : ?>
				<div class="notice notice-info inline"><p><strong>Gotowy pakiet: <?php echo esc_html( count( $bundle['pages'] ) ); ?> stron i wpisów.</strong> <?php echo 'rank_math' === ( isset( $bundle['seo_storage'] ) ? $bundle['seo_storage'] : '' ) ? esc_html( 'Tytuły i opisy zostaną zapisane w polach Rank Math. Teksty Divi zmienią się w istniejących modułach.' ) : ''; ?></p></div>
			<?php endif; ?>
			<?php if ( is_wp_error( $preview ) ) : ?>
				<div class="notice notice-error inline"><p><?php echo esc_html( $preview->get_error_message() ); ?></p></div>
			<?php elseif ( empty( $preview['audit_verified'] ) ) : ?>
				<div class="notice notice-warning inline"><p><strong>Audyt nie jest jeszcze zakończony.</strong> Paczka jest oznaczona jako nieukończony audyt. Pobierz plik diagnostyczny do dalszego audytu, a następnie zaimportuj przygotowaną paczkę zmian. Nic nie zostało automatycznie wgrane na stronę.</p></div>
			<?php endif; ?>
			<?php if ( ! is_wp_error( $preview ) && empty( $preview['site_match'] ) ) : ?>
				<div class="notice notice-error inline"><p>Paczka jest przeznaczona dla innej witryny. Zastosowanie zmian jest zablokowane.</p></div>
			<?php endif; ?>
			<?php if ( ! is_wp_error( $preview ) && ! empty( $preview['warnings'] ) && is_array( $preview['warnings'] ) ) : ?>
				<div class="notice notice-warning inline"><ul>
					<?php foreach ( $preview['warnings'] as $warning ) : ?>
						<?php if ( is_scalar( $warning ) ) : ?><li><?php echo esc_html( (string) $warning ); ?></li><?php endif; ?>
					<?php endforeach; ?>
				</ul></div>
			<?php endif; ?>

			<section class="yoona-card" aria-labelledby="yoona-prepare">
				<h2 id="yoona-prepare" class="yoona-step">Pakiet zmian i diagnostyka</h2>
				<p>Po aktualizacji wtyczki gotowy pakiet jest dostępny poniżej. Jeśli wcześniej wczytano inną paczkę, przycisk przywróci paczkę dołączoną do tej wersji wtyczki. Sam wybór paczki nie zmienia strony.</p>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="yoona_seo_bundled">
					<?php wp_nonce_field( 'yoona_seo_bundled' ); ?>
					<?php submit_button( 'Użyj pakietu dołączonego do wtyczki', 'secondary', 'submit', false ); ?>
				</form>
				<p>Jeśli audyt wymaga dostępu do treści witryny, pobierz diagnostykę i przekaż ten plik osobie przygotowującej teksty. Traktuj go jak kopię treści swojej strony.</p>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="yoona_seo_diagnostics">
					<?php wp_nonce_field( 'yoona_seo_diagnostics' ); ?>
					<?php submit_button( 'Pobierz diagnostykę do audytu (JSON)', 'secondary', 'submit', false ); ?>
				</form>
				<hr>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
					<input type="hidden" name="action" value="yoona_seo_import">
					<?php wp_nonce_field( 'yoona_seo_import' ); ?>
					<p><label for="yoona-bundle"><strong>Gotowa paczka zmian (plik JSON, do 2 MB)</strong></label></p>
					<input id="yoona-bundle" type="file" name="yoona_bundle" accept=".json,application/json" required>
					<?php submit_button( 'Wczytaj paczkę do podglądu', 'secondary', 'submit', false ); ?>
					<p class="description">Import zastępuje paczkę w tym panelu, bez zapisywania tekstów lub SEO. Nowy wygląd włącza się osobno po aktualizacji wtyczki. Wczytuj pliki przygotowane przez zaufaną osobę.</p>
				</form>
			</section>

			<section class="yoona-card" aria-labelledby="yoona-preview">
				<h2 id="yoona-preview" class="yoona-step">1. Podgląd</h2>
				<p>Porównaj obecne i proponowane wartości. Zaznacz tylko strony, które chcesz teraz zaktualizować.</p>
				<?php if ( empty( $pages ) ) : ?>
					<p>Brak przygotowanych zmian do wyświetlenia. Po wczytaniu poprawnej paczki zobaczysz tutaj podgląd każdej strony.</p>
				<?php endif; ?>
				<form id="yoona-seo-apply-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<p><button type="button" class="button" id="yoona-select-all">Zaznacz wszystkie gotowe</button> <button type="button" class="button" id="yoona-select-none">Odznacz wszystkie</button></p>
					<input type="hidden" name="action" value="yoona_seo_apply">
					<input type="hidden" name="bundle_hash" value="<?php echo esc_attr( is_wp_error( $bundle ) ? '' : self::bundle_hash( $bundle ) ); ?>">
					<?php wp_nonce_field( 'yoona_seo_apply' ); ?>
					<?php $applicable = 0; ?>
					<?php foreach ( $pages as $index => $page ) : ?>
						<?php
						if ( ! is_array( $page ) ) {
							continue;
						}
						$path      = isset( $page['path'] ) ? (string) $page['path'] : '';
						$current   = isset( $page['current'] ) && is_array( $page['current'] ) ? $page['current'] : array();
						$sources   = isset( $page['current_source'] ) && is_array( $page['current_source'] ) ? $page['current_source'] : array();
						$new       = isset( $page['new'] ) && is_array( $page['new'] ) ? $page['new'] : array();
						$can_apply = $ready && ! empty( $page['can_apply'] );
						$input_id  = 'yoona-page-' . absint( $index );
						if ( $can_apply ) {
							++$applicable;
						}
						?>
						<article class="yoona-page">
							<h3><label for="<?php echo esc_attr( $input_id ); ?>"><input id="<?php echo esc_attr( $input_id ); ?>" type="checkbox" name="selected_paths[]" value="<?php echo esc_attr( $path ); ?>" <?php disabled( ! $can_apply ); ?>> <span class="yoona-path"><?php echo esc_html( $path ); ?></span></label></h3>
							<?php if ( ! empty( $page['url'] ) ) : ?><p><a href="<?php echo esc_url( $page['url'] ); ?>" target="_blank" rel="noopener noreferrer">Otwórz stronę w nowej karcie</a></p><?php endif; ?>
							<?php if ( ! empty( $page['messages'] ) && is_array( $page['messages'] ) ) : ?>
								<ul><?php foreach ( $page['messages'] as $message ) : ?><?php if ( is_scalar( $message ) ) : ?><li><?php echo esc_html( (string) $message ); ?></li><?php endif; ?><?php endforeach; ?></ul>
							<?php endif; ?>
							<?php if ( ! $can_apply ) : ?><p class="yoona-muted"><?php echo 'unchanged' === ( isset( $page['status'] ) ? $page['status'] : '' ) ? esc_html( 'Te wartości są już zapisane. Aktualizacja nie jest potrzebna.' ) : esc_html( 'Ta strona nie jest obecnie gotowa do aktualizacji. Sprawdź komunikaty i paczkę zmian.' ); ?></p><?php endif; ?>
							<table class="yoona-compare">
								<caption class="screen-reader-text"><?php echo esc_html( 'Porównanie zmian dla ' . $path ); ?></caption>
								<thead><tr><th scope="col">Pole</th><th scope="col">Obecnie</th><th scope="col">Po aktualizacji</th></tr></thead>
								<tbody>
									<?php self::render_comparison_row( 'Tytuł SEO', 'seo_title', $current, $new, $sources ); ?>
									<?php self::render_comparison_row( 'Opis w wyszukiwarce', 'meta_description', $current, $new, $sources ); ?>
									<?php if ( array_key_exists( 'post_title', $new ) ) : ?><?php self::render_comparison_row( 'Tytuł strony', 'post_title', $current, $new ); ?><?php endif; ?>
								</tbody>
								</table>
							<?php if ( ! empty( $page['divi_preview'] ) ) : ?>
								<details><summary>Porównaj zmiany tekstów Divi (<?php echo esc_html( count( $page['divi_preview'] ) ); ?> modułów)</summary>
								<?php foreach ( $page['divi_preview'] as $patch ) : ?>
									<h4><?php echo esc_html( ( 'et_pb_toggle' === $patch['module_type'] ? 'Odpowiedź FAQ ' : 'Moduł tekstowy ' ) . ( 1 + $patch['module_index'] ) ); ?></h4>
									<div class="yoona-content-columns"><div><strong>Obecnie</strong><pre style="white-space:pre-wrap;overflow-wrap:anywhere"><?php echo esc_html( $patch['before'] ); ?></pre></div><div><strong>Po aktualizacji</strong><pre style="white-space:pre-wrap;overflow-wrap:anywhere"><?php echo esc_html( $patch['after'] ); ?></pre></div></div>
								<?php endforeach; ?></details>
							<?php endif; ?>
							<?php if ( array_key_exists( 'content', $new ) ) : ?>
								<details>
									<summary>Porównaj pełną treść strony</summary>
									<p>Treść pokazujemy jako tekst i kod edytora WordPress. Sprawdź ją przed wgraniem.</p>
									<div class="yoona-content-columns">
										<div><label for="<?php echo esc_attr( $input_id . '-old' ); ?>"><strong>Obecna treść</strong></label><textarea id="<?php echo esc_attr( $input_id . '-old' ); ?>" rows="10" readonly><?php echo esc_textarea( isset( $current['content'] ) ? (string) $current['content'] : '' ); ?></textarea></div>
										<div><label for="<?php echo esc_attr( $input_id . '-new' ); ?>"><strong>Proponowana treść</strong></label><textarea id="<?php echo esc_attr( $input_id . '-new' ); ?>" rows="10" readonly><?php echo esc_textarea( (string) $new['content'] ); ?></textarea></div>
									</div>
								</details>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
					<h2>2. Wgraj zmiany</h2>
					<p>Przycisk zapisze zmiany tylko dla zaznaczonych stron. Wtyczka zachowa kopię poprzednich wartości, dostępną poniżej.</p>
					<?php submit_button( 'Wgraj zmiany na zaznaczone strony', 'primary', 'submit', false, 0 === $applicable ? array( 'disabled' => 'disabled' ) : array() ); ?>
					<p>Podczas wgrywania pozostaw tę kartę otwartą. Zmiany będą zapisywane po 10 stron, z osobną kopią każdej partii.</p>
					<div id="yoona-progress" hidden><progress style="width:100%" value="0" max="1"></progress><p role="status" aria-live="polite"></p><ul></ul><button type="button" class="button" id="yoona-refresh" hidden>Odśwież podgląd i historię</button></div>
					<noscript><p>Włącz JavaScript, aby wdrożyć cały pakiet partiami. Bez JavaScript zaznacz najwyżej 10 stron na jeden zapis.</p></noscript>
				</form>
			</section>

			<section class="yoona-card" aria-labelledby="yoona-history">
				<h2 id="yoona-history" class="yoona-step">3. Cofnij zmiany</h2>
				<p>Każde wgranie tworzy osobną partię. Cofnięcie przywraca poprzednie wartości, o ile nie zostały później zmienione w edytorze lub przez inną wtyczkę.</p>
				<p>Jeśli zapis został przerwany, przy partii pojawi się komunikat „Wymaga odzyskania”. Użyj przycisku cofania i sprawdź wyniki dla każdej strony.</p>
				<?php if ( empty( $history ) ) : ?>
					<p>Nie zapisano jeszcze żadnej partii zmian.</p>
				<?php else : ?>
					<table class="widefat striped yoona-history"><thead><tr><th scope="col">Data</th><th scope="col">Strony</th><th scope="col">Stan</th><th scope="col">Działanie</th></tr></thead><tbody>
						<?php foreach ( $history as $batch ) : ?>
							<?php
							if ( ! is_array( $batch ) || empty( $batch['batch_id'] ) ) {
								continue;
							}
							$batch_status   = isset( $batch['status'] ) && is_string( $batch['status'] ) ? $batch['status'] : '';
							$can_rollback   = self::batch_can_rollback( $batch );
							$needs_recovery = ! empty( $batch['recovery_needed'] ) || ( $can_rollback && in_array( $batch_status, array( 'prepared', 'failed', 'applying', 'rolling_back' ), true ) );
							?>
							<tr>
								<td><?php echo esc_html( isset( $batch['created_at'] ) ? (string) $batch['created_at'] : '' ); ?></td>
								<td><?php echo esc_html( isset( $batch['count'] ) ? (string) absint( $batch['count'] ) : '0' ); ?></td>
								<td><?php if ( $needs_recovery ) : ?><strong>Wymaga odzyskania</strong><br><?php endif; ?><?php echo esc_html( self::status_label( $batch_status ) ); ?></td>
								<td>
									<?php if ( $can_rollback ) : ?>
										<form class="yoona-inline-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
											<input type="hidden" name="action" value="yoona_seo_rollback">
											<input type="hidden" name="batch_id" value="<?php echo esc_attr( $batch['batch_id'] ); ?>">
											<?php wp_nonce_field( 'yoona_seo_rollback_' . $batch['batch_id'] ); ?>
											<?php submit_button( 'Cofnij tę partię', 'secondary', 'submit', false ); ?>
										</form>
									<?php else : ?>—<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody></table>
				<?php endif; ?>
			</section>
		</div>
		<?php self::batch_script(); ?>
		<?php
	}

	/** Read current values; do not mistake an installed bundle for deployed SEO. */
	private static function render_deployment_status( $preview ) {
		if ( is_wp_error( $preview ) ) {
			return;
		}
		$counts = array( 'ready' => 0, 'unchanged' => 0, 'blocked' => 0 );
		foreach ( $preview['pages'] as $page ) {
			if ( 'unchanged' === $page['status'] ) {
				++$counts['unchanged'];
			} elseif ( ! empty( $page['can_apply'] ) ) {
				++$counts['ready'];
			} else {
				++$counts['blocked'];
			}
		}
		?>
		<section class="yoona-card" aria-labelledby="yoona-deployment-status">
			<h2 id="yoona-deployment-status" class="yoona-step">Stan tekstów i SEO</h2>
			<p><strong><?php echo esc_html( $counts['unchanged'] ); ?> zgodnych z pakietem</strong> · <strong><?php echo esc_html( $counts['ready'] ); ?> gotowych do zapisu</strong> · <strong><?php echo esc_html( $counts['blocked'] ); ?> wymagających sprawdzenia</strong></p>
			<?php if ( $counts['ready'] ) : ?>
				<p>Gotowe poprawki nie są jeszcze zapisane na tych stronach. Przejdź do podglądu, zaznacz pozycje i uruchom <strong>Wgraj zmiany na zaznaczone strony</strong>.</p>
			<?php elseif ( $counts['unchanged'] && ! $counts['blocked'] ) : ?>
				<p>Wszystkie wartości objęte pakietem są już zapisane. Tytuły i opisy Rank Math sprawdzisz w źródle strony lub podglądzie SEO; zmiana metadanych nie przebudowuje jej układu. Wygląd kontroluje ustawienie powyżej.</p>
			<?php endif; ?>
			<?php if ( $counts['blocked'] ) : ?>
				<p>Część pozycji ma blokadę. Szczegółowy powód znajdziesz przy adresie w podglądzie, np. nowszą edycję treści, brak Rank Math albo inną domenę. Nie pomijaj kontroli zgodności.</p>
			<?php endif; ?>
			<p><a class="button" href="#yoona-preview">Sprawdź podgląd tekstów</a></p>
		</section>
		<?php
	}

	/** Import only; never apply a bundle as a side effect. */
	public static function handle_import() {
		self::authorize( 'yoona_seo_import' );
		if ( ! isset( $_FILES['yoona_bundle'] ) || ! is_array( $_FILES['yoona_bundle'] ) ) {
			self::finish( 'error', 'Wybierz plik JSON z paczką zmian.' );
		}
		$file = $_FILES['yoona_bundle']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Inspected as an uploaded file; its content is parsed and validated, never executed.
		if ( ! isset( $file['error'] ) || ! is_scalar( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] || ! isset( $file['tmp_name'] ) || ! is_string( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			self::finish( 'error', 'Nie udało się odebrać pliku. Sprawdź limit plików na serwerze i spróbuj ponownie.' );
		}
		$size = filesize( $file['tmp_name'] );
		if ( false === $size || 0 === $size || $size > self::MAX_UPLOAD_BYTES ) {
			self::finish( 'error', 'Plik musi zawierać dane JSON i mieć najwyżej 2 MB.' );
		}
		$json = file_get_contents( $file['tmp_name'], false, null, 0, self::MAX_UPLOAD_BYTES + 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local PHP upload only.
		if ( false === $json || strlen( $json ) > self::MAX_UPLOAD_BYTES ) {
			self::finish( 'error', 'Nie udało się odczytać pliku lub plik przekracza limit 2 MB.' );
		}
		$bundle = json_decode( $json, true, 64 );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			self::finish( 'error', 'Plik nie zawiera poprawnej paczki JSON. ' . json_last_error_msg() );
		}
		if ( ! is_array( $bundle ) ) {
			self::finish( 'error', 'Paczka JSON musi zawierać obiekt z konfiguracją i listą stron.' );
		}
		$validated = Yoona_SEO_Engine::validate_bundle( $bundle );
		if ( is_wp_error( $validated ) ) {
			self::finish( 'error', $validated->get_error_message() );
		}
		update_option( self::IMPORT_OPTION, $validated, false );
		if ( get_option( self::IMPORT_OPTION ) !== $validated ) {
			self::finish( 'error', 'Nie udało się zapisać paczki do podglądu. Sprawdź połączenie z bazą danych.' );
		}
		self::finish( 'success', 'Paczka została wczytana. Sprawdź podgląd i wybierz strony. Treści witryny nie zostały zmienione.' );
	}

	public static function handle_bundled() {
		self::authorize( 'yoona_seo_bundled' );
		delete_option( self::IMPORT_OPTION );
		if ( null !== get_option( self::IMPORT_OPTION, null ) ) { self::finish( 'error', 'Nie udało się wybrać dołączonej paczki.' ); }
		self::finish( 'success', 'Wybrano pakiet dołączony do wtyczki. Sprawdź podgląd. Treść witryny nie została zmieniona.' );
	}

	public static function handle_apply() {
		self::authorize( 'yoona_seo_apply' );
		$bundle = Yoona_SEO_Engine::load_bundle();
		if ( is_wp_error( $bundle ) ) {
			self::finish( 'error', $bundle->get_error_message() );
		}
		$submitted_hash = isset( $_POST['bundle_hash'] ) && is_string( $_POST['bundle_hash'] ) ? wp_unslash( $_POST['bundle_hash'] ) : '';
		if ( ! hash_equals( self::bundle_hash( $bundle ), $submitted_hash ) ) {
			self::finish( 'error', 'Paczka zmieniła się od otwarcia podglądu. Sprawdź aktualne propozycje i ponownie wybierz strony.' );
		}
		$paths = isset( $_POST['selected_paths'] ) && is_array( $_POST['selected_paths'] ) ? wp_unslash( $_POST['selected_paths'] ) : array();
		if ( empty( $paths ) || count( $paths ) > 200 ) {
			self::finish( 'error', 'Zaznacz co najmniej jedną stronę w podglądzie (najwyżej 200 naraz).' );
		}
		foreach ( $paths as $path ) {
			if ( ! is_string( $path ) || strlen( $path ) > 2048 ) {
				self::finish( 'error', 'Lista wybranych stron jest niepoprawna. Zaznacz strony ponownie.' );
			}
		}
		if ( count( $paths ) > 10 ) {
			self::finish( 'error', 'Jedna partia może zawierać najwyżej 10 stron. Użyj przycisku w panelu z włączonym JavaScript, aby wykonać cały pakiet partiami.' );
		}
		$result = Yoona_SEO_Engine::apply( $bundle, array_values( array_unique( $paths ) ) );
		if ( self::wants_json() ) {
			if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 409 ); }
			wp_send_json_success( $result );
		}
		self::finish_result( $result, 'Zakończono wgrywanie zmian. Sprawdź stan każdej strony.' );
	}

	public static function handle_rollback() {
		$batch_id = isset( $_POST['batch_id'] ) && is_string( $_POST['batch_id'] ) ? sanitize_text_field( wp_unslash( $_POST['batch_id'] ) ) : '';
		self::authorize( 'yoona_seo_rollback_' . $batch_id );
		if ( '' === $batch_id || strlen( $batch_id ) > 128 || ! preg_match( '/^[a-zA-Z0-9._-]+$/D', $batch_id ) ) {
			self::finish( 'error', 'Niepoprawny identyfikator partii. Odśwież panel i wybierz partię ponownie.' );
		}
		self::finish_result( Yoona_SEO_Engine::rollback( $batch_id ), 'Zakończono cofanie partii. Sprawdź wyniki.' );
	}

	public static function handle_diagnostics() {
		self::authorize( 'yoona_seo_diagnostics' );
		$data = Yoona_SEO_Engine::export_diagnostics();
		if ( is_wp_error( $data ) ) {
			self::finish( 'error', $data->get_error_message() );
		}
		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			self::finish( 'error', 'Nie udało się przygotować pliku diagnostycznego.' );
		}
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="yoona-seo-diagnostyka-' . gmdate( 'Y-m-d' ) . '.json"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download, never an HTML response.
		exit;
	}

	private static function authorize( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( 'Nie masz uprawnień do zmiany ustawień SEO.' ), '', array( 'response' => 403 ) );
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			wp_die( esc_html( 'Ta czynność wymaga wysłania formularza z panelu Yoona SEO.' ), '', array( 'response' => 405 ) );
		}
		check_admin_referer( $nonce_action );
	}

	private static function render_comparison_row( $label, $key, $current, $new, $sources = array() ) {
		$before = array_key_exists( $key, $current ) && is_scalar( $current[ $key ] ) ? (string) $current[ $key ] : '';
		$after  = array_key_exists( $key, $new ) && is_scalar( $new[ $key ] ) ? (string) $new[ $key ] : null;
		$source = isset( $sources[ $key ] ) && is_scalar( $sources[ $key ] ) ? (string) $sources[ $key ] : '';
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( '' === $before ? '(brak zapisanej wartości)' : $before );
		if ( '' !== $source ) {
			echo '<p class="description">' . esc_html( 'Źródło: ' . $source ) . '</p>';
		}
		echo '</td><td>' . esc_html( null === $after ? '(bez zmian)' : ( '' === $after ? '(pusta wartość)' : $after ) ) . '</td></tr>';
	}

	private static function bundle_hash( $bundle ) {
		return hash( 'sha256', (string) wp_json_encode( $bundle ) );
	}

	private static function notice_key() {
		return 'yoona_seo_notice_' . get_current_user_id();
	}

	/** Offer recovery from persisted records even when the final batch status was not saved. */
	private static function batch_can_rollback( $batch ) {
		if ( array_key_exists( 'can_rollback', $batch ) ) {
			return ! empty( $batch['can_rollback'] );
		}
		// Legacy indexes lack record flags. The engine still verifies whether restoring is safe.
		return isset( $batch['count'] ) && absint( $batch['count'] ) > 0 && ( ! isset( $batch['status'] ) || 'rolled_back' !== $batch['status'] );
	}

	private static function finish_result( $result, $fallback_message ) {
		if ( is_wp_error( $result ) ) {
			self::finish( 'error', $result->get_error_message() );
		}
		$status  = is_array( $result ) && isset( $result['status'] ) ? $result['status'] : 'failed';
		$type    = in_array( $status, array( 'success', 'rolled_back' ), true ) ? 'success' : ( 'failed' === $status ? 'error' : 'warning' );
		$message = is_array( $result ) && ! empty( $result['message'] ) && is_string( $result['message'] ) ? $result['message'] : $fallback_message;
		self::finish( $type, $message, $result );
	}

	private static function finish( $type, $message, $result = array() ) {
		if ( self::wants_json() ) { wp_send_json_error( array( 'message' => sanitize_text_field( $message ) ), 409 ); }
		$notice = array(
			'type'    => in_array( $type, array( 'success', 'warning', 'error' ), true ) ? $type : 'warning',
			'message' => sanitize_text_field( $message ),
			'pages'   => array(),
		);
		if ( is_array( $result ) && ! empty( $result['pages'] ) && is_array( $result['pages'] ) ) {
			foreach ( array_slice( $result['pages'], 0, 200 ) as $page ) {
				if ( is_array( $page ) ) {
					$notice['pages'][] = array(
						'path'    => isset( $page['path'] ) && is_scalar( $page['path'] ) ? sanitize_text_field( (string) $page['path'] ) : '',
						'message' => isset( $page['message'] ) && is_scalar( $page['message'] ) ? sanitize_text_field( (string) $page['message'] ) : self::status_label( isset( $page['status'] ) ? $page['status'] : '' ),
					);
				}
			}
		}
		set_transient( self::notice_key(), $notice, 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	private static function render_notice( $notice ) {
		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}
		$type = isset( $notice['type'] ) && in_array( $notice['type'], array( 'success', 'warning', 'error' ), true ) ? $notice['type'] : 'warning';
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p>';
		if ( ! empty( $notice['pages'] ) && is_array( $notice['pages'] ) ) {
			echo '<details><summary>Wyniki dla poszczególnych stron</summary><ul>';
			foreach ( $notice['pages'] as $page ) {
				if ( is_array( $page ) && isset( $page['path'], $page['message'] ) ) {
					echo '<li><code>' . esc_html( $page['path'] ) . '</code>: ' . esc_html( $page['message'] ) . '</li>';
				}
			}
			echo '</ul></details>';
		}
		echo '</div>';
	}

	private static function wants_json() {
		return isset( $_POST['yoona_json'], $_POST['action'] ) && '1' === $_POST['yoona_json'] && 'yoona_seo_apply' === $_POST['action'];
	}

	private static function batch_script() {
		?>
		<script>
		(function () {
			'use strict';
			const form = document.getElementById('yoona-seo-apply-form');
			if (!form || !window.fetch || !window.FormData) return;
			// The hidden input named "action" shadows HTMLFormElement.action.
			const endpoint = form.getAttribute('action');
			if (!endpoint) return;
			const box = document.getElementById('yoona-progress');
			const progress = box.querySelector('progress');
			const status = box.querySelector('[role="status"]');
			const results = box.querySelector('ul');
			const refresh = document.getElementById('yoona-refresh');
			let running = false;
			const choices = () => Array.from(form.querySelectorAll('input[name="selected_paths[]"]:not(:disabled)'));
			document.getElementById('yoona-select-all').addEventListener('click', () => { if (!running) choices().forEach(input => input.checked = true); });
			document.getElementById('yoona-select-none').addEventListener('click', () => { if (!running) choices().forEach(input => input.checked = false); });
			refresh.addEventListener('click', () => window.location.reload());
			window.addEventListener('beforeunload', event => { if (running) { event.preventDefault(); event.returnValue = ''; } });
			form.addEventListener('submit', async event => {
				event.preventDefault();
				if (running) return;
				const paths = choices().filter(input => input.checked).map(input => input.value);
				box.hidden = false;
				if (!paths.length) { status.textContent = 'Zaznacz co najmniej jedną stronę gotową do aktualizacji.'; return; }
				const template = new FormData(form);
				template.delete('selected_paths[]');
				running = true; results.textContent = ''; refresh.hidden = true;
				progress.max = paths.length; progress.value = 0;
				form.querySelectorAll('button,input[type="submit"]').forEach(button => button.disabled = true);
				let completed = 0;
				try {
					for (let offset = 0; offset < paths.length; offset += 10) {
						status.textContent = 'Zapisywanie: ' + completed + ' z ' + paths.length + ' stron. Pozostaw kartę otwartą.';
						const data = new FormData();
						template.forEach((value, key) => data.append(key, value));
						data.set('yoona_json', '1');
						paths.slice(offset, offset + 10).forEach(path => data.append('selected_paths[]', path));
						const response = await fetch(endpoint, {method: 'POST', body: data, credentials: 'same-origin'});
						if (response.redirected && /\/wp-login\.php(?:[?#]|$)/.test(response.url || '')) throw new Error('WordPress przekierował do logowania. Zaloguj się ponownie i odśwież panel. Sprawdź historię przed ponowieniem zapisu.');
						let payload;
						try { payload = await response.json(); } catch (error) {
							const code = response.status ? 'HTTP ' + response.status : 'brak kodu HTTP';
							let advice = 'Stan ostatniej partii nie jest potwierdzony. Sprawdź historię przed ponowieniem; wcześniejsze partie mają własne kopie.';
							if (response.status === 401 || response.status === 403) advice = 'WordPress lub ochrona serwera odrzuciły żądanie. Odśwież panel i sprawdź logowanie oraz historię przed ponowieniem.';
							if (response.status === 404) advice = 'Nie znaleziono adresu zapisu. Zaktualizuj wtyczkę i odśwież panel. Sprawdź historię przed ponowieniem.';
							throw new Error('Nie otrzymano poprawnego potwierdzenia zapisu (' + code + '). ' + advice);
						}
						if (!payload || typeof payload !== 'object' || typeof payload.success !== 'boolean') throw new Error('Serwer zwrócił nierozpoznane potwierdzenie zapisu. Sprawdź historię przed ponowieniem.');
						if (!response.ok || !payload.success) throw new Error((payload.data && payload.data.message) || 'Zapis został odrzucony. Odśwież panel i sprawdź komunikaty.');
						const result = payload.data;
						if (!result || typeof result !== 'object' || !Array.isArray(result.pages) || typeof result.status !== 'string') throw new Error('Odpowiedź nie zawiera wyniku dla stron. Sprawdź historię przed ponowieniem zapisu.');
						(result.pages || []).forEach(page => { const item = document.createElement('li'); item.textContent = page.path + ': ' + page.message; results.appendChild(item); });
						completed += (result.pages || []).filter(page => page.status === 'applied' || page.status === 'unchanged').length;
						progress.value = completed;
						if (result.status !== 'success' && !(result.pages && result.pages.length && result.pages.every(page => page.status === 'unchanged'))) throw new Error(result.message || 'Nie wszystkie zmiany tej partii zostały zapisane. Sprawdź wyniki.');
					}
					status.textContent = 'Zakończono. Zapisano ' + completed + ' z ' + paths.length + ' stron. Wyczyść pamięć podręczną strony i sprawdź wynik. Kopie znajdziesz w historii.';
				} catch (error) {
					status.textContent = 'Zatrzymano po ' + completed + ' z ' + paths.length + ' stron. ' + error.message;
				} finally {
					running = false;
					form.querySelectorAll('button,input[type="submit"]').forEach(button => button.disabled = false);
					refresh.hidden = false;
				}
			});
		})();
		</script>
		<?php
	}

	private static function status_label( $status ) {
		$labels = array(
			'prepared'         => 'Zapis nie został zakończony',
			'applying'         => 'Zapis został przerwany',
			'rolling_back'     => 'Cofanie zostało przerwane',
			'recovery_needed'  => 'Wymaga odzyskania',
			'success'          => 'Zapisano',
			'partial'          => 'Zapisano część zmian',
			'failed'           => 'Operacja nie powiodła się',
			'rolled_back'      => 'Cofnięto',
			'partial_rollback' => 'Cofnięto część zmian',
			'skipped'          => 'Pominięto',
			'conflict'         => 'Konflikt — wymaga sprawdzenia',
		);
		return is_string( $status ) && isset( $labels[ $status ] ) ? $labels[ $status ] : 'Sprawdź szczegóły';
	}
}
