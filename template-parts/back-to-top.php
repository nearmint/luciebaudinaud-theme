<?php
/**
 * Bouton flottant "Retour en haut".
 * Apparaît après 600 px de scroll. Rendu sur toutes les pages via wp_footer.
 *
 * @package lb3
 */

defined('ABSPATH') || exit;
?>
<button
    type="button"
    x-data="backToTop"
    x-init="init()"
    x-show="visible"
    x-cloak
    x-transition.opacity.duration.200ms
    @click="scrollToTop()"
    class="fixed bottom-6 right-6 z-40 flex h-11 w-11 cursor-pointer items-center justify-center rounded-full bg-ink/70 text-white shadow-[0_2px_10px_rgba(0,0,0,0.4)] backdrop-blur-md transition hover:bg-ink/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink"
    aria-label="<?php esc_attr_e('Retour en haut de la page', 'lb3'); ?>"
    data-umami-event="back_to_top"
>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m18 15-6-6-6 6"/>
    </svg>
</button>
