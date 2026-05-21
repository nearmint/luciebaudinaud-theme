<?php
/**
 * Front page : hero + films + photos + CV + contact.
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

get_header();

$home_id = lb3_get_fr_front_page_id();
?>

<?php
// Header sticky global (masqué tant que le hero est visible, cf. Alpine `siteHeader`).
get_template_part('template-parts/site-header', null, ['variant' => 'sticky']);
?>

<main id="main" role="main">

    <?php get_template_part('template-parts/hero'); ?>

    <?php get_template_part('template-parts/films-grid'); ?>

    <?php
    get_template_part('template-parts/section-divider', null, [
        'title' => __('Photo', 'lb3'),
        'image' => (string) get_field('photo_cover_photogrammes', $home_id),
    ]);
    ?>

    <section id="photos"
             class="scroll-mt-[var(--lb-header-h)] py-16 sm:py-20"
             x-data="{ shown: false }"
             x-intersect.once="shown = true"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
             style="transition: opacity 700ms ease, transform 700ms ease;">
        <div class="pb-8 sm:hidden sm:pb-0">
            <h2 class="text-center text-2xl font-bold uppercase tracking-[0.3em] text-white">
                <?php esc_html_e('Photos', 'lb3'); ?>
            </h2>
        </div>
        <?php get_template_part('template-parts/photos-gallery'); ?>
    </section>

    <?php
    get_template_part('template-parts/section-divider', null, [
        'title' => __('CV', 'lb3'),
        'image' => (string) get_field('photo_cover_cv', $home_id),
    ]);
    ?>

    <section id="cv"
             class="scroll-mt-[var(--lb-header-h)] py-16 sm:py-20"
             x-data="{ shown: false }"
             x-intersect.once="shown = true"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
             style="transition: opacity 700ms ease, transform 700ms ease;">
        <?php get_template_part('template-parts/cv'); ?>
    </section>

    <?php
    get_template_part('template-parts/section-divider', null, [
        'title' => __('Contact', 'lb3'),
        'image' => (string) get_field('photo_cover_contact', $home_id),
    ]);
    ?>

    <section id="contact"
             class="scroll-mt-[var(--lb-header-h)] py-16 pb-24 sm:py-20 sm:pb-32"
             x-data="{ shown: false }"
             x-intersect.once="shown = true"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
             style="transition: opacity 700ms ease, transform 700ms ease;">
        <?php get_template_part('template-parts/contact'); ?>
    </section>

</main>

<?php get_footer(); ?>
