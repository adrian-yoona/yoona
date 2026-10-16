<?php
defined( 'ABSPATH' ) || exit;
$offer_title = wp_strip_all_tags( get_the_title() );
$offer_title_length = function_exists( 'mb_strlen' ) ? mb_strlen( $offer_title ) : strlen( $offer_title );
$offer_title_class = 'yn-offer-title' . ( $offer_title_length > 28 ? ' yn-offer-title-long' : '' );
foreach ( preg_split( '/[\s,–—-]+/u', $offer_title ) as $offer_word ) {
	$offer_word_length = function_exists( 'mb_strlen' ) ? mb_strlen( $offer_word ) : strlen( $offer_word );
	if ( $offer_word_length >= 14 ) {
		$offer_title_class .= ' yn-offer-title-wide';
		break;
	}
}
?>
<header class="yn-offer-hero" aria-labelledby="yn-offer-title">
	<div class="yn-container yn-offer-layout">
		<div class="yn-offer-copy">
			<nav class="yn-offer-breadcrumbs" aria-label="Ścieżka nawigacji">
				<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'home' ) ); ?>">Strona główna</a>
				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'services' ) ); ?>">Oferta</a>
			</nav>
			<p class="yn-offer-eyebrow">Oferta YOONA</p>
			<?php if ( $offer_hero['has_body_h1'] ) : ?>
				<p id="yn-offer-title" class="<?php echo esc_attr( $offer_title_class ); ?>"><?php the_title(); ?></p>
			<?php else : ?>
				<h1 id="yn-offer-title" class="<?php echo esc_attr( $offer_title_class ); ?>"><?php the_title(); ?></h1>
			<?php endif; ?>
			<p class="yn-offer-lead"><?php echo esc_html( $offer_hero['lead'] ); ?></p>
			<div class="yn-offer-actions">
				<a class="yn-button" href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Zapytaj o wycenę <span aria-hidden="true">↗</span></a>
				<a class="yn-offer-more" href="#yn-offer-content">Poznaj zakres <span aria-hidden="true"></span></a>
			</div>
			<p class="yn-offer-signature">Od pomysłu do realizacji<span aria-hidden="true"></span>YOONA Creative Group</p>
		</div>
	</div>
	<figure class="yn-offer-visual">
		<?php echo Yoona_SEO_Design::image( $offer_hero, 'yn-offer-image', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the image helper. ?>
		<figcaption><span aria-hidden="true"></span>YOONA / OFERTA</figcaption>
	</figure>
</header>
