<?php
/**
 * i18n bootstrap + helpers Polylang.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

add_action('after_setup_theme', static function (): void {
    load_theme_textdomain(LB3_TEXTDOMAIN, LB3_DIR . '/languages');
});

/**
 * Retourne l'ID de la home FR, peu importe la langue courante.
 *
 * Utilisé pour partager les champs ACF (cover, galerie, bio, CV) entre FR et EN
 * sans dupliquer le contenu côté admin.
 */
function lb3_get_fr_front_page_id(): int
{
    $fr_id = (int) get_option('page_on_front');

    if (!$fr_id) {
        return (int) get_the_ID();
    }

    if (function_exists('pll_get_post')) {
        $pll_fr_id = pll_get_post($fr_id, 'fr');
        if ($pll_fr_id) {
            return (int) $pll_fr_id;
        }
    }

    return $fr_id;
}

/**
 * Langue courante : 'fr' par défaut.
 */
function lb3_current_lang(): string
{
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language('slug');
        if ($lang) {
            return $lang;
        }
    }
    return 'fr';
}

/**
 * URL de la home dans la langue courante.
 */
function lb3_home_url_localized(): string
{
    if (function_exists('pll_home_url')) {
        return pll_home_url(lb3_current_lang());
    }
    return home_url('/');
}
