<?php
/**
 * Plugin Name: Yoona — wygląd i SEO
 * Description: Nowy wygląd witryny Yoona, poprawki Divi i Rank Math, podgląd oraz cofanie zapisanych zmian.
 * Version: 3.3.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Yoona
 * License: GPL-2.0-or-later
 * Text Domain: yoona-seo
 */

if (!defined('ABSPATH')) {
    exit;
}

define('YOONA_SEO_VERSION', '3.3.0');
define('YOONA_SEO_FILE', __FILE__);

require_once __DIR__ . '/includes/class-yoona-seo-divi.php';
require_once __DIR__ . '/includes/class-yoona-seo-engine.php';
require_once __DIR__ . '/includes/class-yoona-seo-design.php';
require_once __DIR__ . '/includes/class-yoona-seo-offer-hero.php';
require_once __DIR__ . '/includes/class-yoona-seo-content-cleanup.php';
require_once __DIR__ . '/includes/class-yoona-seo-project-gallery.php';
require_once __DIR__ . '/includes/class-yoona-seo-admin.php';

/** Metadata is rendered only after an explicit administrator deployment. */
final class Yoona_SEO_Plugin {
    public static function init() {
        Yoona_SEO_Design::register();
        Yoona_SEO_Project_Gallery::register();
        Yoona_SEO_Admin::register();
        add_filter('pre_get_document_title', array(__CLASS__, 'title'), 99);
        foreach (array('wpseo_title', 'wpseo_opengraph_title', 'wpseo_twitter_title',
            'rank_math/frontend/title', 'rank_math/opengraph/facebook/title',
            'rank_math/opengraph/twitter/title', 'aioseo_title', 'seopress_titles_title') as $hook) {
            add_filter($hook, array(__CLASS__, 'title'), 99);
        }
        foreach (array('wpseo_metadesc', 'wpseo_opengraph_desc', 'wpseo_twitter_description',
            'rank_math/frontend/description', 'rank_math/opengraph/facebook/description',
            'rank_math/opengraph/twitter/description', 'aioseo_description', 'seopress_titles_desc') as $hook) {
            add_filter($hook, array(__CLASS__, 'description'), 99);
        }
        add_action('wp_head', array(__CLASS__, 'description_tag'), 5);
    }

    private static function value($key) {
        if (is_admin() || is_feed() || (!is_singular() && !is_front_page())) {
            return '';
        }
        $post_id = get_queried_object_id();
        if (!$post_id || !in_array(get_post_type($post_id), array('page', 'post'), true)) {
            return '';
        }
        return trim((string) get_post_meta($post_id, $key, true));
    }

    public static function title($original) {
        $value = self::value('_yoona_seo_title');
        return $value !== '' ? $value : $original;
    }

    public static function description($original) {
        $value = self::value('_yoona_seo_description');
        return $value !== '' ? $value : $original;
    }

    public static function description_tag() {
        // Existing SEO plugins render their own tag; the filters above supply the copy.
        if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION')
            || defined('SEOPRESS_VERSION') || defined('THE_SEO_FRAMEWORK_VERSION')) {
            return;
        }
        $value = self::value('_yoona_seo_description');
        if ($value !== '') {
            echo '<meta name="description" content="' . esc_attr($value) . '" />' . "\n";
        }
    }
}

add_action('plugins_loaded', array('Yoona_SEO_Plugin', 'init'));
