<?php
/**
 * Header de site — composant unifié.
 *
 * Variants :
 *  - 'sticky' (défaut sur home) : apparaît au scroll hors du hero.
 *    Requiert un élément #intro dans la page (sentinel IntersectionObserver).
 *  - 'solid'  (single film)      : toujours visible en haut, fond plein.
 *
 * Args :
 *  - $args['variant']      string 'sticky' | 'solid' (défaut 'sticky')
 *  - $args['include_lang'] bool   Inclut le switch FR/EN dans la nav (défaut true)
 *
 * Ancres : on pointe toujours vers la home localisée + #ancre pour que la nav
 * fonctionne aussi depuis single.php.
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

$variant      = $args['variant'] ?? 'sticky';
$include_lang = $args['include_lang'] ?? true;

$home_url = lb3_home_url_localized();

$is_sticky = ($variant === 'sticky');

$outer_classes = $is_sticky
    ? 'lb-site-header lb-site-header--sticky fixed inset-x-0 top-0 z-40 bg-ink/80 backdrop-blur-md shadow-[0_1px_0_rgba(255,255,255,0.06)]'
    : 'lb-site-header lb-site-header--solid absolute inset-x-0 top-0 z-30 bg-gradient-to-b from-ink/90 to-transparent';

// Sticky : Alpine gère la visibilité via IntersectionObserver (#intro).
// Solid  : pas d'état outer, seul le menu mobile (inner nav) utilise Alpine.
$header_alpine = $is_sticky
    ? 'x-data="siteHeader" x-init="init()" :class="{ \'is-visible\': visible }" :aria-hidden="!visible"'
    : '';

$nav_label = $is_sticky
    ? __('Navigation principale', 'lb3')
    : __('Navigation film', 'lb3');
?>
<header class="<?php echo esc_attr($outer_classes); ?>"
        <?php echo $header_alpine; // phpcs-safe: constante littérale sans input utilisateur ?>>
    <nav aria-label="<?php echo esc_attr($nav_label); ?>"
         x-data="{ open: false }">

        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-8">

            <a href="<?php echo esc_url($home_url); ?>"
               class="text-xs font-bold uppercase tracking-[0.1em] text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-4 focus-visible:ring-offset-ink">
                <?php esc_html_e('Lucie Baudinaud', 'lb3'); ?>
                <span class="hidden sm:inline"> — </span>
                <span class="block text-white/80 sm:inline"><?php esc_html_e('Directrice de la photographie', 'lb3'); ?></span>
            </a>

            <div class="flex items-center gap-6 lg:gap-8">
                <ul class="hidden items-center gap-6 text-xs font-bold uppercase tracking-[0.1em] text-white md:flex lg:gap-8">
                    <li><a href="<?php echo esc_url($home_url); ?>#films" class="border-b-2 border-transparent pb-0.5 transition hover:border-white focus-visible:border-white focus-visible:outline-none"><?php esc_html_e('Films', 'lb3'); ?></a></li>
                    <li><a href="<?php echo esc_url($home_url); ?>#photos" class="border-b-2 border-transparent pb-0.5 transition hover:border-white focus-visible:border-white focus-visible:outline-none"><?php esc_html_e('Photos', 'lb3'); ?></a></li>
                    <li><a href="<?php echo esc_url($home_url); ?>#cv" class="border-b-2 border-transparent pb-0.5 transition hover:border-white focus-visible:border-white focus-visible:outline-none"><?php esc_html_e('CV', 'lb3'); ?></a></li>
                    <li><a href="<?php echo esc_url($home_url); ?>#contact" class="border-b-2 border-transparent pb-0.5 transition hover:border-white focus-visible:border-white focus-visible:outline-none"><?php esc_html_e('Contact', 'lb3'); ?></a></li>
                </ul>

                <?php if ($include_lang) : ?>
                    <div class="hidden md:block">
                        <?php get_template_part('template-parts/lang-switcher', null, ['inline' => true]); ?>
                    </div>
                <?php endif; ?>

                <button type="button"
                        @click="open = !open"
                        :aria-expanded="open"
                        :aria-label="open ? '<?php echo esc_js(__('Fermer le menu', 'lb3')); ?>' : '<?php echo esc_js(__('Ouvrir le menu', 'lb3')); ?>'"
                        aria-controls="lb-header-mobile-menu"
                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white md:hidden"
                        aria-label="<?php esc_attr_e('Ouvrir le menu', 'lb3'); ?>">
                    <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    <svg x-show="open" x-cloak xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
        </div>

        <div x-show="open"
             x-cloak
             x-transition.opacity
             class="bg-ink/95 md:hidden"
             id="lb-header-mobile-menu">
            <ul class="flex flex-col items-center gap-4 py-6 text-xs font-bold uppercase tracking-[0.1em] text-white">
                <li><a href="<?php echo esc_url($home_url); ?>#films" class="block px-4 py-2" @click="open = false"><?php esc_html_e('Films', 'lb3'); ?></a></li>
                <li><a href="<?php echo esc_url($home_url); ?>#photos" class="block px-4 py-2" @click="open = false"><?php esc_html_e('Photos', 'lb3'); ?></a></li>
                <li><a href="<?php echo esc_url($home_url); ?>#cv" class="block px-4 py-2" @click="open = false"><?php esc_html_e('CV', 'lb3'); ?></a></li>
                <li><a href="<?php echo esc_url($home_url); ?>#contact" class="block px-4 py-2" @click="open = false"><?php esc_html_e('Contact', 'lb3'); ?></a></li>
                <?php if ($include_lang) : ?>
                    <li class="mb-4 mt-2 border-t border-white/10 pt-4">
                        <?php get_template_part('template-parts/lang-switcher', null, ['inline' => true]); ?>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>
