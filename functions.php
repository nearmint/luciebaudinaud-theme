<?php
/**
 * lb3 — Thème WordPress de portfolio pour Lucie Baudinaud, directrice de la photographie.
 *
 * Ce fichier est le point d'entrée unique du thème. Il ne contient AUCUNE logique
 * métier : son seul rôle est de définir les constantes globales, de vérifier les
 * prérequis runtime (PHP, WordPress), puis de charger les modules de /inc/
 * dans le bon ordre.
 *
 * Architecture :
 * - /inc/*.php      Modules fonctionnels (un domaine = un fichier)
 * - /template-parts/*.php  Partials réutilisables
 * - /src/           Sources JS/CSS (compilées par Vite vers /dist/)
 * - /dist/          Assets compilés (versionnés pour prod, régénérés par npm run build)
 *
 * Convention de nommage :
 * - Toutes les fonctions globales du thème sont préfixées `lb3_`.
 * - Toutes les constantes sont préfixées `LB3_`.
 *
 * @package   lb3
 * @author    Nicolas Rapp <https://www.nicolasrapp.co>
 * @copyright 2026 Nicolas Rapp
 * @license   GPL-2.0-or-later
 * @link      https://luciebaudinaud.com
 * @since     1.0.0
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// ─────────────────────────────────────────────────────────────────────────────
// Prérequis runtime
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Version PHP minimale requise. Doit rester alignée avec le header "Requires PHP"
 * dans style.css et avec la version cible en production (OVH).
 */
const LB3_MIN_PHP = '8.1.0';

/**
 * Version WordPress minimale requise.
 */
const LB3_MIN_WP = '6.4';

/**
 * Vérifie les prérequis runtime. En cas d'échec, affiche un message d'admin
 * clair et empêche le chargement du thème plutôt que de laisser un fatal error
 * cryptique côté front.
 *
 * @return bool true si tous les prérequis sont OK, false sinon.
 */
function lb3_check_requirements(): bool
{
    if (version_compare(PHP_VERSION, LB3_MIN_PHP, '<')) {
        add_action('admin_notices', static function (): void {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    /* translators: 1: current PHP version, 2: required PHP version */
                    esc_html__('lb3 requires PHP %2$s or higher. You are running PHP %1$s. Please upgrade PHP.', 'lb3'),
                    esc_html(PHP_VERSION),
                    esc_html(LB3_MIN_PHP)
                )
            );
        });
        return false;
    }

    global $wp_version;
    if (version_compare($wp_version, LB3_MIN_WP, '<')) {
        add_action('admin_notices', static function () use ($wp_version): void {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    /* translators: 1: current WP version, 2: required WP version */
                    esc_html__('lb3 requires WordPress %2$s or higher. You are running %1$s. Please upgrade WordPress.', 'lb3'),
                    esc_html($wp_version),
                    esc_html(LB3_MIN_WP)
                )
            );
        });
        return false;
    }

    return true;
}

if (!lb3_check_requirements()) {
    return;
}

// ─────────────────────────────────────────────────────────────────────────────
// Constantes globales du thème
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Lit la version du thème depuis le header de style.css (source de vérité unique).
 * Évite la désynchronisation entre la constante et le header WordPress.
 */
function lb3_get_theme_version(): string
{
    static $version = null;
    if ($version !== null) {
        return $version;
    }
    $theme = wp_get_theme(get_template());
    $version = $theme->get('Version') ?: '1.0.0';
    return $version;
}

define('LB3_VERSION', lb3_get_theme_version());
define('LB3_DIR', get_template_directory());
define('LB3_URI', get_template_directory_uri());
define('LB3_TEXTDOMAIN', 'lb3');
define('LB3_INC_DIR', LB3_DIR . '/inc');

// ─────────────────────────────────────────────────────────────────────────────
// Chargement des modules
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Require sécurisé d'un module. En debug, log un warning si le fichier manque.
 * En prod, silencieux (pour éviter de casser le site si un module est retiré).
 *
 * @param string $module Nom du module (sans extension), ex : 'i18n'
 */
function lb3_require_module(string $module): void
{
    $file = LB3_INC_DIR . '/' . $module . '.php';
    if (is_readable($file)) {
        require_once $file;
        return;
    }
    if (defined('WP_DEBUG') && WP_DEBUG) {
        trigger_error(
            sprintf('lb3: module "%s" introuvable (%s)', $module, $file),
            E_USER_WARNING
        );
    }
}

/**
 * Liste ordonnée des modules à charger. L'ordre importe :
 *
 * 1. i18n     → charge le textdomain AVANT toute string traduisible
 * 2. setup    → theme supports, image sizes, post type supports (doit précéder assets)
 * 3. cleanup  → head cleanup (emoji, block library)
 * 4. security → headers, XML-RPC disable, author enum
 * 5. assets   → enqueue CSS/JS via manifest Vite (après setup pour les image sizes)
 * 6. seo      → meta, OG, hreflang, JSON-LD
 * 7. render   → callbacks wp_footer (modales)
 * 8. template → helpers utilisés dans les templates
 * 9. picture  → helper <picture> AVIF/WebP (consommé par hero.php / film-card.php)
 * 10. content-sanitize → strip des balises vides (headings) dans les WYSIWYG
 * 11. acf-fields  → field groups
 * 12. acf-cache   → wrapper get_field cache
 * 13. umami       → script analytics
 * 14. indexnow    → endpoint IndexNow + hooks de notification
 * 15. service-worker → kill switch (le SW réel a été retiré, voir inc/service-worker.php)
 * 16. admin-alt-validator → admin only
 * 17. tinymce-lang-button → admin only
 */
const LB3_MODULES = [
    // Core (obligatoire, dans cet ordre)
    'i18n',
    'setup',
    'cleanup',
    'security',

    // Rendu front
    'assets',
    'seo',
    'render',
    'template',
    'picture',
    'content-sanitize',

    // Données / contenu
    'acf-fields',
    'acf-cache',

    // Intégrations
    'umami',
    'indexnow',
    'service-worker',  // Kill switch (le SW réel a été retiré, voir inc/service-worker.php)

    // Admin only (auto-guardés par is_admin() dans chaque fichier)
    'admin-alt-validator',
    'tinymce-lang-button',
];

foreach (LB3_MODULES as $module) {
    lb3_require_module($module);
}