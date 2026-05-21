<?php
/**
 * Helpers réutilisables dans les templates.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Retourne tous les films publiés (posts).
 *
 * `lang => ''` : Polylang retourne tous les films, peu importe la langue.
 * C'est voulu : la home affiche la filmo complète de Lucie, pas une version par langue.
 * Les métadonnées affichées (titre, réalisateur) sont traduites via le post lui-même.
 */
function lb3_get_all_films(): \WP_Query
{
    // Ordre : date de publication descendante. Lucie ajuste la date via Quick
    // Edit pour repositionner un film dans la grille.
    return new \WP_Query([
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'lang'                   => '',
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
    ]);
}

/**
 * Retourne les catégories qui ont au moins un film publié, dans l'ordre
 * éditorial voulu par Lucie (LONG · DOCU · SÉRIE · COURT · CLIP).
 *
 * La catégorie par défaut WP (« Non classé » / « Uncategorized ») est exclue :
 * elle reflète un oubli de tag côté admin, pas une facette éditoriale.
 *
 * « Fiction » est conservée en back-office (films archivés potentiels) mais
 * masquée des filtres front — décision cliente, pas un bug.
 *
 * Les slugs non listés dans $order passent en fin de liste (ordre alpha).
 *
 * @return array<int, \WP_Term>
 */
function lb3_get_film_categories(): array
{
    $cats = get_categories([
        'taxonomy'   => 'category',
        'hide_empty' => true,
        'exclude'    => [(int) get_option('default_category')],
    ]);

    if (!is_array($cats) || empty($cats)) {
        return [];
    }

    $hidden_slugs = ['fiction'];
    $cats = array_values(array_filter($cats, static function ($cat) use ($hidden_slugs) {
        return !in_array($cat->slug, $hidden_slugs, true);
    }));

    // Ordre fixe par slug. `clip-2` = « Clip », `clip` = « Clip-Art » (slugs historiques).
    $order = ['long-metrage', 'docu', 'serie', 'court-metrage', 'clip-2', 'clip'];

    usort($cats, static function ($a, $b) use ($order) {
        $pos_a = array_search($a->slug, $order, true);
        $pos_b = array_search($b->slug, $order, true);
        if ($pos_a === false) {
            $pos_a = PHP_INT_MAX;
        }
        if ($pos_b === false) {
            $pos_b = PHP_INT_MAX;
        }
        if ($pos_a === $pos_b) {
            return strcmp($a->name, $b->name);
        }
        return $pos_a <=> $pos_b;
    });

    return $cats;
}

/**
 * Classes CSS pour un film (pour le filtrage JS).
 * On utilise le slug au lieu de l'ID (plus résilient aux changements d'admin).
 */
function lb3_film_filter_classes(?int $post_id = null): string
{
    $post_id = $post_id ?: (int) get_the_ID();
    $cats    = get_the_category($post_id);
    $classes = [];

    foreach ($cats as $cat) {
        $classes[] = 'cat-' . $cat->slug;
    }

    return implode(' ', $classes);
}

/**
 * Slugs de catégorie d'un film, en liste séparée par espaces (pour data-cats).
 */
function lb3_film_category_slugs(?int $post_id = null): string
{
    $post_id = $post_id ?: (int) get_the_ID();
    $cats    = get_the_category($post_id);
    $slugs   = array_map(static fn($c) => $c->slug, $cats);
    return implode(' ', $slugs);
}

/**
 * Retourne une URL d'image ACF normalisée (accepte array, objet, ID, URL string).
 *
 * @param mixed $value
 */
function lb3_acf_image_url($value, string $size = 'full'): string
{
    if (is_string($value)) {
        return $value;
    }
    if (is_array($value)) {
        if (isset($value['sizes'][$size])) {
            return (string) $value['sizes'][$size];
        }
        if (isset($value['url'])) {
            return (string) $value['url'];
        }
        if (isset($value['ID'])) {
            return (string) wp_get_attachment_image_url((int) $value['ID'], $size);
        }
    }
    if (is_numeric($value)) {
        return (string) wp_get_attachment_image_url((int) $value, $size);
    }
    if (is_object($value) && isset($value->ID)) {
        return (string) wp_get_attachment_image_url((int) $value->ID, $size);
    }
    return '';
}

