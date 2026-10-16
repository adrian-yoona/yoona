<?php
/**
 * Surgical, hash-guarded updates of Divi text module bodies.
 *
 * This helper never renders shortcodes, updates a post or clears caches. Its
 * caller must enforce permissions and the hash of the complete post_content.
 *
 * @package Yoona_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Yoona_SEO_Divi {
	const MAX_SOURCE_BYTES = 5242880;
	const MAX_PATCHES      = 1000;

	/**
	 * Return zero-based indices within a module type, never across types.
	 *
	 * @param string $content     Original post_content, without unslashing.
	 * @param string $module_type et_pb_text (default) or et_pb_toggle.
	 * @return array|WP_Error Items have module_type, module_index, html, html_sha256.
	 */
	public static function extract_modules( $content, $module_type = 'et_pb_text' ) {
		if ( ! self::supported_type( $module_type ) ) {
			return new WP_Error( 'yoona_divi_type', 'Obsługiwane są wyłącznie moduły et_pb_text i et_pb_toggle.' );
		}
		$modules = self::inspect( $content );
		if ( is_wp_error( $modules ) ) {
			return $modules;
		}
		$result = array();
		foreach ( $modules[ $module_type ] as $module ) {
			unset( $module['_offset'], $module['_length'] );
			$result[] = $module;
		}
		return $result;
	}

	/** Validate an import before the corresponding post is available. */
	public static function validate_patches( $patches ) {
		if ( ! is_array( $patches ) || count( $patches ) > self::MAX_PATCHES ) {
			return new WP_Error( 'yoona_divi_patches', 'divi_text musi być tablicą zawierającą najwyżej 1000 zmian modułów.' );
		}
		$result = array();
		$seen   = array();
		foreach ( $patches as $patch ) {
			if ( ! is_array( $patch ) || array_diff( array_keys( $patch ), array( 'module_type', 'module_index', 'expected_html_sha256', 'html' ) ) ) {
				return new WP_Error( 'yoona_divi_patch', 'Zmiana modułu zawiera niepoprawne lub nieobsługiwane pola.' );
			}
			$type = array_key_exists( 'module_type', $patch ) ? $patch['module_type'] : 'et_pb_text';
			if ( ! self::supported_type( $type ) ) {
				return new WP_Error( 'yoona_divi_type', 'Zmiana dotyczy nieobsługiwanego typu modułu Divi.' );
			}
			if ( ! isset( $patch['module_index'] ) || ! is_int( $patch['module_index'] ) || $patch['module_index'] < 0 ) {
				return new WP_Error( 'yoona_divi_index', 'module_index musi być nieujemną liczbą całkowitą.' );
			}
			$key = $type . ':' . $patch['module_index'];
			if ( isset( $seen[ $key ] ) ) {
				return new WP_Error( 'yoona_divi_duplicate', 'Ten sam moduł występuje w zmianach więcej niż raz: ' . $key );
			}
			$seen[ $key ] = true;
			if ( ! isset( $patch['expected_html_sha256'] ) || ! is_string( $patch['expected_html_sha256'] ) || ! preg_match( '/\A[a-f0-9]{64}\z/i', $patch['expected_html_sha256'] ) ) {
				return new WP_Error( 'yoona_divi_hash', 'Brakuje poprawnego SHA-256 źródłowego HTML modułu: ' . $key );
			}
			if ( ! array_key_exists( 'html', $patch ) || ! is_string( $patch['html'] ) || strlen( $patch['html'] ) > 2 * MB_IN_BYTES ) {
				return new WP_Error( 'yoona_divi_html', 'Nowy HTML modułu musi być tekstem do 2 MB: ' . $key );
			}
			if ( wp_kses_post( $patch['html'] ) !== $patch['html'] ) {
				return new WP_Error( 'yoona_divi_unsafe_html', 'HTML modułu zawiera kod niedopuszczony przez WordPress: ' . $key );
			}
			if ( self::has_shortcode_marker( $patch['html'] ) ) {
				return new WP_Error( 'yoona_divi_inner_shortcode', 'Nowy HTML modułu nie może wprowadzać shortcode: ' . $key );
			}
			$result[] = array(
				'module_type' => $type,
				'module_index' => $patch['module_index'],
				'expected_html_sha256' => strtolower( $patch['expected_html_sha256'] ),
				'html' => $patch['html'],
			);
		}
		return $result;
	}

	/**
	 * Replace only inner HTML, preserving every other source byte.
	 *
	 * A patch has module_index (integer), expected_html_sha256 and html. Optional
	 * module_type defaults to et_pb_text. A targeted body containing a shortcode
	 * is intentionally unsupported, to preserve embedded galleries/forms/modules.
	 *
	 * @param string $post_content Original raw post_content.
	 * @param array  $patches      Reviewed patches with per-module baseline hashes.
	 * @return string|WP_Error
	 */
	public static function prepare( $post_content, $patches ) {
		$patches = self::validate_patches( $patches );
		if ( is_wp_error( $patches ) ) {
			return $patches;
		}
		$modules = self::inspect( $post_content );
		if ( is_wp_error( $modules ) ) {
			return $modules;
		}
		$replacements = array();
		foreach ( $patches as $patch ) {
			$type = $patch['module_type'];
			if ( ! isset( $modules[ $type ][ $patch['module_index'] ] ) ) {
				return new WP_Error( 'yoona_divi_index', 'Nie znaleziono modułu pod wskazanym indeksem w obrębie typu ' . $type . '.' );
			}
			$key = $type . ':' . $patch['module_index'];
			$module       = $modules[ $type ][ $patch['module_index'] ];
			if ( ! hash_equals( $module['html_sha256'], $patch['expected_html_sha256'] ) ) {
				return new WP_Error( 'yoona_divi_hash', 'Treść modułu zmieniła się od audytu albo brakuje poprawnego SHA-256: ' . $key );
			}
			if ( self::source_has_shortcode_marker( $module['html'] ) ) {
				return new WP_Error( 'yoona_divi_inner_shortcode', 'Wnętrze zmienianego modułu nie może zawierać shortcode. Osadzona galeria, formularz lub inny moduł musi pozostać nietknięty: ' . $key );
			}
			$replacements[] = array( 'offset' => $module['_offset'], 'length' => $module['_length'], 'html' => $patch['html'] );
		}
		// Every offset refers to the original bytes, so update the highest first.
		usort(
			$replacements,
			static function ( $a, $b ) {
				return $b['offset'] <=> $a['offset'];
			}
		);
		$result = $post_content;
		foreach ( $replacements as $replacement ) {
			$result = substr_replace( $result, $replacement['html'], $replacement['offset'], $replacement['length'] );
		}
		return $result;
	}

	private static function supported_type( $type ) {
		return is_string( $type ) && in_array( $type, array( 'et_pb_text', 'et_pb_toggle' ), true );
	}

	/** Also reject incomplete markers which could consume an adjacent tag. */
	private static function has_shortcode_marker( $html ) {
		return 1 === preg_match( '/\[(?:\/)?[^\x00-\x20<>&\/\[\]=]+/', $html );
	}

	/**
	 * Old pasted HTML contains six inert Tailwind CSS bracket expressions. Mask
	 * only those expressions inside real, quoted class attributes for detection.
	 * The source and replacements are never changed here; new HTML still uses
	 * the strict shortcode guard above. Other attributes and text stay visible.
	 */
	private static function source_has_shortcode_marker( $html ) {
		$parts = wp_html_split( $html );
		foreach ( $parts as $part_index => $part ) {
			if ( ! preg_match( '/\A<[A-Za-z][A-Za-z0-9:-]*/', $part, $opening ) ) {
				continue;
			}
			$offset = strlen( $opening[0] );
			$length = strlen( $part );
			// Consume every attribute in order, including quoted attributes other
			// than class, so text such as title='class="[gallery]"' is not masked.
			$attribute_pattern = '/\G[\x20\t\r\n\f]+([^\x00-\x20\x22\x27<>\/=]+)(?:[\x20\t\r\n\f]*=[\x20\t\r\n\f]*(?:\x22([^\x22]*)\x22|\x27([^\x27]*)\x27|([^\x00-\x20\x22\x27=<>`]+)))?/';
			while ( $offset < $length && preg_match( $attribute_pattern, $part, $attribute, PREG_OFFSET_CAPTURE, $offset ) ) {
				$offset += strlen( $attribute[0][0] );
				if ( 'class' !== strtolower( $attribute[1][0] ) ) {
					continue;
				}
				$value_group = isset( $attribute[2] ) && $attribute[2][1] >= 0 ? 2 : ( isset( $attribute[3] ) && $attribute[3][1] >= 0 ? 3 : null );
				if ( null === $value_group ) {
					continue; // Unquoted class values deliberately keep the strict guard.
				}
				$value   = $attribute[ $value_group ][0];
				$allowed = array( '[40rem]', '[48rem]', '[20px]', '[calc(100%-115px)]', '[calc(100%-.5rem)]', '[.text-message+&amp;]' );
				foreach ( $allowed as $expression ) {
					$value = str_replace( $expression, str_repeat( ' ', strlen( $expression ) ), $value );
				}
				// Equal-length masking preserves parser offsets for later attributes.
				$part = substr_replace( $part, $value, $attribute[ $value_group ][1], strlen( $attribute[ $value_group ][0] ) );
			}
			$parts[ $part_index ] = $part;
		}
		return self::has_shortcode_marker( implode( '', $parts ) );
	}

	private static function inspect( $content ) {
		if ( ! is_string( $content ) || strlen( $content ) > self::MAX_SOURCE_BYTES ) {
			return new WP_Error( 'yoona_divi_source', 'Źródłowa treść Divi musi być tekstem do 5 MB.' );
		}
		$balanced = self::validate_structure( $content );
		if ( is_wp_error( $balanced ) ) {
			return $balanced;
		}
		$all   = array( 'et_pb_text' => array(), 'et_pb_toggle' => array() );
		$spans = array();
		foreach ( array_keys( $all ) as $type ) {
			$pattern = '/' . get_shortcode_regex( array( $type ) ) . '/s';
			$count   = preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
			if ( false === $count ) {
				return new WP_Error( 'yoona_divi_regex', 'Nie udało się bezpiecznie przeanalizować shortcode Divi.' );
			}
			foreach ( $matches as $match ) {
				if ( '' !== $match[1][0] || '' !== $match[6][0] || '' !== $match[4][0] || ! isset( $match[5] ) || $match[5][1] < 0 || substr( $match[0][0], -strlen( '[/' . $type . ']' ) ) !== '[/' . $type . ']' ) {
					return new WP_Error( 'yoona_divi_ambiguous', 'Wykryto escaped, samodomykający lub niedomknięty moduł Divi: ' . $type );
				}
				$index            = count( $all[ $type ] );
				$all[ $type ][]    = array(
					'module_type' => $type,
					'module_index' => $index,
					'html' => $match[5][0],
					'html_sha256' => hash( 'sha256', $match[5][0] ),
					'_offset' => $match[5][1],
					'_length' => strlen( $match[5][0] ),
				);
				$spans[] = array( 'start' => $match[0][1], 'end' => $match[0][1] + strlen( $match[0][0] ) );
			}
			// A malformed opening must not silently disappear from enumeration.
			if ( $balanced[ $type ] !== $count ) {
				return new WP_Error( 'yoona_divi_enumeration', 'Nie wszystkie moduły Divi można jednoznacznie odczytać: ' . $type );
			}
		}
		usort(
			$spans,
			static function ( $a, $b ) {
				return $a['start'] <=> $b['start'];
			}
		);
		$previous_end = -1;
		foreach ( $spans as $span ) {
			if ( $span['start'] < $previous_end ) {
				return new WP_Error( 'yoona_divi_overlap', 'Moduły tekstowe Divi są zagnieżdżone lub nakładają się. Wymagają ręcznej edycji.' );
			}
			$previous_end = $span['end'];
		}
		return $all;
	}

	/**
	 * Check all Divi tag pairs while retaining their original spelling and bytes.
	 * et_pb_line_break_holder is Divi's non-paired code-module placeholder.
	 */
	private static function validate_structure( $content ) {
		$count = preg_match_all( '/\[(\/?)(et_pb_[A-Za-z0-9_]+)(?![A-Za-z0-9_-])/', $content, $markers, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
		if ( false === $count ) {
			return new WP_Error( 'yoona_divi_regex', 'Nie udało się przeanalizować układu Divi.' );
		}
		$stack  = array();
		$counts = array( 'et_pb_text' => 0, 'et_pb_toggle' => 0 );
		foreach ( $markers as $marker ) {
			$start   = $marker[0][1];
			$end     = strpos( $content, ']', $start );
			$name    = $marker[2][0];
			$closing = '/' === $marker[1][0];
			if ( false === $end || ( $start > 0 && '[' === $content[ $start - 1 ] ) || ( isset( $content[ $end + 1 ] ) && ']' === $content[ $end + 1 ] ) ) {
				return new WP_Error( 'yoona_divi_structure', 'Wykryto escaped lub niedomknięty shortcode w źródłowym układzie Divi.' );
			}
			$token = substr( $content, $start, $end - $start + 1 );
			$tail  = substr( $token, strlen( $marker[0][0] ) );
			if ( strtolower( $name ) !== $name || false !== strpos( substr( $token, 1 ), '[' ) || ! preg_match( '/\A(?:\s|\/|\])/', $tail ) || ! self::balanced_attribute_quotes( $tail ) ) {
				return new WP_Error( 'yoona_divi_structure', 'Wykryto niejednoznaczny lub uszkodzony znacznik układu Divi.' );
			}
			if ( 'et_pb_line_break_holder' === $name && ! $closing && '[et_pb_line_break_holder]' === $token ) {
				continue;
			}
			if ( $closing ) {
				if ( '[/' . $name . ']' !== $token || ! $stack || array_pop( $stack ) !== $name ) {
					return new WP_Error( 'yoona_divi_structure', 'Znaczniki układu Divi są niepoprawnie zagnieżdżone lub niezrównoważone.' );
				}
				continue;
			}
			$self_closing = '/]' === substr( $token, -2 );
			if ( self::supported_type( $name ) ) {
				if ( $self_closing || in_array( $name, $stack, true ) ) {
					return new WP_Error( 'yoona_divi_nested', 'Samodomykające i zagnieżdżone moduły tego samego typu nie są obsługiwane: ' . $name );
				}
				++$counts[ $name ];
			}
			if ( ! $self_closing ) {
				$stack[] = $name;
			}
		}
		if ( $stack ) {
			return new WP_Error( 'yoona_divi_structure', 'Źródłowy układ Divi zawiera niedomknięte znaczniki.' );
		}
		return $counts;
	}

	private static function balanced_attribute_quotes( $attributes ) {
		$quote  = '';
		$length = strlen( $attributes );
		for ( $i = 0; $i < $length; ++$i ) {
			$char = $attributes[ $i ];
			if ( '' !== $quote ) {
				if ( $char === $quote ) {
					$quote = '';
				}
			} elseif ( '"' === $char || "'" === $char ) {
				$quote = $char;
			}
		}
		return '' === $quote;
	}
}
