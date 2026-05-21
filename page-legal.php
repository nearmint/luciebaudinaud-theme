<?php
/**
 * Template Name: Page légale
 *
 * Rendu des pages mentions légales / politique de confidentialité (FR & EN).
 *
 * Slugs prévus : mentions-legales, politique-confidentialite, legal-notice, privacy-policy.
 * Contenu saisi via le champ ACF WYSIWYG `legal_content` (cf. group_lb3_legal).
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

get_header();

if (!have_posts()) {
    get_template_part('404');
    get_footer();
    return;
}

the_post();

$legal_content = (string) get_field('legal_content');
?>

<?php get_template_part('template-parts/site-header', null, ['variant' => 'solid']); ?>

<main id="main" role="main">
    <article class="page-legal">

        <header class="pt-28 pb-10 sm:pt-36 sm:pb-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-8">
                <?php get_template_part('template-parts/breadcrumbs'); ?>
            </div>

            <h1 class="mx-auto max-w-3xl px-4 text-center text-2xl font-bold uppercase tracking-[0.06em] text-white sm:px-8 sm:text-3xl md:text-4xl">
                <?php the_title(); ?>
            </h1>
        </header>

        <div class="mx-auto max-w-3xl px-4 pb-24 sm:px-8 sm:pb-32">
            <?php if ($legal_content !== '') : ?>
                <div class="prose-cv">
                    <?php echo wp_kses_post($legal_content); ?>
                </div>
            <?php else : ?>
                <p class="prose-cv text-white/80">
                    <?php esc_html_e('Contenu à renseigner dans l\'admin WordPress (champ « Contenu de la page »).', 'lb3'); ?>
                </p>
            <?php endif; ?>

            <p class="mt-16 text-xs font-bold uppercase tracking-[0.15em] text-white/70">
                <a href="<?php echo esc_url(lb3_home_url_localized()); ?>"
                   class="inline-flex items-center gap-2 transition hover:text-white focus-visible:text-white focus-visible:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    <span><?php esc_html_e('Retour à l\'accueil', 'lb3'); ?></span>
                </a>
            </p>
        </div>

    </article>
</main>

<?php get_footer(); ?>
