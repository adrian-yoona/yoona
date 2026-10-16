<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="yn-skip-link" href="#main-content">Przejdź do treści</a>
<div id="page-container" class="yn-site">
	<header class="yn-header">
		<div class="yn-container yn-header-inner">
			<a class="yn-brand" href="<?php echo esc_url( Yoona_SEO_Design::route( 'home' ) ); ?>" aria-label="YOONA Creative Group — strona główna"><?php echo Yoona_SEO_Design::brand(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by helper. ?></a>
			<nav class="yn-desktop-nav" aria-label="Menu główne">
				<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'home' ) . '#oferta' ); ?>">Oferta</a>
				<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'about' ) ); ?>">O nas</a>
				<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'projects' ) ); ?>">Realizacje</a>
				<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'home' ) . '#proces' ); ?>">Proces</a>
				<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Kontakt</a>
			</nav>
			<a class="yn-button yn-header-cta" href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Zapytaj o wycenę <span aria-hidden="true">↗</span></a>
			<details class="yn-mobile-menu">
				<summary aria-controls="yn-mobile-navigation"><span>Menu</span><span class="yn-menu-lines" aria-hidden="true"></span></summary>
				<nav id="yn-mobile-navigation" aria-label="Menu mobilne">
					<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'home' ) . '#oferta' ); ?>">Oferta</a>
					<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'about' ) ); ?>">O nas</a>
					<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'projects' ) ); ?>">Realizacje</a>
					<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'home' ) . '#proces' ); ?>">Proces</a>
					<a href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Kontakt / wycena <span aria-hidden="true">↗</span></a>
				</nav>
			</details>
		</div>
	</header>
	<div id="et-main-area">
