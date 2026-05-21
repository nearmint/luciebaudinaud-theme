<?php
/**
 * SEO : meta description, Open Graph, Twitter Cards, hreflang, JSON-LD.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Description de la page courante.
 */
function lb3_seo_description(): string
{
    if (is_singular('post')) {
        $excerpt = get_the_excerpt();
        if ($excerpt) {
            return wp_strip_all_tags($excerpt);
        }
        $content = get_the_content();
        return wp_trim_words(wp_strip_all_tags($content), 30, '…');
    }

    if (is_front_page()) {
        $home_desc = (string) get_field('seo_description', lb3_get_fr_front_page_id());
        if ($home_desc !== '') {
            return $home_desc;
        }
    }

    return (string) get_bloginfo('description');
}

/**
 * Image principale pour OG/Twitter.
 */
function lb3_seo_image(): string
{
    if (is_singular('post')) {
        $cover = lb3_film_cover_url(null, 'full');
        if ($cover !== '') {
            return $cover;
        }
    }

    if (is_front_page()) {
        $cover = (string) get_field('cover', lb3_get_fr_front_page_id());
        if ($cover) {
            return $cover;
        }
    }

    // Fallback global : og_image_default déclaré sur la home FR.
    $default = (string) get_field('og_image_default', lb3_get_fr_front_page_id());
    if ($default) {
        return $default;
    }

    return '';
}

/**
 * Parse un champ « Durée » libre ("90 min", "1h30", "2h"…) vers ISO 8601 PT.
 * Retourne null si aucune forme reconnue — les consommateurs omettent alors le champ.
 */
function lb3_parse_duration_iso8601(string $duree): ?string
{
    $s = trim(mb_strtolower($duree));
    if ($s === '') {
        return null;
    }

    // "1h30", "1 h 30 min", "1h30min"
    if (preg_match('#(\d+)\s*h(?:eure?s?)?\s*(\d+)\s*(?:min(?:ute?s?)?|m)?#iu', $s, $m)) {
        return 'PT' . (int) $m[1] . 'H' . (int) $m[2] . 'M';
    }
    // "2h", "2 heures"
    if (preg_match('#(\d+)\s*h(?:eure?s?)?\b#iu', $s, $m)) {
        return 'PT' . (int) $m[1] . 'H';
    }
    // "90 min", "90m", "52 minutes"
    if (preg_match('#(\d+)\s*(?:min(?:ute?s?)?|m)\b#iu', $s, $m)) {
        return 'PT' . (int) $m[1] . 'M';
    }
    // Simple entier → minutes.
    if (preg_match('#^(\d+)$#', $s, $m)) {
        return 'PT' . (int) $m[1] . 'M';
    }
    return null;
}

/**
 * À partir d'un bloc <iframe>, dérive les URLs canoniques {embedUrl, contentUrl}
 * pour schema.org/VideoObject. Retourne null si la vidéo n'est pas reconnue.
 *
 * Utilise lb3_parse_video_iframe() défini dans inc/video-facade.php.
 *
 * @return array{embedUrl:string,contentUrl:string}|null
 */
function lb3_video_urls_from_iframe(string $iframe_html): ?array
{
    if (!function_exists('lb3_parse_video_iframe')) {
        return null;
    }
    $meta = lb3_parse_video_iframe($iframe_html);
    if (!$meta) {
        return null;
    }
    if ($meta['platform'] === 'youtube') {
        return [
            'embedUrl'   => 'https://www.youtube-nocookie.com/embed/' . $meta['id'],
            'contentUrl' => 'https://www.youtube.com/watch?v=' . $meta['id'],
        ];
    }
    if ($meta['platform'] === 'vimeo') {
        return [
            'embedUrl'   => 'https://player.vimeo.com/video/' . $meta['id'],
            'contentUrl' => 'https://vimeo.com/' . $meta['id'],
        ];
    }
    return null;
}

/**
 * Retourne la liste d'items du fil d'Ariane pour la page courante.
 * Chaque item : ['label' => string, 'url' => string]. Le dernier item est la page courante.
 * Retourne [] sur la home ou les contextes sans breadcrumb (archives auto).
 *
 * @return array<int, array{label:string, url:string}>
 */
function lb3_get_breadcrumbs(): array
{
    if (is_front_page() || is_home()) {
        return [];
    }

    $home_label = __('Accueil', 'lb3');
    $home_url   = lb3_home_url_localized();

    if (is_singular('post')) {
        return [
            ['label' => $home_label, 'url' => $home_url],
            ['label' => __('Films', 'lb3'), 'url' => $home_url . '#films'],
            ['label' => (string) get_the_title(), 'url' => (string) get_permalink()],
        ];
    }

    if (is_singular('page')) {
        return [
            ['label' => $home_label, 'url' => $home_url],
            ['label' => (string) get_the_title(), 'url' => (string) get_permalink()],
        ];
    }

    return [];
}

