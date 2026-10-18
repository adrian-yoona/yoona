<?php
/** Native, stored Divi modules: no generated title, guide or forced layout. */
defined( 'ABSPATH' ) || exit;
Yoona_SEO_Design::part( 'header' );
?>
<main id="main-content" class="yn-native-divi">
	<div class="entry-content yn-native-content">
	<?php if ( is_home() ) : ?>
		<?php
		$yn_native_previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		$yn_native_index = get_post( (int) get_option( 'page_for_posts' ) );
		if ( $yn_native_index && 'publish' === $yn_native_index->post_status && '' === $yn_native_index->post_password ) {
			$GLOBALS['post'] = $yn_native_index;
			setup_postdata( $yn_native_index );
			try { the_content(); }
			finally { $GLOBALS['post'] = $yn_native_previous_post; if ( $yn_native_previous_post ) setup_postdata( $yn_native_previous_post ); }
		}
		?>
	<?php else : ?>
		<?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
	<?php endif; ?>
	</div>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