/**
 * Alt text d'une image ACF normalisée.
 *
 * @param mixed $value
 */
function lb3_acf_image_alt($value, string $fallback = ''): string
{
    if (is_array($value) && !empty($value['alt'])) {
        return (string) $value['alt'];
    }
    if (is_numeric($value)) {
        $alt = (string) get_post_meta((int) $value, '_wp_attachment_image_alt', true);
        if ($alt !== '') {
            return $alt;
        }
    }
    return $fallback;
}

/**
 * Normalise une galerie ACF (mix possible d'IDs, arrays, objets) en liste d'arrays.
 *
 * @param mixed $gallery
 * @return array<int, array{id:int,url:string,alt:string,title:string,thumb:string,width:int,height:int}>
 */
function lb3_normalize_gallery($gallery, string $full_size = 'lb3-photo-full', string $thumb_size = 'lb3-photo-thumb'): array
{
    if (empty($gallery) || !is_array($gallery)) {
        return [];
    }

    $out = [];

    foreach ($gallery as $item) {
        $id = 0;
        if (is_numeric($item)) {
            $id = (int) $item;
        } elseif (is_array($item) && !empty($item['ID'])) {
            $id = (int) $item['ID'];
        } elseif (is_object($item) && !empty($item->ID)) {
            $id = (int) $item->ID;
        }

        if (!$id) {
            continue;
        }

        $full  = wp_get_attachment_image_src($id, $full_size);
        $thumb = wp_get_attachment_image_src($id, $thumb_size);

        if (!$full) {
            continue;
        }

        $out[] = [
            'id'     => $id,
            'url'    => (string) $full[0],
            'width'  => (int) $full[1],
            'height' => (int) $full[2],
            'thumb'  => $thumb ? (string) $thumb[0] : (string) $full[0],
            'alt'    => (string) get_post_meta($id, '_wp_attachment_image_alt', true),
            'title'  => (string) get_the_title($id),
        ];
    }

    return $out;
}

/**
 * URL de l'image de couverture d'un film (single post).
 *
 * Hiérarchie :
 *  1. Champ ACF `cover` (rempli via la fiche film)
 *  2. Fallback : featured image WP du post
 *  3. Chaîne vide si aucune source
 *
 * Utilisé à la fois côté rendu (single.php header) et côté SEO (OG image,
 * JSON-LD VideoObject). Point d'entrée unique → cohérence garantie entre
 * l'image visible et l'image partagée sur les réseaux sociaux.
 */
function lb3_film_cover_url(?int $post_id = null, string $size = 'lb3-film-cover'): string
{
    $post_id = $post_id ?: (int) get_the_ID();
    if ($post_id <= 0) {
        return '';
    }

    $url = lb3_acf_image_url(get_field('cover', $post_id), $size);
    if ($url !== '') {
        return $url;
    }

    if (has_post_thumbnail($post_id)) {
        $thumb = wp_get_attachment_image_url((int) get_post_thumbnail_id($post_id), $size);
        if ($thumb) {
            return (string) $thumb;
        }
    }

    return '';
}

/**
 * Détecte plateforme + ID vidéo à partir d'une URL Vimeo ou YouTube.
 *
 * Accepte les formats courants :
 *  - https://vimeo.com/{id}
 *  - https://player.vimeo.com/video/{id}
 *  - https://www.youtube.com/watch?v={id}
 *  - https://youtu.be/{id}
 *  - https://www.youtube.com/embed/{id}
 *  - https://www.youtube-nocookie.com/embed/{id}
 *
 * @return array{platform:string,id:string}|null
 */
function lb3_parse_video_url(string $url): ?array
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    if (preg_match('#vimeo\.com/(?:video/)?(\d+)#i', $url, $m)) {
        return ['platform' => 'vimeo', 'id' => $m[1]];
    }
    if (preg_match('#(?:youtube\.com/watch\?(?:.*&)?v=|youtu\.be/|youtube(?:-nocookie)?\.com/embed/)([A-Za-z0-9_-]{11})#i', $url, $m)) {
        return ['platform' => 'youtube', 'id' => $m[1]];
    }
    return null;
}

