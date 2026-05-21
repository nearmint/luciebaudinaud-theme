<?php
/**
 * Cache wrapper pour `get_field()` ACF.
 *
 * Pourquoi : `get_field()` fait une query meta à chaque appel. Dans les boucles
 * (grille films : `realisateur`, `duree`, `annee`, `featured` × N films),
 * ça s'accumule. Avec un backend d'object cache persistant (Redis/Memcached),
 * on déplace ces lectures hors DB.
 *
 * Gain réel uniquement si `wp_cache_*` persiste entre requêtes (OVH propose
 * Redis sur ses offres managed). En l'absence, le cache reste non-persistant
 * mais déduplique les appels au sein d'une même requête (utile en loop).
 *
 * TTL : HOUR_IN_SECONDS (fallback si invalidation manquée).
 * Invalidation : hooks acf/save_post, save_post, updated_post_meta, deleted_post_meta.
 *
 * Usage : remplacer `get_field('key', $id)` par `lb3_get_field('key', $id)`.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const LB3_ACF_CACHE_GROUP = 'lb3_acf';

/**
 * Récupère un champ ACF via l'object cache.
 *
 * @param string   $key      Nom du champ ACF.
 * @param int|null $post_id  ID du post (null → post courant).
 * @return mixed
 */
function lb3_get_field(string $key, ?int $post_id = null)
{
    if (!function_exists('get_field')) {
        return null;
    }

    $resolved_id = $post_id ?? (int) get_the_ID();
    if ($resolved_id <= 0) {
        return get_field($key, false);
    }

    $cache_key = $resolved_id . ':' . $key;
    $found     = false;
    $value     = wp_cache_get($cache_key, LB3_ACF_CACHE_GROUP, false, $found);

    if ($found) {
        return $value;
    }

    $value = get_field($key, $resolved_id);
    wp_cache_set($cache_key, $value, LB3_ACF_CACHE_GROUP, HOUR_IN_SECONDS);

    return $value;
}

/**
 * Invalide toutes les entrées cache associées à un post.
 * wp_cache_delete n'opère pas en wildcard → on incrémente une version de groupe,
 * mais `wp_cache_flush_group()` n'est dispo que WP 6.1+ avec certains backends.
 *
 * Stratégie simple et portable : on force un re-fetch en bumpant la clé
 * via delete ciblé sur les champs connus du post. Comme on ne connaît pas
 * la liste exhaustive, on invalide par champ au save.
 */
function lb3_invalidate_acf_cache_for_post(int $post_id, ?string $meta_key = null): void
{
    if ($post_id <= 0) {
        return;
    }

    // Si on a la clé meta ACF modifiée → delete ciblé (pattern : `_key` ou `key`).
    if ($meta_key !== null && $meta_key !== '') {
        // ACF stocke le champ sous `key` et sa ref sous `_key`.
        $clean = ltrim($meta_key, '_');
        wp_cache_delete($post_id . ':' . $clean, LB3_ACF_CACHE_GROUP);
        return;
    }

    // Pas de clé précise : invalide la liste connue des champs lb3 sur ce post.
    $known_fields = [
        'cover', 'iframe', 'realisateur', 'duree', 'annee', 'gallery',
        'featured', 'selections', 'transcription',
        'camera', 'lenses', 'format', 'pellicule_vs_numerique',
        'aspect_ratio', 'resolution', 'etalonnage',
        'seo_description', 'og_image_default', 'cv_pdf', 'galerie',
        'photo_cover_photogrammes', 'photo_cover_cv', 'photo_cover_contact',
        'texte_cv', 'texte_bio',
        'social_instagram', 'social_imdb', 'social_vimeo',
        'legal_content',
    ];
    foreach ($known_fields as $field) {
        wp_cache_delete($post_id . ':' . $field, LB3_ACF_CACHE_GROUP);
    }
}

// Invalidation au save (ACF passe par `acf/save_post`, d'autres plugins par save_post).
add_action('acf/save_post', static function ($post_id): void {
    if (is_numeric($post_id)) {
        lb3_invalidate_acf_cache_for_post((int) $post_id);
    }
}, 20);

add_action('save_post', static function (int $post_id): void {
    lb3_invalidate_acf_cache_for_post($post_id);
}, 20);

add_action('deleted_post_meta', static function ($meta_ids, int $post_id, string $meta_key): void {
    lb3_invalidate_acf_cache_for_post($post_id, $meta_key);
}, 20, 3);

add_action('updated_post_meta', static function ($meta_id, int $post_id, string $meta_key): void {
    lb3_invalidate_acf_cache_for_post($post_id, $meta_key);
}, 20, 3);
