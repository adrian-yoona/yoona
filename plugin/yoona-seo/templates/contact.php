<?php defined( 'ABSPATH' ) || exit; Yoona_SEO_Design::part( 'header' ); $locations = Yoona_SEO_Design::data()['contact']['locations']; ?>
<main id="main-content" class="yn-main yn-secondary-main">
<?php while ( have_posts() ) : the_post(); ?>
<header class="yn-page-heading"><div class="yn-container"><p class="yn-eyebrow">YOONA Creative Group</p><?php $heading_tag = preg_match( '/<h1(?:\s|>)/i', (string) get_post_field( 'post_content', get_the_ID() ) ) ? 'p' : 'h1'; echo '<' . $heading_tag . ' class="yn-page-title">Kontakt — Olsztyn i Ostróda</' . $heading_tag . '>'; ?><p>Wybierz dogodny kontakt. Prześlij projekt, wymiary lub zdjęcie miejsca realizacji — pomożemy ustalić zakres i przygotujemy wycenę.</p></div></header>
<section class="yn-section yn-guide" aria-label="Dwa kontakty YOONA"><div class="yn-container">
<div class="yn-location-grid yn-contact-page-locations">
<?php foreach ( $locations as $location ) : ?><section class="yn-contact-location" data-yn-location="<?php echo esc_attr( $location['label'] ); ?>"><h2><?php echo esc_html( $location['label'] ); ?></h2><a class="yn-contact-phone" href="<?php echo esc_url( $location['telephone_href'] ); ?>"><?php echo esc_html( $location['telephone_display'] ); ?></a><a class="yn-contact-email" href="<?php echo esc_url( 'mailto:' . $location['email'] ); ?>"><?php echo esc_html( $location['email'] ); ?></a></section><?php endforeach; ?>
</div><p>Poniedziałek–piątek, 7:30–15:30.</p>
</div></section>
<div class="entry-content yn-content yn-builder-content"><?php Yoona_SEO_Content_Cleanup::the_content(); ?></div>
<?php endwhile; ?>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
