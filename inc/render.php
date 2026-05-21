<?php
/**
 * Rendus en fin de <body>.
 *
 * - Container PhotoSwipe (vide, peuplé par JS).
 * - Modale vidéo singleton (pilotée par Alpine via event `lb:open-video`).
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Container PhotoSwipe.
 * PhotoSwipe v5 n'a pas besoin de markup : il génère son propre DOM à l'ouverture.
 * On garde cette fonction pour d'éventuels hooks futurs. Ne rend rien par défaut.
 */
add_action('wp_footer', static function (): void {
    // Intentionnellement vide. PhotoSwipe 5 monte tout en JS.
}, 20);

/**
 * Barre de progression de lecture — contenus longs uniquement.
 * On l'exclut de la home (navigation non-linéaire par sections).
 */
add_action('wp_footer', static function (): void {
    $is_legal = false;
    if (is_singular('page')) {
        $slug = (string) get_post_field('post_name', get_queried_object_id());
        $known_slugs = ['mentions-legales', 'politique-confidentialite', 'legal-notice', 'privacy-policy'];
        $is_legal = is_page_template('page-legal.php') || in_array($slug, $known_slugs, true);
    }
    if (!is_singular('post') && !$is_legal) {
        return;
    }
    get_template_part('template-parts/scroll-progress');
}, 15);

/**
 * Bouton flottant "Retour en haut" — visible sur toutes les pages.
 */
add_action('wp_footer', static function (): void {
    get_template_part('template-parts/back-to-top');
}, 25);

/**
 * Modale vidéo singleton.
 *
 * Stratégie : une seule modale en fin de <body>, gérée par un composant Alpine.
 * Sur la home et le single film, les triggers dispatchent un CustomEvent
 * `lb:open-video` avec l'HTML iframe en payload.
 *
 * L'iframe n'est injectée qu'à l'ouverture (lazy-load réel, gain perf majeur).
 */
add_action('wp_footer', static function (): void {
    if (!(is_front_page() || is_singular('post'))) {
        return;
    }
    ?>
    <div
        id="lb-video-modal"
        x-data="videoModal"
        @lb:open-video.window="open($event.detail)"
        @keydown.escape.window="close()"
        x-show="isOpen"
        x-cloak
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[100] flex items-center justify-center bg-ink/95 p-4 sm:p-8"
        role="dialog"
        aria-modal="true"
        aria-labelledby="lb-video-modal-title"
    >
        <h2 id="lb-video-modal-title" class="sr-only"><?php esc_html_e('Lecteur vidéo', 'lb3'); ?></h2>

        <button
            type="button"
            @click="close()"
            class="absolute right-4 top-4 z-10 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink"
            aria-label="<?php esc_attr_e('Fermer la vidéo', 'lb3'); ?>"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>

        <div
            class="relative aspect-video w-full max-w-6xl bg-black"
            @click.outside="close()"
        >
            <div x-html="iframeHtml" class="absolute inset-0 [&>iframe]:h-full [&>iframe]:w-full"></div>
        </div>
    </div>
    <?php
}, 30);
