<?php
/**
 * Nettoyage du <head> WordPress.
 * Supprime les bruits inutiles sur un site portfolio one-page.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// Generator (masque la version WP).
remove_action('wp_head', 'wp_generator');

// Emoji script & styles (inutile, on a de toute façon utf-8).
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

// DNS prefetch par défaut WP (on gère nos propres preconnect).
remove_action('wp_head', 'wp_resource_hints', 2);

// RSD + wlwmanifest (blogs uniquement, inutile ici).
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');

// Shortlink (SEO : on a des permaliens propres).
remove_action('wp_head', 'wp_shortlink_wp_head');

// REST API link (garde-le si tu utilises Gutenberg beaucoup ; OK à supprimer ici).
// On laisse activé pour ne pas casser Polylang / ACF admin. Pas ajouté ici.

// Block library CSS (Gutenberg) — on n'utilise pas Gutenberg pour le front.
add_action('wp_enqueue_scripts', static function (): void {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
}, 100);

// Supprime wp-embed.min.js (embed oEmbed dans le contenu — inutile ici).
add_action('wp_footer', static function (): void {
    wp_dequeue_script('wp-embed');
}, 1);