/**
 * Construit un embed iframe safe à partir d'une URL Vimeo ou YouTube.
 *
 * Les paramètres (dnt, rel=0, lazy, referrer-policy) sont imposés côté serveur :
 * Lucie ne colle qu'une URL, impossible d'injecter du HTML malveillant ou de
 * corrompre les attributs au copier-coller.
 *
 * @param string $url   URL source (Vimeo ou YouTube).
 * @param string $title Titre du film pour l'attribut title="…" (a11y).
 * @return string|null  HTML de l'iframe ou null si URL non reconnue.
 */
function lb3_build_video_iframe(string $url, string $title = ''): ?string
{
    $meta = lb3_parse_video_url($url);
    if ($meta === null) {
        return null;
    }

    $embed_url = $meta['platform'] === 'vimeo'
        ? 'https://player.vimeo.com/video/' . $meta['id'] . '?dnt=1'
        : 'https://www.youtube-nocookie.com/embed/' . $meta['id'] . '?rel=0';

    $safe_title = $title !== ''
        /* translators: %s : titre du film. */
        ? sprintf(__('Bande-annonce de %s', 'lb3'), $title)
        : __('Lecteur vidéo', 'lb3');

    return sprintf(
        '<iframe src="%s" title="%s" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>',
        esc_url($embed_url),
        esc_attr($safe_title)
    );
}

/**
 * Pour un film donné, retourne l'HTML de l'iframe ou null.
 *
 * Priorité :
 *  1. Nouveau champ `video_url` (URL pure → iframe sûre générée côté PHP)
 *  2. Ancien champ `iframe` (HTML legacy stocké en DB — compat arrière)
 *
 * Point d'entrée unique pour tous les consommateurs (film-card, single, modale).
 */
function lb3_film_iframe_html(?int $post_id = null): ?string
{
    $post_id = $post_id ?: (int) get_the_ID();

    $video_url = (string) get_field('video_url', $post_id);
    if ($video_url !== '') {
        $html = lb3_build_video_iframe($video_url, (string) get_the_title($post_id));
        if ($html !== null) {
            return $html;
        }
    }

    $legacy = get_field('iframe', $post_id);
    if ($legacy) {
        return (string) $legacy;
    }

    return null;
}

/**
 * URL d'embed canonique d'un film (pour schema.org/VideoObject).
 *
 * Renvoie null si aucune source (nouveau champ ni legacy) n'est reconnue.
 *
 * @return array{embedUrl:string,contentUrl:string}|null
 */
function lb3_film_video_embed_urls(?int $post_id = null): ?array
{
    $post_id = $post_id ?: (int) get_the_ID();

    // 1. Nouveau champ : parse direct.
    $video_url = (string) get_field('video_url', $post_id);
    if ($video_url !== '') {
        $meta = lb3_parse_video_url($video_url);
        if ($meta !== null) {
            return $meta['platform'] === 'vimeo'
                ? [
                    'embedUrl'   => 'https://player.vimeo.com/video/' . $meta['id'],
                    'contentUrl' => 'https://vimeo.com/' . $meta['id'],
                ]
                : [
                    'embedUrl'   => 'https://www.youtube-nocookie.com/embed/' . $meta['id'],
                    'contentUrl' => 'https://www.youtube.com/watch?v=' . $meta['id'],
                ];
        }
    }

    // 2. Legacy : parse via extraction du src de l'iframe.
    $legacy = (string) get_field('iframe', $post_id);
    if ($legacy !== '' && function_exists('lb3_video_urls_from_iframe')) {
        return lb3_video_urls_from_iframe($legacy);
    }

    return null;
}

/**
 * Le film a-t-il une source vidéo (nouvelle ou legacy) ?
 * Utilisé pour gater preconnect/preload côté assets.
 */
function lb3_film_has_video(?int $post_id = null): bool
{
    $post_id = $post_id ?: (int) get_the_ID();
    if ((string) get_field('video_url', $post_id) !== '') {
        return true;
    }
    return (string) get_field('iframe', $post_id) !== '';
}
