<?php
/**
 * 404.
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

get_header();
?>
<main id="main" role="main" class="flex min-h-svh items-center justify-center px-4 text-center">
    <div class="max-w-xl">
        <p class="text-xs font-bold uppercase tracking-[0.3em] text-white/50">404</p>
        <h1 class="mt-4 text-2xl font-bold uppercase tracking-[0.06em] sm:text-3xl">
            <?php esc_html_e('Page introuvable', 'lb3'); ?>
        </h1>
        <p class="mt-4 text-white/70">
            <?php esc_html_e('La page que vous cherchez n\'existe pas ou a été déplacée.', 'lb3'); ?>
        </p>
        <a href="<?php echo esc_url(lb3_home_url_localized()); ?>"
           class="mt-8 inline-block border-b-2 border-white pb-0.5 text-xs font-bold uppercase tracking-[0.15em] transition hover:opacity-70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-4 focus-visible:ring-offset-ink">
            <?php esc_html_e('Retour à l\'accueil', 'lb3'); ?>
        </a>
    </div>
</main>

<script>
// Tracking 404 → Umami. Retry court le temps que le script Umami s'initialise.
(function () {
    var attempts = 0;
    function track() {
        if (typeof window.umami !== 'undefined' && typeof window.umami.track === 'function') {
            window.umami.track('page_not_found', {
                path: location.pathname + location.search,
                referrer: document.referrer || 'direct',
            });
            return;
        }
        if (++attempts < 20) {
            setTimeout(track, 150);
        }
    }
    track();
})();
</script>

<?php get_footer(); ?>
