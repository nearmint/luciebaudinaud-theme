<?php
/**
 * Helper `<picture>` AVIF / WebP / fallback JPG-PNG.
 *
 * Principe : WordPress génère les variantes .jpg/.png via `wp_get_attachment_image_src()`
 * et `wp_get_attachment_image_srcset()`. On s'appuie sur ces URLs pour vérifier
 * sur disque la présence de fichiers jumeaux `.avif` / `.webp` (même dossier,
 * même basename). Si oui, on émet un <picture> avec les <source> correspondants.
 * Sinon on retombe sur un <img> classique — zéro régression.
 *
 * La génération des fichiers .avif / .webp est one-shot, faite via
 * `bin/convert-images.sh`. Le thème ne fait AUCUNE conversion on-the-fly.
 *
 * @package lb3
 */
declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Convertit une URL pointant dans wp-content/uploads/ en path disque.
 * Retourne null si l'URL n'est pas dans les uploads (ex : CDN externe).
 */
function lb3_upload_url_to_path(string $url): ?string
{
    $upload = wp_upload_dir();
    $baseurl = rtrim((string) ($upload['baseurl'] ?? ''), '/');
    $basedir = rtrim((string) ($upload['basedir'] ?? ''), '/');
    if ($baseurl === '' || $basedir === '') {
        return null;
    }
    // Strip query string / anchor.
    $clean = (string) strtok($url, '?');
    if (strpos($clean, $baseurl) !== 0) {
        return null;
    }
    return $basedir . substr($clean, strlen($baseurl));
}

/**
 * Remplace l'extension .jpg/.jpeg/.png d'une URL par une autre (ex : avif).
 */
function lb3_swap_image_ext(string $url, string $new_ext): string
{
    return (string) preg_replace('/\.(jpe?g|png)(?=$|\?)/i', '.' . $new_ext, $url);
}

/**
 * Construit un srcset AVIF/WebP à partir d'un srcset JPG/PNG :
 * ne conserve que les entrées dont le fichier alternatif existe sur disque.
 * Retourne '' si aucune variante n'est disponible.
 */
function lb3_alt_srcset(string $srcset, string $new_ext): string
{
    if ($srcset === '') {
        return '';
    }
    $kept = [];
    foreach (explode(',', $srcset) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        // "<url> <1234w>" (descriptor optionnel mais présent en pratique).
        $bits = preg_split('/\s+/', $part, 2);
        if (!is_array($bits) || $bits[0] === '') {
            continue;
        }
        $alt_url = lb3_swap_image_ext($bits[0], $new_ext);
        $path = lb3_upload_url_to_path($alt_url);
        if ($path === null || !is_file($path)) {
            continue;
        }
        $kept[] = trim($alt_url . (isset($bits[1]) ? ' ' . $bits[1] : ''));
    }
    return implode(', ', $kept);
}

/**
 * Retourne l'URL AVIF de l'image `$size` si un fichier .avif existe, sinon ''.
 * Utile pour émettre un <link rel="preload" type="image/avif"> ciblé.
 */
function lb3_attachment_avif_url(int $id, string $size = 'full'): string
{
    $src = wp_get_attachment_image_src($id, $size);
    if (!$src) {
        return '';
    }
    $alt = lb3_swap_image_ext($src[0], 'avif');
    $path = lb3_upload_url_to_path($alt);
    return ($path !== null && is_file($path)) ? $alt : '';
}

/**
 * Formate un dict d'attributs HTML → chaîne " key=\"value\" …".
 * Passe-through pour les attributs Alpine (@click, x-data, :class) : la clé
 * est émise telle quelle, la valeur passe par esc_attr.
 */
function lb3_html_attrs(array $attrs): string
{
    $out = '';
    foreach ($attrs as $k => $v) {
        if ($v === null || $v === false) {
            continue;
        }
        if ($v === true) {
            $out .= ' ' . $k;
            continue;
        }
        $out .= ' ' . $k . '="' . esc_attr((string) $v) . '"';
    }
    return $out;
}

/**
 * Émet un <picture> avec sources AVIF + WebP si disponibles, sinon <img>.
 *
 * @param int    $id    Attachment ID.
 * @param string $size  Image size name (WP : 'full', 'lb3-film-card', etc.).
 * @param array  $attrs Attributs HTML appliqués au <img> : class, sizes, alt,
 *                      loading, decoding, fetchpriority, width, height, plus
 *                      tout attribut Alpine (x-data, @load, :class, etc.).
 *                      `src`, `srcset` sont ignorés (calculés ici).
 */
function lb3_the_attachment_picture(int $id, string $size = 'full', array $attrs = []): void
{
    $src = wp_get_attachment_image_src($id, $size);
    if (!$src) {
        return;
    }

    $img_url  = (string) $src[0];
    $img_w    = (int) $src[1];
    $img_h    = (int) $src[2];
    $srcset   = (string) wp_get_attachment_image_srcset($id, $size);
    $sizes    = $attrs['sizes'] ?? '';

    $avif_srcset = lb3_alt_srcset($srcset ?: $img_url . ' ' . $img_w . 'w', 'avif');
    $webp_srcset = lb3_alt_srcset($srcset ?: $img_url . ' ' . $img_w . 'w', 'webp');

    // Attributs <img> finaux. width/height fournis par WP si pas passés.
    $img_attrs = array_merge([
        'width'  => $img_w,
        'height' => $img_h,
    ], $attrs);
    unset($img_attrs['src'], $img_attrs['srcset']);

    $has_sources = $avif_srcset !== '' || $webp_srcset !== '';

    if ($has_sources) {
        echo '<picture>';
        if ($avif_srcset !== '') {
            echo '<source type="image/avif" srcset="' . esc_attr($avif_srcset) . '"'
                . ($sizes !== '' ? ' sizes="' . esc_attr((string) $sizes) . '"' : '')
                . '>';
        }
        if ($webp_srcset !== '') {
            echo '<source type="image/webp" srcset="' . esc_attr($webp_srcset) . '"'
                . ($sizes !== '' ? ' sizes="' . esc_attr((string) $sizes) . '"' : '')
                . '>';
        }
    }

    echo '<img src="' . esc_url($img_url) . '"'
        . ($srcset !== '' ? ' srcset="' . esc_attr($srcset) . '"' : '')
        . lb3_html_attrs($img_attrs)
        . '>';

    if ($has_sources) {
        echo '</picture>';
    }
}
