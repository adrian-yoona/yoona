<?php
defined( 'ABSPATH' ) || exit;
$yn_footer_data = Yoona_SEO_Design::data();
?>
	<section class="yn-section yn-contact" aria-labelledby="yn-contact-title">
		<div class="yn-container yn-contact-grid">
			<div><p class="yn-eyebrow">Masz pomysł?</p><h2 id="yn-contact-title">Zróbmy coś,<br><span>co widać.</span></h2><p>Opowiedz nam, czego potrzebuje Twoja marka. Doradzimy, przygotujemy wycenę i&nbsp;przeprowadzimy Cię przez realizację.</p></div>
			<div class="yn-contact-card">
				<div class="yn-location-grid">
				<?php foreach ( $yn_footer_data['contact']['locations'] as $yn_location ) : ?>
					<div class="yn-contact-location"><h3><?php echo esc_html( $yn_location['label'] ); ?></h3><a class="yn-contact-phone" href="<?php echo esc_url( $yn_location['telephone_href'] ); ?>"><?php echo esc_html( $yn_location['telephone_display'] ); ?></a><a class="yn-contact-email" href="<?php echo esc_url( 'mailto:' . $yn_location['email'] ); ?>"><?php echo esc_html( $yn_location['email'] ); ?></a></div>
				<?php endforeach; ?>
				</div>
				<div class="yn-contact-hours"><span>Godziny pracy</span><span>Pon.–pt. 7:30–15:30</span></div>
				<a class="yn-button" href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Zapytaj o wycenę <span aria-hidden="true">↗</span></a>
			</div>
		</div>
	</section>
	<footer class="yn-footer">
		<div class="yn-container">
			<div class="yn-footer-grid">
				<div><a class="yn-brand yn-footer-brand" href="<?php echo esc_url( Yoona_SEO_Design::route( 'home' ) ); ?>" aria-label="YOONA Creative Group — strona główna"><?php echo Yoona_SEO_Design::brand(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a><p class="yn-footer-tagline">Reklama, którą widać.<br>Olsztyn / Ostróda / cała Polska</p></div>
				<div class="yn-footer-services"><h3><a href="<?php echo esc_url( Yoona_SEO_Design::route( 'services' ) ); ?>">Pełna oferta</a></h3><ul><?php foreach ( $yn_footer_data['services'] as $yn_service ) : ?><li><a href="<?php echo esc_url( Yoona_SEO_Design::url( $yn_service['url'] ) ); ?>"><?php echo esc_html( $yn_service['name'] ); ?></a></li><?php endforeach; ?></ul></div>
				<div><h3>Firma</h3><ul><li><a href="<?php echo esc_url( Yoona_SEO_Design::route( 'about' ) ); ?>">O nas</a></li><li><a href="<?php echo esc_url( Yoona_SEO_Design::route( 'projects' ) ); ?>">Realizacje</a></li><li><a href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Kontakt</a></li></ul></div>
				<div><h3>Zobacz więcej</h3><ul><?php foreach ( $yn_footer_data['social'] as $yn_social_name => $yn_social_url ) : ?><li><a href="<?php echo esc_url( $yn_social_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $yn_social_name ); ?> <span aria-hidden="true">↗</span></a></li><?php endforeach; ?></ul></div>
			</div>
			<div class="yn-footer-bottom"><span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> YOONA Creative Group</span><a href="<?php echo esc_url( Yoona_SEO_Design::route( 'privacy' ) ); ?>">Polityka prywatności</a><a class="yn-top-link" href="#page-container">Do góry ↑</a></div>
		</div>
	</footer>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
