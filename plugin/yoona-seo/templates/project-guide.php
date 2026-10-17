<?php defined( 'ABSPATH' ) || exit; Yoona_SEO_Design::part( 'header' ); ?>
<main id="main-content" class="yn-main yn-secondary-main">
<?php while ( have_posts() ) : the_post(); $guide = Yoona_SEO_Project_Guides::context( get_the_ID() ); $media = Yoona_SEO_Service_Guides::legacy_media( get_the_ID(), array() ); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'yn-entry' ); ?>>
<header class="yn-page-heading"><div class="yn-container"><p class="yn-eyebrow">Nasze realizacje<?php if ( ! empty( $guide['location'] ) ) echo ' / ' . esc_html( $guide['location'] ); ?></p><h1 class="yn-page-title"><?php the_title(); ?></h1></div></header>
<?php Yoona_SEO_Project_Guides::render( $guide ); ?>
<?php if ( ! $media ) { $featured = Yoona_SEO_Project_Gallery::image( get_the_ID(), true ); if ( $featured ) $media[] = $featured; } ?>
<?php if ( $media ) : ?><section class="yn-section yn-guide-media" aria-label="Zdjęcia realizacji"><div class="yn-container yn-guide-media-grid"><?php foreach ( $media as $html ) echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Original rendered WordPress/Divi media. ?></div></section><?php endif; ?>
<section class="yn-section"><div class="yn-container yn-actions"><a class="yn-button" href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Zapytaj o podobną realizację <span aria-hidden="true">↗</span></a><a class="yn-text-link" href="<?php echo esc_url( Yoona_SEO_Design::route( 'projects' ) ); ?>">Wszystkie realizacje <span aria-hidden="true">↗</span></a></div></section>
</article>
<?php if ( comments_open() || get_comments_number() ) : ?><div class="yn-container yn-comments"><?php comments_template(); ?></div><?php endif; ?>
<?php endwhile; ?>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
