<?php
/** Fixed, decorative SVGs for public service links and social profiles. */
defined( 'ABSPATH' ) || exit;

final class Yoona_SEO_Icons {

	/** Return a semantic icon for a known offer URL; no user markup is rendered. */
	public static function service( $path ) {
		$path = parse_url( (string) $path, PHP_URL_PATH );
		$path = '/' . trim( strtolower( (string) $path ), '/' ) . '/';
		$mapping = array(
			'/litery-3d/' => 'letters', '/reklamy-swietlne/' => 'light-sign', '/tablice-olsztyn/' => 'board',
			'/drukarnia-wielkoformatowa/' => 'printer', '/oklejanie-samochodow/' => 'car', '/zmiana-koloru-samochodu/' => 'car-colour',
			'/oklejanie-szyb/' => 'window', '/reklama-zewnetrzna/' => 'billboard', '/szyldy/' => 'shop-sign',
			'/banery/' => 'banner', '/beczki-reklamowe/' => 'barrel', '/reklama-na-rusztowaniu/' => 'scaffold',
			'/reklama-na-rusztowaniu-2/' => 'scaffold', '/plotki-ogrodki-piwne/' => 'terrace', '/neony/' => 'neon',
			'/siatka-mesh/' => 'mesh', '/oklejanie-samochodow-osobowych/' => 'car', '/oklejanie-autobusow/' => 'bus',
			'/oklejanie-samochodow-ciezarowych/' => 'truck', '/kasetony-swietlne/' => 'lightbox', '/pylony/' => 'pylon',
			'/markizy-z-logo/' => 'awning', '/flagi/' => 'flag', '/fototapety/' => 'wallpaper', '/totemy/' => 'totem',
			'/scianki-reklamowe/' => 'backdrop', '/oklejanie-przyczep-gastronomicznych/' => 'food-trailer', '/mobilna-reklama/' => 'mobile-ad',
			'/ledony/' => 'neon', '/tabliczki-informacyjne/' => 'wayfinding', '/windery/' => 'windflag', '/billboardy/' => 'billboard',
			'/etykiety/' => 'label', '/naklejki/' => 'sticker', '/potykacze/' => 'a-board', '/rollupy/' => 'rollup',
			'/koszulki/' => 'shirt', '/menuboardy/' => 'menu', '/oklejanie-samochodu-cena/' => 'car-price',
			'/frezowanie-cnc/' => 'cnc', '/tablice-unijne/' => 'eu-board', '/frezowanie-plexi/' => 'plexi',
			'/frezowanie-dibondu/' => 'layers', '/frezowanie-elementow-reklamowych/' => 'cnc', '/markizy-tarasowe/' => 'awning',
			'/markizy-wolnostojace-duo/' => 'duo-awning', '/znakowanie-markiz/' => 'printed-awning',
		);
		$name = isset( $mapping[ $path ] ) ? $mapping[ $path ] : 'spark';
		$shapes = array(
			'letters' => '<path d="m3 20 6-16h4l6 16M6 13h10M10 4l6 1 6 15h-3M19 20l3-1"/>',
			'light-sign' => '<rect x="5" y="6" width="14" height="12" rx="1"/><path d="M9 10h6M9 14h6M12 2v2m0 16v2M2 12h1m18 0h1M4 4l2 2m12 12 2 2M4 20l2-2M18 6l2-2"/>',
			'board' => '<rect x="3" y="4" width="18" height="13" rx="1"/><path d="M7 17v4m10-4v4M7 8h10M7 12h7"/>',
			'printer' => '<path d="M6 8V3h12v5M6 17H3V8h18v9h-3M6 14h12v7H6zM6 11h1M10 17h4"/>',
			'car' => '<path d="m3 12 2-6h14l2 6v7h-3v-3H6v3H3zM3 12h18M6 9h12M6 14h2m8 0h2"/>',
			'car-colour' => '<path d="m3 13 2-5h9l2 5v6h-3v-3H6v3H3zM3 13h13M6 15h1m5 0h1M18 2c-1 2-3 4-3 6a3 3 0 0 0 6 0c0-2-2-4-3-6Z"/>',
			'window' => '<rect x="3" y="3" width="18" height="18" rx="1"/><path d="M12 3v18M3 12h18M6 9l3-3m6 12 3-3"/>',
			'billboard' => '<rect x="2" y="3" width="20" height="13" rx="1"/><path d="M8 16v5m8-5v5M6 21h12M6 7h12M6 11h8"/>',
			'shop-sign' => '<path d="M3 3v18M3 5h18M7 5v4m12-4v4"/><rect x="5" y="9" width="16" height="9" rx="1"/><path d="M9 13h8"/>',
			'banner' => '<path d="M3 4h18v16H3zM6 7h.01M18 7h.01M6 17h.01M18 17h.01M7 10h10M9 14h6"/>',
			'barrel' => '<ellipse cx="12" cy="4" rx="7" ry="2"/><path d="M5 4c-2 5-2 11 0 16 3 2 11 2 14 0 2-5 2-11 0-16M4 9h16M4 16h16M9 11h6v3H9z"/>',
			'scaffold' => '<path d="M3 2v20M21 2v20M2 6h20M2 18h20M3 6l18 12M21 6 3 18"/><path d="M7 9h10v6H7z" fill="var(--yn-paper,#f6f5f1)"/>',
			'terrace' => '<path d="M2 10h9v10H2zM2 13h9M5 10v10m3-10v10M14 13h8m-4 0v7M18 3l-5 7h10z"/>',
			'neon' => '<path d="M4 20V4h4l8 16h4V4M7 20V9l7 11M10 4l7 11V4"/>',
			'mesh' => '<rect x="3" y="3" width="18" height="18" rx="1"/><path d="M3 8h18M3 13h18M3 18h18M8 3v18M13 3v18M18 3v18"/>',
			'bus' => '<rect x="3" y="3" width="18" height="16" rx="2"/><path d="M3 11h18M8 3v8m8-8v8M6 15h2m8 0h2M6 19v2m12-2v2"/>',
			'truck' => '<path d="M2 5h13v12H2zM15 9h4l3 4v4h-7M18 9v4h4"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
			'lightbox' => '<rect x="4" y="5" width="16" height="14" rx="1"/><path d="M8 9h8M8 13h5M1 8h1m20 0h1M1 16h1m20 0h1"/>',
			'pylon' => '<path d="M7 21V3h10v18M5 21h14M10 7h4m-4 4h4m-4 4h4M7 3l10-1v1"/>',
			'awning' => '<path d="M3 9 5 3h14l2 6M3 9v3a2.25 2.25 0 0 0 4.5 0 2.25 2.25 0 0 0 4.5 0 2.25 2.25 0 0 0 4.5 0 2.25 2.25 0 0 0 4.5 0V9ZM8 9l1-6m7 6-1-6M5 16v5m14-5v5"/>',
			'flag' => '<path d="M5 22V2m0 2c5-5 9 5 15 0v11c-6 5-10-5-15 0"/>',
			'wallpaper' => '<path d="M4 3h14v18H4zM18 3c5 0 5 7 0 7M7 7l4 4-4 4m4-8 4 4-4 4M7 19h8"/>',
			'totem' => '<path d="M7 21V3h10v18M5 21h14M10 7h4M10 11h4M10 15h4"/>',
			'backdrop' => '<path d="M3 4h18v15H3zM3 19l-2 3m20-3 2 3M7 8h3v3H7zm7 0h3v3h-3zM10 14h4"/>',
			'food-trailer' => '<path d="M2 5h17v13H2zM19 18h4M6 9h9v5H6zM5 14h11"/><circle cx="7" cy="19" r="2"/><path d="M12 18v3"/>',
			'mobile-ad' => '<path d="M2 3h14v11H2zM3 14v4h18v-6l-3-3h-2M6 7h6"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/>',
			'wayfinding' => '<path d="M12 2v20M3 5h14l4 4-4 4H3zM8 17h13M7 14l-3 3 3 3"/>',
			'windflag' => '<path d="M5 22V4C14-2 19 9 17 17c-4-2-8-2-12 0M3 22h6"/>',
			'label' => '<path d="m3 3 8 1 10 10-7 7L4 11Z"/><circle cx="7.5" cy="7.5" r="1.5"/><path d="m12 10 5 5m-7-2 4 4"/>',
			'sticker' => '<path d="M21 12a9 9 0 1 0-9 9l9-9ZM21 12h-5a4 4 0 0 0-4 4v5M7 8h8M7 12h3"/>',
			'a-board' => '<path d="m4 22 4-20h8l4 20M7 6h10M6 17h12M9 10h6m-6 3h4M4 22l8-4 8 4"/>',
			'rollup' => '<path d="M6 2h12v18H6zM4 22h16M9 6h6M9 10h6M9 14h4M12 20v2"/>',
			'shirt' => '<path d="m8 3-6 4 3 5 3-2v11h8V10l3 2 3-5-6-4a4 4 0 0 1-8 0Z"/><path d="M10 12h4m-4 3h4"/>',
			'menu' => '<rect x="4" y="2" width="16" height="20" rx="1"/><path d="M8 6h8M8 10h5m3 0h.01M8 14h5m3 0h.01M8 18h5m3 0h.01"/>',
			'car-price' => '<path d="m2 13 2-5h10l2 5v6h-3v-3H5v3H2zM2 13h14M5 15h1m6 0h1M18 2v2m3 0h-3a2 2 0 0 0 0 4h1a2 2 0 0 1 0 4h-3m2 0v2"/>',
			'cnc' => '<path d="M3 3h18v5H3zM8 8v5h8V8M10 13v3l2 3 2-3v-3M2 21h20M4 17v4m16-4v4"/>',
			'eu-board' => '<rect x="2" y="4" width="20" height="16" rx="1"/><path d="M5 4v16M9 9h.01M12 7h.01M15 9h.01M15 13h.01M12 15h.01M9 13h.01M18 9h1m-1 4h1"/>',
			'plexi' => '<path d="m3 7 12-5 6 4v12l-12 4-6-4zM3 7l6 4 12-5M9 11v11M12 14l5-2m-5 5 5-2"/>',
			'layers' => '<path d="m2 7 10-5 10 5-10 5ZM2 12l10 5 10-5M2 17l10 5 10-5"/>',
			'duo-awning' => '<path d="M2 10 6 4h12l4 6M2 10h20M12 4v18M3 10v3m18-3v3M7 22h10M6 4l2 6m10-6-2 6"/>',
			'printed-awning' => '<path d="M3 9 5 3h14l2 6M3 9h18v4H3zM5 16v5m14-5v5M10 5h4m-2 4v4"/>',
			'spark' => '<path d="m12 2 3 7 7 3-7 3-3 7-3-7-7-3 7-3Z"/>',
		);
		return '<svg class="yn-icon yn-icon-service" data-yn-icon="' . $name . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $shapes[ $name ] . '</svg>';
	}

