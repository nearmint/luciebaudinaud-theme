<?php
/**
 * Toggle FR ⟷ EN.
 *
 * Args :
 *  - $args['inline'] bool  Si true, rend sans `position: fixed` (usage dans une nav).
 *                          Défaut : false (mode flottant historique).
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

if (!function_exists('pll_the_languages')) {
    return;
}

$inline = !empty($args['inline']);

$languages = pll_the_languages(['raw' => 1]);
if (!is_array($languages) || empty($languages)) {
    return;
}

$fr_url = $en_url = '#';
$fr_active = $en_active = false;

foreach ($languages as $lang) {
    if ($lang['slug'] === 'fr') {
        $fr_url    = $lang['url'];
        $fr_active = (bool) $lang['current_lang'];
    } elseif ($lang['slug'] === 'en') {
        $en_url    = $lang['url'];
        $en_active = (bool) $lang['current_lang'];
    }
}

$other_url  = $fr_active ? $en_url : $fr_url;
$other_lang = $fr_active ? 'en' : 'fr';
$other_loc  = $fr_active ? 'en-US' : 'fr-FR';

// Flottant : masqué sur mobile (< sm) pour ne pas chevaucher la nav ancres du hero.
// Sur mobile le switch reste accessible via le burger du header sticky dès que
// l'utilisateur scrolle au-delà du hero.
// `absolute` (et non `fixed`) : le switcher est ancré dans le hero et scrolle
// avec lui. Dès que le header sticky apparaît (hors hero), le switcher du hero
// est hors viewport → un seul switcher visible à la fois.
$wrapper_classes = $inline
    ? 'lang-toggle lang-toggle--inline'
    : 'lang-toggle lang-toggle--floating absolute right-4 top-4 z-20 hidden sm:right-6 sm:top-6 sm:block';
?>
<nav class="<?php echo esc_attr($wrapper_classes); ?>"
     aria-label="<?php esc_attr_e('Changer de langue', 'lb3'); ?>">
    <a href="<?php echo esc_url($other_url); ?>"
       hreflang="<?php echo esc_attr($other_loc); ?>"
       lang="<?php echo esc_attr($other_lang); ?>"
       data-umami-event="lang_switch"
       data-umami-event-from="<?php echo esc_attr($fr_active ? 'fr' : 'en'); ?>"
       data-umami-event-to="<?php echo esc_attr($other_lang); ?>"
       class="group inline-flex items-center gap-2 rounded-full bg-ink/40 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-sm transition hover:bg-ink/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink">
        <span class="<?php echo $fr_active ? 'text-white' : 'text-white/50'; ?>"
              <?php echo $fr_active ? 'aria-current="true"' : ''; ?>>FR</span>
        <span class="relative inline-block h-4 w-8 rounded-full bg-white/20" aria-hidden="true">
            <span class="absolute top-0.5 h-3 w-3 rounded-full bg-white transition-all duration-300
                         <?php echo $fr_active ? 'left-0.5' : 'left-[18px]'; ?>"></span>
        </span>
        <span class="<?php echo $en_active ? 'text-white' : 'text-white/50'; ?>"
              <?php echo $en_active ? 'aria-current="true"' : ''; ?>>EN</span>
    </a>
</nav>
