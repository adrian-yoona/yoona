<?php defined( 'ABSPATH' ) || exit; Yoona_SEO_Design::part( 'header' ); ?>
<main id="main-content" class="yn-main yn-secondary-main">
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'yn-entry' ); ?>>
			<header class="yn-page-heading"><div class="yn-container"><p class="yn-eyebrow"><?php echo 'post' === get_post_type() ? 'Nasze realizacje' : 'YOONA Creative Group'; ?></p>
				<?php if ( ! post_password_required() && preg_match( '/<h1(?:\s|>)/i', (string) get_post_field( 'post_content', get_the_ID() ) ) ) : ?>
					<p class="yn-page-title"><?php the_title(); ?></p>
				<?php else : ?>
					<h1 class="yn-page-title"><?php the_title(); ?></h1>
				<?php endif; ?>
				<a class="yn-text-link" href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Porozmawiajmy o Twoim projekcie <span aria-hidden="true">↗</span></a>
			</div></header>
			<div class="entry-content yn-content <?php echo function_exists( 'et_pb_is_pagebuilder_used' ) && et_pb_is_pagebuilder_used( get_the_ID() ) ? 'yn-builder-content' : 'yn-container'; ?>">
				<?php the_content(); ?>
				<?php wp_link_pages( array( 'before' => '<nav class="yn-page-links" aria-label="Części wpisu">Strony: ', 'after' => '</nav>' ) ); ?>
			</div>
		</article>
		<?php if ( comments_open() || get_comments_number() ) : ?><div class="yn-container yn-comments"><?php comments_template(); ?></div><?php endif; ?>
	<?php endwhile; ?>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