	/** Brand silhouettes are fixed artwork; the surrounding link names the site. */
	public static function social( $name ) {
		$name = strtolower( (string) $name );
		$shapes = array(
			'facebook' => '<path d="M14 22v-9h3l.5-4H14V7c0-1.2.4-2 2-2h2V1.4A25 25 0 0 0 15 1c-3 0-5 1.8-5 5v3H7v4h3v9Z"/>',
			'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.6" cy="6.5" r="1.2"/>',
			'tiktok' => '<path d="M14 2h3c.3 2.4 1.7 4 4 4.5v3.2a10 10 0 0 1-4-1.4v7.3a6.4 6.4 0 1 1-5.5-6.3v3.3a3.2 3.2 0 1 0 2.5 3V2Z"/>',
			'youtube' => '<path d="M21.5 6.4c-.2-.8-.8-1.4-1.6-1.6C18.5 4.4 12 4.4 12 4.4s-6.5 0-7.9.4c-.8.2-1.4.8-1.6 1.6C2.1 7.8 2.1 12 2.1 12s0 4.2.4 5.6c.2.8.8 1.4 1.6 1.6 1.4.4 7.9.4 7.9.4s6.5 0 7.9-.4c.8-.2 1.4-.8 1.6-1.6.4-1.4.4-5.6.4-5.6s0-4.2-.4-5.6ZM10 15.5v-7l6 3.5Z" fill-rule="evenodd"/>',
		);
		if ( ! isset( $shapes[ $name ] ) ) {
			return '';
		}
		return '<svg class="yn-icon yn-icon-social yn-icon-' . $name . '" viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true" focusable="false">' . $shapes[ $name ] . '</svg>';
	}
}
