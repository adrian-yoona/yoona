<?php
defined( 'ABSPATH' ) || exit;
Yoona_SEO_Design::part( 'header' );
$yn_is_contact = is_page( 'kontakt' );
?>
<main id="main-content" class="yn-main yn-secondary-main yn-company-page <?php echo $yn_is_contact ? 'yn-company-contact' : 'yn-company-about'; ?>">
<?php while ( have_posts() ) : the_post(); ?>
	<?php
	$yn_company_context = Yoona_SEO_Company_Pages::context( get_the_ID() );
	$yn_company_result = Yoona_SEO_Company_Pages::render( $yn_company_context );
	$yn_has_title = (bool) preg_match( '/<h1(?:\s|>)/i', $yn_company_result['html'] );
	$yn_heading_tag = $yn_has_title ? 'p' : 'h1';
	?>
	<?php if ( $yn_company_result['hero_omitted'] || ! preg_match( '/<h[12](?:\s|>)/i', $yn_company_result['html'] ) ) : ?>
	<header class="yn-company-heading">
		<div class="yn-container yn-company-heading-grid">
			<div class="yn-company-heading-copy">
				<nav class="yn-company-breadcrumbs" aria-label="Ścieżka strony"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona główna</a><span aria-hidden="true">/</span><span><?php echo $yn_is_contact ? 'Kontakt' : 'O nas'; ?></span></nav>
				<p class="yn-eyebrow"><?php echo $yn_is_contact ? 'Olsztyn / Ostróda' : 'YOONA Creative Group / O nas'; ?></p>
				<?php if ( $yn_is_contact ) : ?>
					<?php echo '<' . $yn_heading_tag . ' class="yn-company-title">Kontakt.<br><span>Jesteśmy blisko.</span></' . $yn_heading_tag . '>'; ?>
				<?php else : ?>
					<?php echo '<' . $yn_heading_tag . ' class="yn-company-title">Od projektu<br>do <span>montażu.</span></' . $yn_heading_tag . '>'; ?>
				<?php endif; ?>
			</div>
			<div class="yn-company-heading-lead">
				<?php if ( $yn_is_contact ) : ?>
					<p>Napisz lub zadzwoń. Prześlij projekt, wymiary albo zdjęcie miejsca.</p>
					<p class="yn-company-hours"><span>Jesteśmy dostępni</span><strong>Poniedziałek–piątek, 7:30–15:30</strong></p>
				<?php else : ?>
					<p>Projekt, produkcja i montaż reklamy. Olsztyn, Ostróda i cała Polska.</p>
					<div class="yn-actions"><a class="yn-button" href="<?php echo esc_url( Yoona_SEO_Design::route( 'projects' ) ); ?>">Realizacje <span aria-hidden="true">↗</span></a><a class="yn-text-link" href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Kontakt <span aria-hidden="true">↗</span></a></div>
				<?php endif; ?>
			</div>
		</div>
	</header>
	<?php endif; ?>
	<div class="entry-content yn-content yn-builder-content yn-company-content">
		<?php echo $yn_company_result['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard WordPress content, rendered once. ?>
	</div>
<?php endwhile; ?>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
