<?php
/**
 * Single film.
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

$cover_url    = lb3_film_cover_url();
$iframe_html  = lb3_film_iframe_html();
$realisateur  = (string) get_field('realisateur');
$duree        = (string) get_field('duree');
$annee        = (string) get_field('annee');
$gallery_raw  = get_field('gallery');
$photos       = lb3_normalize_gallery($gallery_raw);
$selections   = get_field('selections');
if (!is_array($selections)) {
    $selections = [];
}
$technical = [
    'camera'                 => (string) get_field('camera'),
    'lenses'                 => (string) get_field('lenses'),
    'format'                 => (string) get_field('format'),
    'pellicule_vs_numerique' => (string) get_field('pellicule_vs_numerique'),
    'aspect_ratio'           => (string) get_field('aspect_ratio'),
    'resolution'             => (string) get_field('resolution'),
    'etalonnage'             => (string) get_field('etalonnage'),
];
$has_technical = array_filter($technical) !== [];
$transcription = (string) get_field('transcription');
$technical_labels = [
    'camera'                 => __('Caméra', 'lb3'),
    'lenses'                 => __('Optiques', 'lb3'),
    'format'                 => __('Format', 'lb3'),
    'pellicule_vs_numerique' => __('Support', 'lb3'),
    'aspect_ratio'           => __('Ratio', 'lb3'),
    'resolution'             => __('Résolution', 'lb3'),
    'etalonnage'             => __('Étalonnage', 'lb3'),
];
$support_labels = [
    'numerique' => __('Numérique', 'lb3'),
    'pellicule' => __('Pellicule', 'lb3'),
    'mixte'     => __('Mixte', 'lb3'),
];
$type_labels = [
    'selection' => __('Sélection', 'lb3'),
    'prix'      => __('Prix', 'lb3'),
    'mention'   => __('Mention spéciale', 'lb3'),
];
?>

<main id="main" role="main">

    <article class="film-single">

        <?php get_template_part('template-parts/site-header', null, ['variant' => 'solid']); ?>

        <header class="relative flex min-h-[70svh] items-center justify-center overflow-hidden bg-black text-white">
            <?php if ($cover_url) : ?>
                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
                     style="background-image: url('<?php echo esc_url($cover_url); ?>');"
                     aria-hidden="true"></div>
            <?php endif; ?>

            <?php if ($iframe_html) : ?>
                <button type="button"
                        x-data
                        @click="$dispatch('lb:open-video', { html: <?php echo esc_attr(wp_json_encode($iframe_html)); ?> })"
                        class="relative z-10 inline-flex cursor-pointer items-center gap-3 rounded-full bg-white/15 px-6 py-3 text-xs font-bold uppercase tracking-[0.15em] text-white backdrop-blur-sm transition hover:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-4 focus-visible:ring-offset-ink"
                        data-umami-event="film_play"
                        data-umami-event-slug="<?php echo esc_attr(get_post_field('post_name')); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><path d="M8 5.14v14l11-7-11-7z"/></svg>
                    <?php esc_html_e('Regarder', 'lb3'); ?>
                </button>
            <?php endif; ?>
        </header>

        <div class="mx-auto max-w-3xl px-4 py-16 sm:px-8 sm:py-20">
            <h1 class="text-center text-2xl font-bold uppercase tracking-[0.06em] text-white sm:text-3xl md:text-4xl">
                <?php the_title(); ?>
            </h1>

            <?php if ($realisateur || $duree || $annee) : ?>
                <ul class="mt-6 flex flex-wrap justify-center gap-x-6 gap-y-2 text-xs font-bold uppercase tracking-[0.15em] text-white/70">
                    <?php if ($realisateur) : ?><li><?php echo esc_html($realisateur); ?></li><?php endif; ?>
                    <?php if ($duree) : ?><li><?php echo esc_html($duree); ?></li><?php endif; ?>
                    <?php if ($annee) : ?><li><?php echo esc_html($annee); ?></li><?php endif; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($selections)) : ?>
                <section class="mt-10" aria-label="<?php esc_attr_e('Sélections et prix', 'lb3'); ?>">
                    <h2 class="mb-4 text-center text-xs font-bold uppercase tracking-[0.3em] text-white/70">
                        <?php esc_html_e('Sélections & prix', 'lb3'); ?>
                    </h2>
                    <ul class="mx-auto max-w-2xl divide-y divide-white/10 text-xs uppercase tracking-[0.1em] text-white/80">
                        <?php foreach ($selections as $row) :
                            $festival = (string) ($row['festival'] ?? '');
                            if ($festival === '') {
                                continue;
                            }
                            $section   = (string) ($row['section'] ?? '');
                            $sel_annee = (string) ($row['annee'] ?? '');
                            $type      = (string) ($row['type'] ?? 'selection');
                            $prix_nom  = (string) ($row['prix_nom'] ?? '');
                            $type_label = $type_labels[$type] ?? $type_labels['selection'];
                            ?>
                            <li class="flex flex-wrap items-baseline gap-x-3 gap-y-1 py-3">
                                <span class="text-[0.7rem] font-bold tracking-[0.2em] text-white">
                                    <?php echo esc_html($type_label); ?>
                                </span>
                                <span class="text-white">
                                    <?php echo esc_html($festival); ?>
                                </span>
                                <?php if ($section !== '') : ?>
                                    <span class="text-white/60" aria-hidden="true">·</span>
                                    <span><?php echo esc_html($section); ?></span>
                                <?php endif; ?>
                                <?php if ($type !== 'selection' && $prix_nom !== '') : ?>
                                    <span class="text-white/60" aria-hidden="true">·</span>
                                    <span class="italic text-white"><?php echo esc_html($prix_nom); ?></span>
                                <?php endif; ?>
                                <?php if ($sel_annee !== '') : ?>
                                    <span class="ml-auto font-bold text-white/70"><?php echo esc_html($sel_annee); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if (get_the_content()) : ?>
                <div class="prose-cv mt-12">
                    <?php the_content(); ?>
                </div>
            <?php endif; ?>

            <?php if ($transcription !== '') : ?>
                <details class="mt-10 border-t border-white/10 pt-6 text-white/80 open:pb-2">
                    <summary class="cursor-pointer text-xs font-bold uppercase tracking-[0.2em] text-white/70 transition hover:text-white focus-visible:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-4 focus-visible:ring-offset-ink">
                        <?php esc_html_e('Transcription de la bande-annonce', 'lb3'); ?>
                    </summary>
                    <div class="prose-cv mt-6">
                        <?php echo wp_kses_post($transcription); ?>
                    </div>
                </details>
            <?php endif; ?>

            <?php if (!empty($photos)) : ?>
                <div id="lb-photoswipe-film-gallery" class="mt-16 space-y-6">
                    <?php foreach ($photos as $i => $photo) : ?>
                        <a href="<?php echo esc_url($photo['url']); ?>"
                           data-pswp-width="<?php echo (int) $photo['width']; ?>"
                           data-pswp-height="<?php echo (int) $photo['height']; ?>"
                           data-pswp-caption="<?php echo esc_attr($photo['title']); ?>"
                           data-umami-event="gallery_open"
                           data-umami-event-context="film"
                           data-umami-event-index="<?php echo (int) $i; ?>"
                           class="block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-4 focus-visible:ring-offset-ink">
                            <img src="<?php echo esc_url($photo['url']); ?>"
                                 alt="<?php echo esc_attr($photo['alt']); ?>"
                                 width="<?php echo (int) $photo['width']; ?>"
                                 height="<?php echo (int) $photo['height']; ?>"
                                 loading="lazy"
                                 decoding="async"
                                 class="h-auto w-full">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($has_technical) : ?>
                <details class="mt-16 border-t border-white/10 pt-6 text-white/80 open:pb-2">
                    <summary class="cursor-pointer text-xs font-bold uppercase tracking-[0.2em] text-white/70 transition hover:text-white focus-visible:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-4 focus-visible:ring-offset-ink">
                        <?php esc_html_e('Fiche technique', 'lb3'); ?>
                    </summary>
                    <dl class="mt-6 grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                        <?php foreach ($technical as $key => $value) :
                            if ($value === '') {
                                continue;
                            }
                            $display_value = ($key === 'pellicule_vs_numerique')
                                ? ($support_labels[$value] ?? $value)
                                : $value;
                            ?>
                            <div class="flex flex-col gap-0.5">
                                <dt class="text-[0.7rem] font-bold uppercase tracking-[0.2em] text-white/60">
                                    <?php echo esc_html($technical_labels[$key]); ?>
                                </dt>
                                <dd class="text-sm text-white/90">
                                    <?php echo esc_html($display_value); ?>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </details>
            <?php endif; ?>

            <?php if ($realisateur || $annee) : ?>
                <p class="mt-10 text-center text-[0.65rem] uppercase tracking-[0.2em] text-white/40">
                    <?php
                    /* translators: %1$s : réalisateur ou «Lucie Baudinaud», %2$s : année. */
                    echo esc_html(sprintf(
                        __('© %1$s %2$s — Tous droits réservés', 'lb3'),
                        $realisateur !== '' ? $realisateur : 'Lucie Baudinaud',
                        $annee
                    ));
                    ?>
                </p>
            <?php endif; ?>

        </div>

        <div class="mx-auto max-w-3xl border-t border-white/10 px-4 py-12 sm:px-8 sm:py-16">
            <?php get_template_part('template-parts/breadcrumbs'); ?>
        </div>
    </article>

</main>

<?php get_footer(); ?>
