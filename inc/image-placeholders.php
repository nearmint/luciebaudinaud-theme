<?php
/**
 * LQIP (Low-Quality Image Placeholder).
 *
 * À l'upload d'une image, on génère une mini version (20 px wide) encodée
 * en base64 et stockée dans la meta `_lb3_lqip` de l'attachment. Rendu inline
 * dans le <style background-image> du conteneur, l'image finale est affichée
 * en fade-in au load.
 *
 * Coût : ~400-700 octets par attachment. Zéro requête réseau supplémentaire.
 *
 * Fallback : si la meta est absente au moment du rendu (images uploadées avant
 * l'activation du module), `lb3_get_lqip()` génère à la volée et mémorise.
 *
 * Régénération massive : `wp media regenerate` rejoue le filtre
 * wp_generate_attachment_metadata sur tous les attachments.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const LB3_LQIP_META_KEY = '_lb3_lqip';
const LB3_LQIP_WIDTH    = 20;

/**
 * À l'upload (ou régénération), calcule et stocke le LQIP.
 */
add_filter('wp_generate_attachment_metadata', static function (array $metadata, int $attachment_id): array {
    lb3_generate_lqip_for_attachment($attachment_id);
    return $metadata;
}, 20, 2);

/**
 * Génère un data-URL base64 JPEG très compact et le stocke en meta.
 * Retourne le data-URL (ou '' en cas d'échec).
 */
function lb3_generate_lqip_for_attachment(int $attachment_id): string
{
    if (!wp_attachment_is_image($attachment_id)) {
        return '';
    }

    $file = get_attached_file($attachment_id);
    if (!$file || !is_readable($file)) {
        return '';
    }

    $editor = wp_get_image_editor($file);
    if (is_wp_error($editor)) {
        return '';
    }

    $size = $editor->get_size();
    if (!$size || empty($size['width']) || empty($size['height'])) {
        return '';
    }

    $width    = max(1, (int) $size['width']);
    $height   = max(1, (int) $size['height']);
    $target_h = max(1, (int) round(LB3_LQIP_WIDTH * ($height / $width)));

    $resized = $editor->resize(LB3_LQIP_WIDTH, $target_h, false);
    if (is_wp_error($resized)) {
        return '';
    }

    // Qualité faible : la largeur de 20 px rend le bruit invisible après blur CSS.
    $editor->set_quality(40);

    $tmp   = wp_tempnam('lb3-lqip-' . $attachment_id . '.jpg');
    $saved = $editor->save($tmp, 'image/jpeg');
    if (is_wp_error($saved) || empty($saved['path']) || !is_readable($saved['path'])) {
        if ($tmp && file_exists($tmp)) {
            @unlink($tmp);
        }
        return '';
    }

    $bytes = (string) @file_get_contents($saved['path']);
    @unlink($saved['path']);
    if ($tmp && $tmp !== $saved['path'] && file_exists($tmp)) {
        @unlink($tmp);
    }
    if ($bytes === '') {
        return '';
    }

    $data_url = 'data:image/jpeg;base64,' . base64_encode($bytes);
    update_post_meta($attachment_id, LB3_LQIP_META_KEY, $data_url);

    return $data_url;
}

/**
 * Récupère le LQIP d'un attachment (meta, sinon génère à la volée).
 * Mémorisé par request.
 */
function lb3_get_lqip(int $attachment_id): string
{
    static $cache = [];

    if (isset($cache[$attachment_id])) {
        return $cache[$attachment_id];
    }

    $stored = (string) get_post_meta($attachment_id, LB3_LQIP_META_KEY, true);
    if ($stored !== '') {
        return $cache[$attachment_id] = $stored;
    }

    return $cache[$attachment_id] = lb3_generate_lqip_for_attachment($attachment_id);
}