/**
 * Canonical URL.
 */
function lb3_seo_canonical(): string
{
    if (is_singular()) {
        return (string) get_permalink();
    }
    if (is_front_page()) {
        return (string) lb3_home_url_localized();
    }
    global $wp;
    return home_url(add_query_arg([], $wp->request));
}

/**
 * Injecte les balises SEO dans <head>.
 */
add_action('wp_head', static function (): void {
    $desc      = lb3_seo_description();
    $image     = lb3_seo_image();
    $canonical = lb3_seo_canonical();
    $title     = wp_get_document_title();
    $site_name = get_bloginfo('name');
    $locale    = get_locale();

    echo "\n<!-- lb3 SEO -->\n";

    if ($canonical) {
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }

    if ($desc) {
        echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
    }

    // Open Graph
    echo '<meta property="og:type" content="' . (is_singular('post') ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    if ($desc) {
        echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
    }
    echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($locale) . '">' . "\n";
    if ($image) {
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        echo '<meta property="og:image:width" content="1200">' . "\n";
        echo '<meta property="og:image:height" content="630">' . "\n";
    }

    // Twitter
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    if ($desc) {
        echo '<meta name="twitter:description" content="' . esc_attr($desc) . '">' . "\n";
    }
    if ($image) {
        echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
    }

    // hreflang alternates via Polylang.
    // Sur singular : on consulte les vraies traductions du post pour ne PAS émettre
    // d'alternate vers la home d'une langue où le post n'existe pas (Google considère
    // un alternate inexistant comme une erreur, et ça gonfle "Alternate with proper
    // canonical" dans GSC).
    $lang_map = ['fr' => 'fr-FR', 'en' => 'en-US'];

    if (is_singular() && function_exists('pll_get_post_translations')) {
        $post_id      = get_queried_object_id();
        $translations = pll_get_post_translations($post_id);
        if (empty($translations) && function_exists('pll_get_post_language')) {
            $current_lang = pll_get_post_language($post_id);
            if ($current_lang) {
                $translations = [$current_lang => $post_id];
            }
        }
        foreach ($translations as $slug => $translated_id) {
            if (!isset($lang_map[$slug])) {
                continue;
            }
            $url = get_permalink($translated_id);
            if (!$url) {
                continue;
            }
            echo '<link rel="alternate" hreflang="' . esc_attr($lang_map[$slug]) . '" href="' . esc_url($url) . '">' . "\n";
        }
        if (function_exists('pll_home_url')) {
            echo '<link rel="alternate" hreflang="x-default" href="' . esc_url(pll_home_url('fr')) . '">' . "\n";
        }
    } elseif (function_exists('pll_the_languages')) {
        $langs = pll_the_languages(['raw' => 1]);
        if (is_array($langs)) {
            foreach ($langs as $lang) {
                if (!empty($lang['url']) && !empty($lang['locale'])) {
                    $hreflang = str_replace('_', '-', $lang['locale']);
                    echo '<link rel="alternate" hreflang="' . esc_attr($hreflang) . '" href="' . esc_url($lang['url']) . '">' . "\n";
                }
            }
            if (function_exists('pll_home_url')) {
                echo '<link rel="alternate" hreflang="x-default" href="' . esc_url(pll_home_url('fr')) . '">' . "\n";
            }
        }
    }

    echo "<!-- /lb3 SEO -->\n";
}, 2);

/**
 * noindex,nofollow sur les archives auto-générées par WP (auteur, catégorie, tag,
 * date, recherche). Double sécurité par-dessus le 301 d'inc/security.php sur /author/.
 * Les pages utiles (home, singles, pages légales) restent indexables.
 */
add_action('wp_head', static function (): void {
    if (is_admin()) {
        return;
    }
    if (is_author() || is_category() || is_tag() || is_date() || is_search()) {
        echo '<meta name="robots" content="noindex,nofollow">' . "\n";
    }
}, 1);

/**
 * robots.txt custom : bloque l'admin et les URLs paramétrées, expose le sitemap natif WP.
 */
add_filter('robots_txt', static function (string $output, bool $public): string {
    // Respect de l'option "décourager les moteurs de recherche" (Réglages > Lecture).
    if (!$public) {
        return $output;
    }

    $lines = [
        'User-agent: *',
        'Disallow: /wp-admin/',
        'Allow: /wp-admin/admin-ajax.php',
        'Disallow: /?s=',
        'Disallow: /*?*',
        '',
        'Sitemap: ' . home_url('/wp-sitemap.xml'),
    ];

    return implode("\n", $lines) . "\n";
}, 10, 2);

/**
 * JSON-LD structured data.
 */
add_action('wp_head', static function (): void {
    // BreadcrumbList — présent dès qu'un fil d'Ariane est disponible.
    $crumbs = lb3_get_breadcrumbs();
    if (!empty($crumbs)) {
        $items = [];
        foreach ($crumbs as $i => $crumb) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['label'],
                'item'     => $crumb['url'],
            ];
        }
        $breadcrumb = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
        echo '<script type="application/ld+json">' . wp_json_encode(
            $breadcrumb,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . '</script>' . "\n";
    }

    if (is_front_page()) {
        // sameAs : liste curée des profils canoniques de Lucie Baudinaud. Critique
        // pour le knowledge panel Google (confirme que toutes ces pages parlent
        // de la même entité). Mettre à jour si un nouveau profil officiel apparaît.
        $person = [
            '@context'   => 'https://schema.org',
            '@type'      => 'Person',
            'name'       => 'Lucie Baudinaud',
            'url'        => home_url('/'),
            'jobTitle'   => __('Directrice de la photographie', 'lb3'),
            'knowsAbout' => ['Cinematography', 'Director of Photography', 'Film', 'Documentary'],
            'alumniOf'   => [
                '@type' => 'EducationalOrganization',
                'name'  => "La Fémis - École nationale supérieure des métiers de l'image et du son",
                'url'   => 'https://www.femis.fr',
            ],
            'memberOf'   => [
                '@type' => 'Organization',
                'name'  => 'AFC - Association Française des directeurs de la photographie Cinématographique',
                'url'   => 'https://www.afcinema.com',
            ],
            'sameAs'     => [
                'https://www.imdb.com/fr/name/nm3044276/',
                'https://www.afcinema.com/Baudinaud-Lucie.html',
                'https://www.allocine.fr/personne/fichepersonne_gen_cpersonne=764843.html',
                'https://www.unifrance.org/annuaires/personne/392232/lucie-baudinaud',
                'https://www.femis.fr/index.php?page=fiche_ancien&id_ancien=18399',
                'https://www.femmesalacamera.com/membres-collectif/baudinaud-lucie',
                'https://www.film-documentaire.fr/4DACTION/w_liste_generique/C_66501_F',
                'https://mubi.com/fr/cast/lucie-baudinaud',
                'https://vimeo.com/user20159589',
                'https://www.instagram.com/luciebaudinaud/',
            ],
        ];

        echo '<script type="application/ld+json">' . wp_json_encode(
            $person,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . '</script>' . "\n";
        return;
    }

    if (is_singular('post')) {
        $cover        = lb3_film_cover_url(null, 'full');
        $duree_raw    = (string) get_field('duree');
        $duration_iso = $duree_raw !== '' ? lb3_parse_duration_iso8601($duree_raw) : null;
        $video_urls   = function_exists('lb3_film_video_embed_urls') ? lb3_film_video_embed_urls() : null;

        // Dates JSON-LD : format ISO 8601 `'c'` (locale-independent par construction).
        // NE PAS utiliser wp_date() ici — les crawlers veulent du machine-readable strict.
        $creative = [
            '@context'      => 'https://schema.org',
            '@type'         => 'CreativeWork',
            'name'          => get_the_title(),
            'url'           => (string) get_permalink(),
            'datePublished' => get_the_date('c'),
            'author'        => [
                '@type' => 'Person',
                'name'  => (string) get_field('realisateur'),
            ],
            'creator'       => [
                '@type'    => 'Person',
                'name'     => 'Lucie Baudinaud',
                'jobTitle' => __('Directrice de la photographie', 'lb3'),
            ],
        ];

        if ($cover !== '') {
            $creative['image'] = $cover;
        }
        if ($duration_iso !== null) {
            $creative['duration'] = $duration_iso;
        }

        echo '<script type="application/ld+json">' . wp_json_encode(
            $creative,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . '</script>' . "\n";

        // VideoObject enrichi si une source vidéo est présente (nouveau champ
        // video_url ou legacy iframe).
        if ($video_urls !== null) {
            $video = [
                '@context'   => 'https://schema.org',
                '@type'      => 'VideoObject',
                'name'       => get_the_title(),
                'uploadDate' => get_the_date('c'),
                'embedUrl'   => $video_urls['embedUrl'],
                'contentUrl' => $video_urls['contentUrl'],
            ];

            $desc = lb3_seo_description();
            if ($desc !== '') {
                $video['description'] = $desc;
            }
            if ($cover !== '') {
                $video['thumbnailUrl'] = $cover;
            }
            if ($duration_iso !== null) {
                $video['duration'] = $duration_iso;
            }

            echo '<script type="application/ld+json">' . wp_json_encode(
                $video,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ) . '</script>' . "\n";
        }
    }
}, 3);
