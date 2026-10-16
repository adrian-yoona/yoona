<?php
/**
 * Real WordPress regression tests for surgical Divi text patches.
 * Run with: wp eval-file /path/to/test-divi.php
 * No production records or source content are modified or embedded here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this test with wp eval-file inside a local WordPress installation.\n" );
	exit( 1 );
}
if ( ! class_exists( 'Yoona_SEO_Divi' ) ) {
	require_once WP_PLUGIN_DIR . '/yoona-seo/includes/class-yoona-seo-divi.php';
}

$yoona_divi_assertions = 0;
$yoona_divi_check      = static function ( $condition, $label ) use ( &$yoona_divi_assertions ) {
	++$yoona_divi_assertions;
	if ( ! $condition ) {
		throw new RuntimeException( 'Divi assertion failed: ' . $label );
	}
};
$yoona_divi_error = static function ( $result, $label ) use ( $yoona_divi_check ) {
	$yoona_divi_check( is_wp_error( $result ), $label );
};
$yoona_divi_patch = static function ( $index, $old, $new, $type = null ) {
	$patch = array( 'module_index' => $index, 'expected_html_sha256' => hash( 'sha256', $old ), 'html' => $new );
	if ( null !== $type ) {
		$patch['module_type'] = $type;
	}
	return $patch;
};

$yoona_divi_old_one = '<h2>Stary nagłówek — Łódź</h2><p>Treść pierwsza.</p>';
$yoona_divi_old_two = '<p>Druga treść: <strong>żółć</strong>.</p>';
$yoona_divi_old_faq = '<p>Stara odpowiedź.</p>';
$yoona_divi_source  = '[et_pb_section fb_built="1" custom_padding="4px|8px|4px|8px"]'
	. '[et_pb_row admin_label="Zachowaj atrybuty"][et_pb_column type="4_4"]'
	. '[et_pb_text _builder_version="4.27.4" text_font="Open Sans||||||||"]' . $yoona_divi_old_one . '[/et_pb_text]'
	. '[et_pb_image src="https://example.invalid/car.jpg" title_text="Bez zmian" /]'
	. '[et_pb_gallery gallery_ids="12,34,56"][/et_pb_gallery]'
	. '[et_pb_contact_form email="public@example.invalid"][et_pb_contact_field field_id="Name"][/et_pb_contact_field][/et_pb_contact_form]'
	. '[et_pb_toggle title="Pytanie bez zmian" open="off"]' . $yoona_divi_old_faq . '[/et_pb_toggle]'
	. '[et_pb_code]one[et_pb_line_break_holder]two[et_pb_line_break_holder]three[/et_pb_code]'
	. '[et_pb_text admin_label="Drugi moduł"]' . $yoona_divi_old_two . '[/et_pb_text]'
	. '[/et_pb_column][/et_pb_row][/et_pb_section]';

$yoona_divi_modules = Yoona_SEO_Divi::extract_modules( $yoona_divi_source );
$yoona_divi_check( is_array( $yoona_divi_modules ) && 2 === count( $yoona_divi_modules ), 'enumerate text modules only' );
$yoona_divi_check( 0 === $yoona_divi_modules[0]['module_index'] && 1 === $yoona_divi_modules[1]['module_index'], 'zero-based indices' );
$yoona_divi_check( $yoona_divi_old_one === $yoona_divi_modules[0]['html'], 'extract exact UTF-8 body bytes' );
$yoona_divi_check( hash( 'sha256', $yoona_divi_old_two ) === $yoona_divi_modules[1]['html_sha256'], 'hash exact body bytes' );
$yoona_divi_check( array( 'module_type', 'module_index', 'html', 'html_sha256' ) === array_keys( $yoona_divi_modules[0] ), 'public extraction does not expose internal offsets' );
$yoona_divi_toggles = Yoona_SEO_Divi::extract_modules( $yoona_divi_source, 'et_pb_toggle' );
$yoona_divi_check( is_array( $yoona_divi_toggles ) && 1 === count( $yoona_divi_toggles ) && 0 === $yoona_divi_toggles[0]['module_index'], 'toggle indices are separate from text' );
$yoona_divi_check( $yoona_divi_old_faq === $yoona_divi_toggles[0]['html'], 'extract toggle body without title attribute' );

$yoona_divi_new_one = '<h1>Nowy nagłówek — Łódź</h1><p>Treść &amp; szczegóły.</p>';
$yoona_divi_new_two = '<p>Nowa druga treść.</p>';
$yoona_divi_new_faq = '<p>Nowa odpowiedź bez obietnic bez pokrycia.</p>';
$yoona_divi_patches = array(
	$yoona_divi_patch( 1, $yoona_divi_old_two, $yoona_divi_new_two ),
	$yoona_divi_patch( 0, $yoona_divi_old_faq, $yoona_divi_new_faq, 'et_pb_toggle' ),
	$yoona_divi_patch( 0, $yoona_divi_old_one, $yoona_divi_new_one ),
);
$yoona_divi_expected = str_replace( array( $yoona_divi_old_one, $yoona_divi_old_two, $yoona_divi_old_faq ), array( $yoona_divi_new_one, $yoona_divi_new_two, $yoona_divi_new_faq ), $yoona_divi_source );
$yoona_divi_actual = Yoona_SEO_Divi::prepare( $yoona_divi_source, $yoona_divi_patches );
$yoona_divi_check( $yoona_divi_expected === $yoona_divi_actual, 'multiple out-of-order patches preserve every other source byte, shortcode attribute, image, gallery, code and form' );
$yoona_divi_check( $yoona_divi_source === Yoona_SEO_Divi::prepare( $yoona_divi_source, array() ), 'empty patch list is byte-exact no-op' );
$yoona_divi_normalized = Yoona_SEO_Divi::validate_patches( array( $yoona_divi_patch( 0, $yoona_divi_old_one, $yoona_divi_new_one ) ) );
$yoona_divi_check( 'et_pb_text' === $yoona_divi_normalized[0]['module_type'], 'normalize default module type before resolving post' );
$yoona_divi_caps_hash = $yoona_divi_patch( 0, $yoona_divi_old_one, $yoona_divi_new_one );
$yoona_divi_caps_hash['expected_html_sha256'] = strtoupper( $yoona_divi_caps_hash['expected_html_sha256'] );
$yoona_divi_normalized = Yoona_SEO_Divi::validate_patches( array( $yoona_divi_caps_hash ) );
$yoona_divi_check( strtolower( $yoona_divi_caps_hash['expected_html_sha256'] ) === $yoona_divi_normalized[0]['expected_html_sha256'], 'normalize uppercase hexadecimal baseline' );

$yoona_divi_duplicate = $yoona_divi_patches[2];
$yoona_divi_duplicate['module_type'] = 'et_pb_text';
$yoona_divi_error( Yoona_SEO_Divi::prepare( $yoona_divi_source, array( $yoona_divi_patches[2], $yoona_divi_duplicate ) ), 'reject duplicate default and explicit module index' );
foreach ( array( -1, '0', 0.0, 99 ) as $yoona_divi_bad_index ) {
	$yoona_divi_bad = $yoona_divi_patches[2];
	$yoona_divi_bad['module_index'] = $yoona_divi_bad_index;
	$yoona_divi_error( Yoona_SEO_Divi::prepare( $yoona_divi_source, array( $yoona_divi_bad ) ), 'reject invalid or absent module index' );
}
$yoona_divi_bad = $yoona_divi_patches[2];
$yoona_divi_bad['expected_html_sha256'] = str_repeat( '0', 64 );
$yoona_divi_error( Yoona_SEO_Divi::prepare( $yoona_divi_source, array( $yoona_divi_bad ) ), 'reject changed baseline hash' );
$yoona_divi_bad['expected_html_sha256'] = 'invalid';
$yoona_divi_error( Yoona_SEO_Divi::validate_patches( array( $yoona_divi_bad ) ), 'reject malformed hash during import' );
foreach ( array( 'et_pb_code', null, array() ) as $yoona_divi_bad_type ) {
	$yoona_divi_bad = $yoona_divi_patches[2];
	$yoona_divi_bad['module_type'] = $yoona_divi_bad_type;
	$yoona_divi_error( Yoona_SEO_Divi::validate_patches( array( $yoona_divi_bad ) ), 'reject unsupported module type' );
}
$yoona_divi_bad = $yoona_divi_patches[2];
$yoona_divi_bad['attributes'] = array( 'title' => 'Do not change attributes' );
$yoona_divi_error( Yoona_SEO_Divi::validate_patches( array( $yoona_divi_bad ) ), 'reject attribute mutation fields' );
$yoona_divi_error( Yoona_SEO_Divi::validate_patches( 'not an array' ), 'reject scalar patches' );

foreach ( array( '<script>alert(1)</script>', '<p onclick="alert(1)">Unsafe</p>', '<iframe src="https://example.invalid/"></iframe>', '<p>[gallery ids="1,2"]</p>', '<p>[some_unknown_module]</p>', '[et_pb_text]Nested[/et_pb_text]', '[/et_pb_section]', '<p>[incomplete</p>' ) as $yoona_divi_bad_html ) {
	$yoona_divi_error( Yoona_SEO_Divi::prepare( $yoona_divi_source, array( $yoona_divi_patch( 0, $yoona_divi_old_one, $yoona_divi_bad_html ) ) ), 'reject unsafe HTML or introduced shortcode' );
}
$yoona_divi_embedded = '[et_pb_text]<p>Intro.</p>[gallery ids="1,2"][/et_pb_text]';
$yoona_divi_error( Yoona_SEO_Divi::prepare( $yoona_divi_embedded, array( $yoona_divi_patch( 0, '<p>Intro.</p>[gallery ids="1,2"]', '<p>New intro.</p>' ) ) ), 'refuse to remove an embedded gallery from a targeted body' );

foreach ( array(
	'[et_pb_text]Outer[et_pb_text]Inner[/et_pb_text][/et_pb_text]',
	'[et_pb_toggle]Outer[et_pb_toggle]Inner[/et_pb_toggle][/et_pb_toggle]',
	'[et_pb_toggle][et_pb_text]Nested types[/et_pb_text][/et_pb_toggle]',
	'[[et_pb_text]]Escaped[[/et_pb_text]]',
	'[et_pb_text /]',
	'[et_pb_text]Unclosed',
	'[et_pb_section][et_pb_text]Text[/et_pb_text]',
	'[et_pb_section][et_pb_row][/et_pb_section][/et_pb_row]',
	'[et_pb_text title="unbalanced]Bad attribute[/et_pb_text]',
) as $yoona_divi_malformed ) {
	$yoona_divi_error( Yoona_SEO_Divi::extract_modules( $yoona_divi_malformed ), 'reject ambiguous, nested, escaped or malformed source structure' );
}
$yoona_divi_check( array() === Yoona_SEO_Divi::extract_modules( '<p>Classic editor content.</p>' ), 'no fictitious modules in classic HTML' );
$yoona_divi_error( Yoona_SEO_Divi::extract_modules( $yoona_divi_source, 'et_pb_code' ), 'cannot extract editable code modules' );
$yoona_divi_empty = '[et_pb_text admin_label="Empty"][/et_pb_text]';
$yoona_divi_check( '[et_pb_text admin_label="Empty"]<p>Added.</p>[/et_pb_text]' === Yoona_SEO_Divi::prepare( $yoona_divi_empty, array( $yoona_divi_patch( 0, '', '<p>Added.</p>' ) ) ), 'patch an empty inner body without touching its attributes' );
$yoona_divi_same = '[et_pb_text]<p>Same</p>[/et_pb_text][et_pb_text]<p>Same</p>[/et_pb_text]';
$yoona_divi_check( '[et_pb_text]<p>Same</p>[/et_pb_text][et_pb_text]<p>Only second</p>[/et_pb_text]' === Yoona_SEO_Divi::prepare( $yoona_divi_same, array( $yoona_divi_patch( 1, '<p>Same</p>', '<p>Only second</p>' ) ) ), 'offset replacement does not change another module with identical text' );

// Legacy pasted UI markup contains CSS arbitrary values, not executable
// shortcodes. Only the six observed expressions in quoted class attributes
// are exceptions, and this exception never applies to newly supplied HTML.
$yoona_divi_css_body = '<div class="lg:max-w-[40rem] xl:max-w-[48rem] min-h-[20px] lg:w-[calc(100%-115px)] md:w-[calc(100%-.5rem)] [.text-message+&amp;]:mt-5"><p>Old pasted UI.</p></div>';
$yoona_divi_css_source = '[et_pb_text admin_label="Preserve"]' . $yoona_divi_css_body . '[/et_pb_text]';
$yoona_divi_check( '[et_pb_text admin_label="Preserve"]<p>Clean copy.</p>[/et_pb_text]' === Yoona_SEO_Divi::prepare( $yoona_divi_css_source, array( $yoona_divi_patch( 0, $yoona_divi_css_body, '<p>Clean copy.</p>' ) ) ), 'allow removal of the observed CSS brackets in double-quoted class only' );
$yoona_divi_css_single = "<div CLASS = 'lg:max-w-[40rem]'><p>Old.</p></div>";
$yoona_divi_check( '[et_pb_text]<p>Clean.</p>[/et_pb_text]' === Yoona_SEO_Divi::prepare( '[et_pb_text]' . $yoona_divi_css_single . '[/et_pb_text]', array( $yoona_divi_patch( 0, $yoona_divi_css_single, '<p>Clean.</p>' ) ) ), 'allow exact CSS expressions in single-quoted HTML class' );
foreach ( array(
	'<div class="lg:max-w-[40rem] [gallery]"><p>Old.</p></div>',
	'<div class="[gallery]"><p>Old.</p></div>',
	'<div title="[gallery]" class="lg:max-w-[40rem]"><p>Old.</p></div>',
	'<div title="[40rem]"><p>Old.</p></div>',
	'<div data-class="lg:max-w-[40rem]"><p>Old.</p></div>',
	"<div title='class=\"[40rem]\"'><p>Old.</p></div>",
	'<div class=lg:max-w-[40rem]><p>Old.</p></div>',
	'<div class="lg:max-w-[41rem]"><p>Old.</p></div>',
	'<p>[40rem]</p>',
	'<p>class="[40rem]"</p>',
	'<div class="lg:max-w-[40rem]"></div>[gallery ids="1,2"]',
) as $yoona_divi_bracket_body ) {
	$yoona_divi_error( Yoona_SEO_Divi::prepare( '[et_pb_text]' . $yoona_divi_bracket_body . '[/et_pb_text]', array( $yoona_divi_patch( 0, $yoona_divi_bracket_body, '<p>Clean.</p>' ) ) ), 'CSS exception must not hide actual shortcodes, non-class attributes, body text, unknown values or unquoted classes' );
}
$yoona_divi_error( Yoona_SEO_Divi::prepare( '[et_pb_text]<p>Old.</p>[/et_pb_text]', array( $yoona_divi_patch( 0, '<p>Old.</p>', '<div class="lg:max-w-[40rem]">New CSS brackets.</div>' ) ) ), 'new HTML keeps strict bracket-shortcode guard even for observed CSS expressions' );

echo 'Divi helper: ' . $yoona_divi_assertions . " assertions passed.\n";
