<?php
/**
 * Theme supports, image sizes, nav menus.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

add_action('after_setup_theme', static function (): void {
    // Titre via WP (SEO propre).
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);

    // Tailles d'images optimisées pour la grille films + galerie photos.
    // La grille films est 2 colonnes max → 900px large suffit sur desktop (retina 2x ≈ 1800px).
    // Ratio natif sur les vignettes films (pas de crop) : Lucie intègre ses
    // propres logos festivals dans l'image, on ne les tranche pas.
    add_image_size('lb3-film-card', 1400, 0, false);        // ratio natif (pas de crop) pour grille films
    add_image_size('lb3-film-cover', 2400, 0, false);       // cover plein écran single film
    add_image_size('lb3-photo-thumb', 800, 0, false);       // vignette galerie photos
    add_image_size('lb3-photo-full', 2400, 0, false);       // lightbox photos
    add_image_size('lb3-hero-bg', 2400, 0, false);          // background hero home

    // Menus — on n'en utilise pas réellement (ancres hardcodées),
    // mais on déclare un "primary" au cas où Lucie en aurait besoin.
    register_nav_menus([
        'primary' => __('Menu principal', 'lb3'),
    ]);

    // Polylang : inscrire les strings statiques utilisées dans les templates
    // (labels de filtre pour catégorie "Tout" notamment).
    if (function_exists('pll_register_string')) {
        pll_register_string('lb3_filter_all', 'Tout', 'lb3');
    }
});

/**
 * Ajoute `lb-track-scroll` sur <body> pour les pages à contenu long et linéaire
 * (home + pages légales). Lu côté JS par initScrollDepth() pour gater le tracking.
 */
add_filter('body_class', static function (array $classes): array {
    if (is_front_page()) {
        $classes[] = 'lb-track-scroll';
        return $classes;
    }
    if (is_singular('page')) {
        $slug = (string) get_post_field('post_name', get_queried_object_id());
        $legal_slugs = ['mentions-legales', 'politique-confidentialite', 'legal-notice', 'privacy-policy'];
        if (is_page_template('page-legal.php') || in_array($slug, $legal_slugs, true)) {
            $classes[] = 'lb-track-scroll';
        }
    }
    return $classes;
});

/**
 * Exclut les pages des résultats de recherche (on ne cherche que des films).
 */
add_filter('pre_get_posts', static function (\WP_Query $query): \WP_Query {
    if (!is_admin() && $query->is_search()) {
        $query->set('post_type', 'post');
    }
    return $query;
});

/**
 * Filet de sécurité : si une page légale a un slug connu mais n'a pas (encore)
 * le template "Page légale" assigné, on charge quand même page-legal.php.
 * Évite un renvoi vers la home via index.php.
 */
add_filter('template_include', static function (string $template): string {
    if (!is_singular('page')) {
        return $template;
    }
    $slug = get_post_field('post_name', get_queried_object_id());
    $legal_slugs = ['mentions-legales', 'politique-confidentialite', 'legal-notice', 'privacy-policy'];
    if (in_array($slug, $legal_slugs, true)) {
        $custom = LB3_DIR . '/page-legal.php';
        if (is_readable($custom)) {
            return $custom;
        }
    }
    return $template;
}, 20);

/**
 * Autorise les iframes dans les champs WYSIWYG ACF (YouTube, Vimeo, etc.).
 * Autorise aussi l'attribut `style` inline sur les balises de texte courantes,
 * pour préserver les couleurs choisies par Lucie dans l'éditeur visuel
 * (ex : `<span style="color: #ffcc00">...</span>`). `safecss_filter_attr()`
 * filtre ensuite la liste des propriétés CSS autorisées (color en fait partie).
 */
add_filter('wp_kses_allowed_html', static function (array $tags, string $context): array {
    if ($context === 'post' || $context === 'acf') {
        $tags['iframe'] = [
            'src'                 => true,
            'width'               => true,
            'height'              => true,
            'frameborder'         => true,
            'allow'               => true,
            'allowfullscreen'     => true,
            'loading'             => true,
            'referrerpolicy'      => true,
            'sandbox'             => true,
            'title'               => true,
        ];

        foreach (['span', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'em', 'a', 'div', 'li', 'ul', 'ol', 'blockquote'] as $tag) {
            if (!isset($tags[$tag]) || !is_array($tags[$tag])) {
                $tags[$tag] = [];
            }
            $tags[$tag]['style'] = true;
        }
    }
    return $tags;
}, 10, 2);

