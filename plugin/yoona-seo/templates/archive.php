<?php
defined( 'ABSPATH' ) || exit;
Yoona_SEO_Design::part( 'header' );
$yn_archive_title = 'Nasze realizacje';
if ( is_404() ) {
	$yn_archive_title = 'Tu jeszcze nic nie widać.';
} elseif ( is_search() ) {
	$yn_archive_title = 'Wyniki wyszukiwania: ' . get_search_query();
} elseif ( is_archive() ) {
	$yn_archive_title = wp_strip_all_tags( get_the_archive_title() );
} elseif ( is_home() && get_option( 'page_for_posts' ) ) {
	$yn_archive_title = get_the_title( (int) get_option( 'page_for_posts' ) );
}
?>
<main id="main-content" class="yn-main yn-archive-main">
	<header class="yn-page-heading"><div class="yn-container"><p class="yn-eyebrow">YOONA / inspiracje / realizacje</p><h1 class="yn-page-title"><?php echo esc_html( $yn_archive_title ); ?></h1><?php if ( is_archive() ) : ?><div class="yn-archive-description"><?php the_archive_description(); ?></div><?php endif; ?></div></header>
	<div class="yn-container yn-archive-content">
		<?php if ( is_404() ) : ?>
			<div class="yn-empty-state"><p>Nie znaleźliśmy tej strony. Zajrzyj do naszej oferty lub opowiedz nam, czego szukasz.</p><div class="yn-actions"><a class="yn-button" href="<?php echo esc_url( Yoona_SEO_Design::route( 'services' ) ); ?>">Zobacz ofertę <span aria-hidden="true">↗</span></a><a class="yn-text-link" href="<?php echo esc_url( Yoona_SEO_Design::route( 'home' ) ); ?>">Wróć na stronę główną</a></div></div>
		<?php elseif ( have_posts() ) : ?>
			<div class="yn-archive-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'yn-archive-card' ); ?>>
						<a class="yn-archive-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); else : ?><span class="yn-image-placeholder" aria-hidden="true">YOONA<span>CREATIVE GROUP</span></span><?php endif; ?></a>
						<div class="yn-archive-card-copy"><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?><span aria-hidden="true">↗</span></a></h2><div class="yn-archive-excerpt"><?php the_excerpt(); ?></div></div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination( array( 'prev_text' => '← Poprzednie', 'next_text' => 'Następne →', 'screen_reader_text' => 'Nawigacja realizacji' ) ); ?>
		<?php else : ?>
			<div class="yn-empty-state"><p>Nie znaleźliśmy pasujących treści. Sprawdź naszą ofertę lub spróbuj wyszukać inną frazę.</p><form class="yn-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>"><label for="yn-search">Czego szukasz?</label><div><input id="yn-search" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" required><button class="yn-button" type="submit">Szukaj <span aria-hidden="true">↗</span></button></div></form></div>
		<?php endif; ?>
	</div>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
