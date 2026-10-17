<?php defined( 'ABSPATH' ) || exit; Yoona_SEO_Design::part( 'header' ); ?>
<main id="main-content" class="yn-main yn-secondary-main">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php
		$offer_hero = Yoona_SEO_Offer_Hero::context( get_the_ID() );
		$offer_applied = false;
		// Complete Divi callbacks before omitting only the reviewed legacy blocks.
		ob_start();
		try {
			$offer_applied = Yoona_SEO_Content_Cleanup::the_content( $offer_hero );
		} finally {
			$yn_rendered_content = ob_get_clean();
		}
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'yn-entry' ); ?>>
			<?php if ( $offer_applied ) : ?>
				<?php require __DIR__ . '/offer-hero.php'; ?>
			<?php else : ?>
			<header class="yn-page-heading"><div class="yn-container"><p class="yn-eyebrow"><?php echo 'post' === get_post_type() ? 'Nasze realizacje' : 'YOONA Creative Group'; ?></p>
				<?php if ( ! post_password_required() && preg_match( '/<h1(?:\s|>)/i', (string) get_post_field( 'post_content', get_the_ID() ) ) ) : ?>
					<p class="yn-page-title"><?php the_title(); ?></p>
				<?php else : ?>
					<h1 class="yn-page-title"><?php the_title(); ?></h1>
				<?php endif; ?>
				<a class="yn-text-link" href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Porozmawiajmy o Twoim projekcie <span aria-hidden="true">↗</span></a>
			</div></header>
			<?php endif; ?>
			<div <?php if ( $offer_applied ) : ?>id="yn-offer-content" tabindex="-1"<?php endif; ?> class="entry-content yn-content <?php echo function_exists( 'et_pb_is_pagebuilder_used' ) && et_pb_is_pagebuilder_used( get_the_ID() ) ? 'yn-builder-content' : 'yn-container'; ?>">
				<?php echo $yn_rendered_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard the_content output, rendered once above. ?>
				<?php wp_link_pages( array( 'before' => '<nav class="yn-page-links" aria-label="Części wpisu">Strony: ', 'after' => '</nav>' ) ); ?>
			</div>
		</article>
		<?php if ( comments_open() || get_comments_number() ) : ?><div class="yn-container yn-comments"><?php comments_template(); ?></div><?php endif; ?>
	<?php endwhile; ?>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
