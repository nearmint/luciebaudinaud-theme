<?php
/**
 * Admin — vérifie que les images d'un film ont un texte alternatif.
 *
 * Images contrôlées :
 *   - featured image (post thumbnail)
 *   - ACF `cover` (cover hero du single)
 *   - ACF `gallery` (galerie photos du film)
 *
 * Stocke la liste des images concernées dans la post meta `_lb3_alt_warnings`
 * et l'affiche comme `notice-warning` sur l'écran d'édition du film.
 * Ne bloque jamais la publication.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const LB3_ALT_WARNINGS_META = '_lb3_alt_warnings';

/**
 * À partir d'une valeur ACF image (URL, ID, array), retourne l'ID attachment ou 0.
 */
function lb3_alt_extract_attachment_id($value): int
{
    if (is_numeric($value)) {
        return (int) $value;
    }
    if (is_array($value)) {
        if (!empty($value['ID'])) {
            return (int) $value['ID'];
        }
        if (!empty($value['url'])) {
            return (int) attachment_url_to_postid((string) $value['url']);
        }
    }
    if (is_object($value) && !empty($value->ID)) {
        return (int) $value->ID;
    }
    if (is_string($value) && $value !== '') {
        return (int) attachment_url_to_postid($value);
    }
    return 0;
}

/**
 * True si l'attachment n'a pas de texte alternatif.
 */
function lb3_alt_missing(int $attachment_id): bool
{
    if ($attachment_id <= 0) {
        return false;
    }
    $alt = (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    return trim($alt) === '';
}

/**
 * À l'enregistrement d'un film, collecte les images sans alt et stocke le résumé.
 */
add_action('save_post_post', static function (int $post_id, \WP_Post $post, bool $update): void {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if ($post->post_status === 'auto-draft' || $post->post_status === 'trash') {
        return;
    }

    $missing = [];

    // Featured image.
    $thumb_id = (int) get_post_thumbnail_id($post_id);
    if ($thumb_id && lb3_alt_missing($thumb_id)) {
        $missing[] = __('Image à la une', 'lb3');
    }

    // ACF cover (si la fonction ACF existe sur ce context — save_post s'exécute en admin).
    if (function_exists('get_field')) {
        $cover_id = lb3_alt_extract_attachment_id(get_field('cover', $post_id));
        if ($cover_id && lb3_alt_missing($cover_id)) {
            $missing[] = __('Cover (hero single)', 'lb3');
        }

        $gallery = get_field('gallery', $post_id);
        if (is_array($gallery)) {
            foreach ($gallery as $i => $item) {
                $gid = lb3_alt_extract_attachment_id($item);
                if ($gid && lb3_alt_missing($gid)) {
                    /* translators: %d : position de l'image dans la galerie (1-based). */
                    $missing[] = sprintf(__('Galerie photo #%d', 'lb3'), $i + 1);
                }
            }
        }
    }

    if (!empty($missing)) {
        update_post_meta($post_id, LB3_ALT_WARNINGS_META, $missing);
    } else {
        delete_post_meta($post_id, LB3_ALT_WARNINGS_META);
    }
}, 20, 3);

/**
 * Affiche un warning sur l'écran d'édition si des alt manquent.
 */
add_action('admin_notices', static function (): void {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'post') {
        return;
    }

    global $post;
    if (!$post instanceof \WP_Post) {
        return;
    }

    $warnings = get_post_meta($post->ID, LB3_ALT_WARNINGS_META, true);
    if (!is_array($warnings) || empty($warnings)) {
        return;
    }
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php esc_html_e('Accessibilité / SEO — texte alternatif manquant', 'lb3'); ?></strong>
        </p>
        <ul style="list-style:disc;margin-left:24px;">
            <?php foreach ($warnings as $w) : ?>
                <li><?php echo esc_html((string) $w); ?></li>
            <?php endforeach; ?>
        </ul>
        <p>
            <?php esc_html_e('Complète le texte alternatif de chaque image depuis la médiathèque. Il sera lu par les lecteurs d\'écran et indexé par les moteurs de recherche.', 'lb3'); ?>
        </p>
    </div>
    <?php
});
