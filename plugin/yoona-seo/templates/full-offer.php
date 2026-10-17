<?php
defined( 'ABSPATH' ) || exit;
Yoona_SEO_Design::part( 'header' );
?>
<main id="main-content" class="yn-main yn-offer-catalog">
	<?php while ( have_posts() ) : the_post(); ?>
		<header class="yn-collection-heading">
			<div class="yn-collection-container">
				<p class="yn-eyebrow">Od pomysłu do realizacji</p>
				<h1><?php the_title(); ?></h1>
			</div>
		</header>
		<div class="yn-collection-container yn-collection-content">
			<div class="yn-catalog-grid">
				<?php foreach ( Yoona_SEO_Design::offer_cards() as $yn_card_index => $yn_card ) : ?>
					<article class="yn-catalog-card" data-page-id="<?php echo esc_attr( $yn_card['id'] ); ?>">
						<a href="<?php echo esc_url( $yn_card['url'] ); ?>">
							<div class="yn-catalog-image"><?php echo Yoona_SEO_Design::image( $yn_card, '', $yn_card_index < 4 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by helper. ?></div>
							<h2><?php echo esc_html( $yn_card['name'] ); ?><span aria-hidden="true">↗</span></h2>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endwhile; ?>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
