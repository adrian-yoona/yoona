<?php
/** Structured service copy inside the complete, preserved original Divi layout. */
defined( 'ABSPATH' ) || exit;
Yoona_SEO_Design::part( 'header' );
?>
<main id="main-content" class="yn-main yn-secondary-main yn-service-guide">
<?php while ( have_posts() ) : the_post();
    $guide = Yoona_SEO_Service_Guides::context( get_the_ID() );
    $yn_owned_service = (bool) get_post_meta( get_the_ID(), '_yoona_growth_owned', true );
    ob_start();
    try {
?>
<section class="yn-section yn-guide" aria-labelledby="yn-guide-title">
  <div class="yn-container">
    <?php if ( 'append' === $guide['layout_mode'] ) : ?>
    <div class="yn-guide-intro"><p class="yn-eyebrow">Materiały / wykonanie / wycena</p><h2 id="yn-guide-title">Szczegóły zamówienia</h2></div>
    <?php else : ?>
    <div class="yn-guide-intro"><p class="yn-eyebrow">Olsztyn / Ostróda / realizacje w Polsce</p><h2 id="yn-guide-title"><?php echo esc_html( $guide['title'] ); ?></h2><p><?php echo esc_html( $guide['overview'] ); ?></p></div>
    <?php endif; ?>
    <div class="yn-guide-grid">
    <?php foreach ( array( 'applications' => 'Zastosowanie', 'materials' => 'Materiały i warianty', 'process' => 'Jak realizujemy zamówienie', 'pricing' => 'Od czego zależy cena', 'quote' => 'Co przesłać do wyceny' ) as $key => $label ) : if ( empty( $guide[ $key ] ) ) continue; ?>
      <section class="yn-guide-panel"><h2><?php echo esc_html( $label ); ?></h2><ul><?php foreach ( $guide[ $key ] as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul></section>
    <?php endforeach; ?>
    </div>
    <?php if ( ! empty( $guide['faq'] ) ) : ?><section class="yn-guide-faq" aria-labelledby="yn-guide-faq-title"><h2 id="yn-guide-faq-title">Pytania przed zamówieniem</h2><?php foreach ( $guide['faq'] as $faq ) : ?><details><summary><?php echo esc_html( $faq['q'] ); ?></summary><p><?php echo esc_html( $faq['a'] ); ?></p></details><?php endforeach; ?></section><?php endif; ?>
    <?php $links = array_values( array_unique( array_merge( isset( $guide['children'] ) ? $guide['children'] : array(), isset( $guide['related'] ) ? $guide['related'] : array() ) ) ); if ( $links ) : ?><nav class="yn-guide-related" aria-label="Powiązane usługi"><h2>Sprawdź także</h2><div class="yn-actions"><?php foreach ( $links as $path ) : $link = Yoona_SEO_Service_Guides::link( $path ); $definitions = Yoona_SEO_Service_Guides::data(); if ( ! $link || ! isset( $definitions[ $path ] ) ) continue; ?><a class="yn-text-link" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $definitions[ $path ]['title'] ); ?> <span aria-hidden="true">↗</span></a><?php endforeach; ?></div></nav><?php endif; ?>
    <div class="yn-actions"><a class="yn-button" href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Prześlij projekt do wyceny <span aria-hidden="true">↗</span></a></div>
  </div>
</section>
<?php
    } finally { $yn_service_details = ob_get_clean(); }
    $yn_service_layout = Yoona_SEO_Service_Guides::legacy_content( get_the_ID(), $guide, $yn_service_details );
    if ( $yn_service_layout['hero_applied'] ) {
        $offer_hero = $guide;
        $offer_hero['has_body_h1'] = 1 === preg_match( '/<h1(?:\s|>)/i', $yn_service_layout['html'] );
        require __DIR__ . '/offer-hero.php';
    }
?>
<div id="yn-offer-content" tabindex="-1" class="<?php echo $yn_owned_service ? 'yn-service-copy' : 'entry-content yn-content yn-builder-content yn-service-original'; ?>">
<?php echo $yn_service_layout['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Complete standard WordPress/Divi output, rendered once. ?>
</div>
<?php endwhile; ?>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
