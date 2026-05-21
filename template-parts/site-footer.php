<?php
/**
 * Footer global minimaliste.
 *
 * - Copyright dynamique.
 * - Liens vers les pages légales (résolus par slug dans la langue courante).
 * - Liens sociaux si renseignés en ACF (Instagram, IMDb, Vimeo).
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

$lang = lb3_current_lang();

$legal_links = [];
$slugs       = $lang === 'en'
    ? ['legal-notice' => __('Legal notice', 'lb3'), 'privacy-policy' => __('Privacy policy', 'lb3')]
    : ['mentions-legales' => __('Mentions légales', 'lb3'), 'politique-confidentialite' => __('Politique de confidentialité', 'lb3')];

foreach ($slugs as $slug => $label) {
    $page = get_page_by_path($slug, OBJECT, 'page');
    if ($page) {
        $legal_links[] = [
            'url'   => (string) get_permalink($page->ID),
            'label' => $label,
        ];
    }
}

$home_id   = lb3_get_fr_front_page_id();
$instagram = (string) get_field('social_instagram', $home_id);
$imdb      = (string) get_field('social_imdb', $home_id);
$vimeo     = (string) get_field('social_vimeo', $home_id);

$socials = array_values(array_filter([
    $instagram ? ['url' => $instagram, 'label' => 'Instagram'] : null,
    $imdb      ? ['url' => $imdb,      'label' => 'IMDb']      : null,
    $vimeo     ? ['url' => $vimeo,     'label' => 'Vimeo']     : null,
]));
?>
<footer class="lb-site-footer border-t border-white/10 bg-ink/80 px-4 py-10 text-xs font-bold uppercase tracking-[0.15em] text-white/70 sm:px-8"
        role="contentinfo">
    <div class="mx-auto flex max-w-7xl flex-col items-center gap-6 text-center md:flex-row md:justify-between md:text-left">

        <p>
            <?php
            /* translators: %s : année courante. */
            // wp_date() respecte la locale + le fuseau WP (remplace date_i18n, déprécié en contexte locale).
            printf(esc_html__('© %s Lucie Baudinaud', 'lb3'), esc_html((string) wp_date('Y')));
            ?>
        </p>

        <?php if (!empty($legal_links)) : ?>
            <nav aria-label="<?php esc_attr_e('Liens légaux', 'lb3'); ?>">
                <ul class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
                    <?php foreach ($legal_links as $link) : ?>
                        <li>
                            <a href="<?php echo esc_url($link['url']); ?>"
                               class="border-b-2 border-transparent pb-0.5 transition hover:border-white hover:text-white focus-visible:border-white focus-visible:text-white focus-visible:outline-none">
                                <?php echo esc_html($link['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <?php if (!empty($socials)) : ?>
            <nav aria-label="<?php esc_attr_e('Réseaux', 'lb3'); ?>">
                <ul class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
                    <?php foreach ($socials as $social) : ?>
                        <li>
                            <a href="<?php echo esc_url($social['url']); ?>"
                               rel="noopener noreferrer"
                               target="_blank"
                               class="border-b-2 border-transparent pb-0.5 transition hover:border-white hover:text-white focus-visible:border-white focus-visible:text-white focus-visible:outline-none">
                                <?php echo esc_html($social['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>

    </div>
</footer>
