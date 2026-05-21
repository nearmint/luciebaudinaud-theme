<?php
/**
 * Favicon : injection des <link> dans <head> + site.webmanifest.
 *
 * Chaque tag n'est émis que si le fichier existe sur disque. Permet de livrer
 * un placeholder SVG monogramme "LB" et d'ajouter les PNG/ICO finaux plus tard
 * sans toucher au code (cf. assets/favicon/README.md).
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Retourne l'URL publique d'un asset favicon si le fichier existe, null sinon.
 */
function lb3_favicon_url(string $filename): ?string
{
    $path = LB3_DIR . '/assets/favicon/' . $filename;
    if (!is_readable($path)) {
        return null;
    }
    return LB3_URI . '/assets/favicon/' . $filename;
}

add_action('wp_head', static function (): void {
    $ico              = lb3_favicon_url('favicon.ico');
    $svg              = lb3_favicon_url('favicon.svg');
    $apple            = lb3_favicon_url('apple-touch-icon.png');
    $png_192          = lb3_favicon_url('favicon-192.png');
    $png_512          = lb3_favicon_url('favicon-512.png');
    $safari_pinned    = lb3_favicon_url('safari-pinned-tab.svg');
    $manifest         = lb3_favicon_url('site.webmanifest');

    if ($ico) {
        echo '<link rel="icon" href="' . esc_url($ico) . '" sizes="any">' . "\n";
    }
    if ($svg) {
        echo '<link rel="icon" type="image/svg+xml" href="' . esc_url($svg) . '">' . "\n";
    }
    if ($png_192) {
        echo '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url($png_192) . '">' . "\n";
    }
    if ($png_512) {
        echo '<link rel="icon" type="image/png" sizes="512x512" href="' . esc_url($png_512) . '">' . "\n";
    }
    if ($apple) {
        echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url($apple) . '">' . "\n";
    }
    if ($safari_pinned) {
        echo '<link rel="mask-icon" href="' . esc_url($safari_pinned) . '" color="#1C1A23">' . "\n";
    }
    if ($manifest) {
        echo '<link rel="manifest" href="' . esc_url($manifest) . '">' . "\n";
    }
}, 3);
