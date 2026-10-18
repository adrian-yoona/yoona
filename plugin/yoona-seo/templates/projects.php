<?php
defined( 'ABSPATH' ) || exit;
Yoona_SEO_Design::part( 'header' );
$yn_projects_title = get_option( 'page_for_posts' ) ? get_the_title( (int) get_option( 'page_for_posts' ) ) : 'Nasze realizacje';
$yn_gallery_position = 0;
?>
<main id="main-content" class="yn-main yn-project-gallery">
	<header class="yn-collection-heading">
		<div class="yn-collection-container">
			<p class="yn-eyebrow">Zrobione przez YOONA</p>
			<h1><?php echo esc_html( $yn_projects_title ); ?></h1>
		</div>
	</header>
	<div class="yn-collection-container yn-collection-content">
		<?php if ( have_posts() ) : ?>
			<div class="yn-project-gallery-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'yn-project-gallery-card' ); ?>>
						<a href="<?php the_permalink(); ?>">
							<div class="yn-project-gallery-image"><?php echo Yoona_SEO_Project_Gallery::image( get_the_ID(), 0 === $yn_gallery_position ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by helper. ?></div>
							<h2><?php the_title(); ?></h2>
						</a>
					</article>
					<?php ++$yn_gallery_position; endwhile; ?>
			</div>
			<?php the_posts_pagination( array( 'prev_text' => '← Poprzednie', 'next_text' => 'Następne →', 'screen_reader_text' => 'Nawigacja realizacji', 'mid_size' => 1 ) ); ?>
		<?php else : ?>
			<p class="yn-collection-empty">Realizacje pojawią się tutaj po opublikowaniu.</p>
		<?php endif; ?>
	</div>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
