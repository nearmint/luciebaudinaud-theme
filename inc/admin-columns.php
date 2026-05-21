<?php
/**
 * Colonnes custom sur la liste des films (post type `post`) en admin.
 *
 * Affiche Réalisateur / Année / Durée / Catégorie à la place des tags, rend
 * Année et Catégorie triables. Permet à Lucie de scanner rapidement la filmo.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Redéfinit la liste et l'ordre des colonnes.
 *
 * @param array<string, string> $columns
 * @return array<string, string>
 */
add_filter('manage_posts_columns', static function (array $columns): array {
    unset($columns['tags']);

    $ordered = [];
    foreach ($columns as $key => $label) {
        if ($key === 'title') {
            // ⭐ avant le titre pour scanner rapidement la filmo.
            $ordered['lb3_featured'] = '<span title="' . esc_attr__('Film mis en avant', 'lb3') . '" aria-hidden="true">⭐</span>';
            $ordered[$key]           = $label;
            $ordered['lb3_realisateur'] = __('Réalisation', 'lb3');
            $ordered['lb3_annee']       = __('Année', 'lb3');
            $ordered['lb3_duree']       = __('Durée', 'lb3');
            continue;
        }
        $ordered[$key] = $label;
    }
    return $ordered;
});

/**
 * Remplit les colonnes custom.
 */
add_action('manage_posts_custom_column', static function (string $column, int $post_id): void {
    switch ($column) {
        case 'lb3_featured':
            $featured = (bool) get_field('featured', $post_id);
            echo $featured
                ? '<span title="' . esc_attr__('Mis en avant', 'lb3') . '">⭐</span>'
                : '<span style="color:#ccc">—</span>';
            break;
        case 'lb3_realisateur':
            $v = (string) get_field('realisateur', $post_id);
            echo $v !== '' ? esc_html($v) : '<span style="color:#999">—</span>';
            break;
        case 'lb3_annee':
            $v = (string) get_field('annee', $post_id);
            echo $v !== '' ? esc_html($v) : '<span style="color:#999">—</span>';
            break;
        case 'lb3_duree':
            $v = (string) get_field('duree', $post_id);
            echo $v !== '' ? esc_html($v) : '<span style="color:#999">—</span>';
            break;
    }
}, 10, 2);

/**
 * Rend triable : Année (par meta ACF), Catégorie (la colonne native `categories`).
 */
add_filter('manage_edit-post_sortable_columns', static function (array $columns): array {
    $columns['lb3_featured'] = 'lb3_featured';
    $columns['lb3_annee']    = 'lb3_annee';
    $columns['categories']   = 'categories';
    return $columns;
});

/**
 * Applique le tri par meta `annee` quand la colonne est sélectionnée.
 */
add_action('pre_get_posts', static function (\WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    if ($query->get('orderby') === 'lb3_annee') {
        $query->set('meta_key', 'annee');
        $query->set('orderby', 'meta_value');
    }
    if ($query->get('orderby') === 'lb3_featured') {
        $query->set('meta_key', 'featured');
        $query->set('orderby', 'meta_value_num');
    }
});