/**
 * TinyMCE : laisser passer les styles inline générés par le color picker
 * et autres options de mise en forme. Sans ça, l'éditeur « nettoie » le
 * <span style="color:…"> à la sauvegarde et Lucie perd ses couleurs.
 */
add_filter('tiny_mce_before_init', static function (array $init): array {
    $init['verify_html'] = false;
    $init['keep_styles'] = true;
    return $init;
});

/**
 * Validation du nouveau champ `video_url` : n'accepte que Vimeo / YouTube.
 *
 * ACF valide déjà le format URL (HTML5 `type="url"`), ce hook ajoute la
 * contrainte métier : rejeter les URLs d'autres plateformes (twitter, tiktok…).
 */
add_filter('acf/validate_value/name=video_url', static function ($valid, $value, $field, $input) {
    if ($valid !== true) {
        return $valid;
    }
    if (!is_string($value) || trim($value) === '') {
        return $valid; // Champ optionnel.
    }
    if (!preg_match('#(?:vimeo\.com|youtube\.com|youtu\.be|youtube-nocookie\.com)#i', $value)) {
        return __('Seules les URLs Vimeo et YouTube sont acceptées.', 'lb3');
    }
    return $valid;
}, 10, 4);

/**
 * Champ legacy `iframe` : si la valeur stockée en DB est vide, on cache le
 * champ dans l'admin — les nouvelles fiches ne le voient jamais. Si la valeur
 * est non vide, on affiche le wysiwyg pour permettre à Lucie de copier l'URL
 * dans le nouveau champ `video_url` puis de vider ce champ legacy.
 */
add_filter('acf/prepare_field/name=iframe', static function ($field) {
    if (!is_array($field)) {
        return $field;
    }
    $post_id = get_the_ID();
    if (!$post_id && isset($_GET['post'])) {
        $post_id = (int) $_GET['post'];
    }
    if (!$post_id) {
        return false; // Nouveau post : champ legacy masqué.
    }
    $stored = get_post_meta($post_id, 'iframe', true);
    if (!is_string($stored) || trim($stored) === '') {
        return false;
    }
    return $field;
});

/**
 * Nettoyage à la volée des valeurs legacy du champ `iframe` au rendu :
 * - décode les entités HTML (utile pour les embeds collés via Gutenberg)
 * - supprime l'enveloppe <p> ajoutée par wpautop
 * - supprime les width/height hardcodés (gérés par le wrapper aspect-ratio)
 * - injecte un title="Bande-annonce de {titre}" si absent (a11y)
 *
 * Ne s'applique qu'au champ legacy ; les iframes générées par
 * lb3_build_video_iframe() (nouveau champ) sont déjà propres.
 */
add_filter('acf/format_value/type=wysiwyg', static function ($value, $post_id, $field) {
    if (isset($field['name']) && $field['name'] === 'iframe' && is_string($value) && $value !== '') {
        $value = html_entity_decode($value);
        $value = preg_replace('#<p>\s*(<iframe\b[^>]*>.*?</iframe>)\s*</p>#si', '$1', $value);
        $value = preg_replace('#\s+(?:width|height)\s*=\s*(?:"[^"]*"|\'[^\']*\')#i', '', $value);

        $film_title = get_the_title($post_id);
        if ($film_title !== '') {
            /* translators: %s : titre du film. */
            $iframe_title = sprintf(__('Bande-annonce de %s', 'lb3'), $film_title);
            $value = preg_replace_callback(
                '#<iframe\b([^>]*)>#i',
                static function (array $m) use ($iframe_title): string {
                    if (preg_match('#\stitle\s*=\s*(?:"[^"]*"|\'[^\']*\')#i', $m[1])) {
                        return $m[0];
                    }
                    return '<iframe title="' . esc_attr($iframe_title) . '"' . $m[1] . '>';
                },
                $value
            );
        }
    }
    return $value;
}, 20, 3);

/**
 * Active acf-json (save/load automatique des field groups en JSON dans /acf-json).
 */
add_filter('acf/settings/save_json', static function (): string {
    return LB3_DIR . '/acf-json';
});
add_filter('acf/settings/load_json', static function (array $paths): array {
    unset($paths[0]);
    $paths[] = LB3_DIR . '/acf-json';
    return $paths;
});
