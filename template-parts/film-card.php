<?php
/**
 * Carte film (dans la grille). Exécuté dans la boucle de films-grid.php.
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

// La vignette est rendue via lb3_the_attachment_picture() plus bas : le helper
// résout src / srcset / dimensions et sert en prime <picture> avec AVIF+WebP
// si les fichiers jumeaux ont été générés (cf. bin/convert-images.sh).
$thumb_id = (int) get_post_thumbnail_id();
$lqip     = ($thumb_id && function_exists('lb3_get_lqip')) ? lb3_get_lqip($thumb_id) : '';
$slugs        = lb3_film_category_slugs();
$iframe       = lb3_film_iframe_html();
// lb3_get_field() met en cache sur HOUR_IN_SECONDS — critique dans cette boucle
// de N films où chaque card lit 3 champs meta.
$get = function_exists('lb3_get_field') ? 'lb3_get_field' : 'get_field';
$realisateur  = (string) $get('realisateur');
$duree        = (string) $get('duree');
$annee        = (string) $get('annee');
$index        = isset($args['index']) ? (int) $args['index'] : 0;
// Stagger plafonné à 800 ms (soit ~13 cartes, au-delà tout apparaît ensemble).
$stagger_ms   = min($index * 60, 800);
?>
<?php
$article_style = 'transition-delay: ' . (int) $stagger_ms . 'ms;';
if ($lqip !== '') {
    $article_style .= ' background-image: url(\'' . esc_attr($lqip) . '\'); background-size: cover; background-position: center;';
}
?>
<article class="film group relative overflow-hidden bg-ink isolate"
         data-cats="<?php echo esc_attr($slugs); ?>"
         x-data="{ shown: false }"
         x-intersect.once="shown = true"
         :class="{ 'film--shown': shown }"
         style="<?php echo esc_attr($article_style); ?>"
         x-show="matches($el.dataset.cats)"
         x-transition:enter="transition duration-300 ease-out"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

    <?php if ($thumb_id) : ?>
        <?php lb3_the_attachment_picture($thumb_id, 'lb3-film-card', [
            'sizes'    => '(min-width: 640px) 50vw, 100vw',
            'alt'      => get_the_title(),
            'loading'  => 'lazy',
            'decoding' => 'async',
            'x-data'   => '{ loaded: false }',
            'x-init'   => 'loaded = ($el.complete && $el.naturalWidth > 0)',
            '@load'    => 'loaded = true',
            ':class'   => "{ 'opacity-100': loaded, 'opacity-0': !loaded }",
            'class'    => 'block h-auto w-full transition-opacity duration-500',
        ]); ?>
    <?php else : ?>
        <div class="film__fallback flex aspect-video items-center justify-center bg-white/5 text-white/30" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
        </div>
    <?php endif; ?>

    <a href="<?php the_permalink(); ?>"
       class="absolute inset-0 z-[3] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-white"
       data-film-link
       data-umami-event="film_view"
       data-umami-event-slug="<?php echo esc_attr(get_post_field('post_name')); ?>"
       aria-label="<?php echo esc_attr(sprintf(
           /* translators: %s: film title */
           __('Voir la page du film %s', 'lb3'),
           get_the_title()
       )); ?>">
        <span class="sr-only"><?php the_title(); ?></span>
    </a>

    <?php if ($iframe) : ?>
        <button type="button"
                @click="$dispatch('lb:open-video', { html: <?php echo esc_attr(wp_json_encode($iframe)); ?> })"
                class="absolute left-1/2 top-1/2 z-10 flex h-14 w-14 -translate-x-1/2 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-white/15 text-white opacity-0 backdrop-blur-sm transition duration-300 group-hover:opacity-100 hover:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus:opacity-100"
                data-umami-event="film_play"
                data-umami-event-slug="<?php echo esc_attr(get_post_field('post_name')); ?>"
                aria-label="<?php echo esc_attr(sprintf(
                    /* translators: %s: film title */
                    __('Regarder la bande-annonce de %s', 'lb3'),
                    get_the_title()
                )); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><path d="M8 5.14v14l11-7-11-7z"/></svg>
        </button>
    <?php endif; ?>

    <footer class="pointer-events-none absolute inset-x-0 bottom-0 z-[2] p-4 transition-transform duration-300 group-hover:-translate-y-1 sm:p-6">
        <h3 class="text-sm font-bold uppercase tracking-wider text-white drop-shadow-[0_2px_6px_rgba(0,0,0,0.9)] sm:text-base">
            <?php the_title(); ?>
        </h3>
        <?php if ($realisateur) : ?>
            <p class="mt-1 text-xs uppercase tracking-wider text-white/90 drop-shadow-[0_2px_6px_rgba(0,0,0,0.9)] sm:text-sm"><?php echo esc_html($realisateur); ?></p>
        <?php endif; ?>
        <?php if ($duree || $annee) : ?>
            <p class="mt-0.5 text-xs text-white/80 drop-shadow-[0_2px_6px_rgba(0,0,0,0.9)]">
                <?php echo esc_html(trim($duree . ' ' . $annee)); ?>
            </p>
        <?php endif; ?>
    </footer>
</article>
