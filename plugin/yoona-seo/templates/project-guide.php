<?php defined( 'ABSPATH' ) || exit; Yoona_SEO_Design::part( 'header' ); ?>
<main id="main-content" class="yn-main yn-secondary-main">
<?php while ( have_posts() ) : the_post();
    $guide = Yoona_SEO_Project_Guides::context( get_the_ID() );
    $original = Yoona_SEO_Project_Guides::original( get_the_ID() );
    // A genuinely image-free entry can use its existing featured photograph.
    // Never repeat a featured image when the original source already has photos.
    $featured = empty( $original['photo_count'] ) ? Yoona_SEO_Project_Gallery::image( get_the_ID(), true ) : '';
    $photo_count = (int) $original['photo_count'] + ( $featured ? 1 : 0 );
    $photo_class = 1 === $photo_count ? 'yn-project-one-photo' : ( $photo_count > 1 ? 'yn-project-many-photos' : 'yn-project-no-photos' );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'yn-entry yn-project-detail ' . $photo_class ); ?>>
<header class="yn-project-heading"><div class="yn-container">
    <nav class="yn-project-breadcrumb" aria-label="Ścieżka nawigacji"><a href="<?php echo esc_url( Yoona_SEO_Design::route( 'projects' ) ); ?>">Realizacje</a><span aria-hidden="true">/</span><span>Projekt YOONA</span></nav>
    <p class="yn-eyebrow">Nasze realizacje<?php if ( ! empty( $guide['location'] ) ) echo ' / ' . esc_html( $guide['location'] ); ?></p>
    <?php if ( $original['has_h1'] ) : ?><p class="yn-project-title"><?php the_title(); ?></p><?php else : ?><h1 class="yn-project-title"><?php the_title(); ?></h1><?php endif; ?>
</div></header>
<?php Yoona_SEO_Project_Guides::render( $guide ); ?>
<div class="yn-project-source"><div class="yn-container">
    <div class="entry-content yn-content yn-project-detail-gallery <?php echo function_exists( 'et_pb_is_pagebuilder_used' ) && et_pb_is_pagebuilder_used( get_the_ID() ) ? 'yn-builder-content' : 'yn-project-native-content'; ?>" data-yn-project-gallery>
        <?php if ( $featured ) : ?><figure class="yn-project-featured"><?php echo $featured; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Existing WordPress image helper. ?></figure><?php endif; ?>
        <?php echo $original['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Complete standard WordPress/Divi output, rendered once. ?>
        <?php wp_link_pages( array( 'before' => '<nav class="yn-page-links" aria-label="Części wpisu">Strony: ', 'after' => '</nav>' ) ); ?>
    </div>
</div></div>
<?php Yoona_SEO_Project_Guides::render_planning( $guide ); ?>
<section class="yn-project-next" aria-label="Kolejne kroki"><div class="yn-container yn-project-next-inner"><div class="yn-actions"><a class="yn-button" href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Zapytaj o podobną realizację <span aria-hidden="true">↗</span></a><a class="yn-text-link" href="<?php echo esc_url( Yoona_SEO_Design::route( 'projects' ) ); ?>">Wszystkie realizacje <span aria-hidden="true">↗</span></a></div></div></section>
</article>
<?php if ( comments_open() || get_comments_number() ) : ?><div class="yn-container yn-comments"><?php comments_template(); ?></div><?php endif; ?>
<?php endwhile; ?>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
