<?php
/**
 * Grille de films avec filtres par catégorie.
 *
 * Powered by Alpine.js — composant `filmsFilter`.
 * Les catégories sont chargées dynamiquement (slug au lieu d'ID hardcodé).
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

$categories = lb3_get_film_categories();
$films      = lb3_get_all_films();

$filter_labels = wp_json_encode([
    'all_singular' => __('1 film affiché', 'lb3'),
    'all_plural'   => __('%d films affichés', 'lb3'),
    'cat_singular' => __('%s : 1 film', 'lb3'),
    'cat_plural'   => __('%s : %d films', 'lb3'),
], JSON_UNESCAPED_UNICODE);
?>
<section id="films" class="scroll-mt-[var(--lb-header-h)] py-16 sm:py-20">
    <div class="px-4 sm:px-8"
         x-data="filmsFilter({ labels: <?php echo esc_attr($filter_labels); ?> })"
         @lb:filter-changed.window="setFilter($event.detail)">

        <div role="status" aria-live="polite" aria-atomic="true" class="sr-only" x-text="statusMessage"></div>

        <h2 class="sr-only"><?php esc_html_e('Films', 'lb3'); ?></h2>

        <?php if (!empty($categories)) : ?>
            <nav class="sticky top-[var(--lb-header-h)] z-20 -mx-4 mb-10 bg-ink/80 px-4 py-5 backdrop-blur-md sm:-mx-8 sm:px-8 sm:py-6"
                 aria-label="<?php esc_attr_e('Filtrer les films par catégorie', 'lb3'); ?>">
                <ul class="lb-hide-scrollbar flex snap-x snap-mandatory gap-x-6 overflow-x-auto whitespace-nowrap text-xs font-bold uppercase tracking-[0.1em] sm:flex-wrap sm:justify-center sm:gap-x-8 sm:overflow-visible sm:whitespace-normal"
                    data-films-filters>

                    <li class="shrink-0 snap-start">
                        <button type="button"
                                @click="setFilter('*')"
                                :class="active === '*' ? 'text-white border-white' : 'text-white/70 border-transparent'"
                                :aria-current="active === '*' ? 'true' : 'false'"
                                data-filter-slug="*"
                                class="cursor-pointer border-b-2 pb-0.5 uppercase transition hover:text-white focus-visible:outline-none focus-visible:text-white"
                                data-umami-event="film_filter"
                                data-umami-event-category="all">
                            <?php echo esc_html(function_exists('pll__') ? pll__('Tout') : __('Tout', 'lb3')); ?>
                        </button>
                    </li>

                    <?php foreach ($categories as $cat) : ?>
                        <li class="shrink-0 snap-start">
                            <button type="button"
                                    @click="setFilter('<?php echo esc_js($cat->slug); ?>')"
                                    :class="active === '<?php echo esc_js($cat->slug); ?>' ? 'text-white border-white' : 'text-white/70 border-transparent'"
                                    :aria-current="active === '<?php echo esc_js($cat->slug); ?>' ? 'true' : 'false'"
                                    data-filter-slug="<?php echo esc_attr($cat->slug); ?>"
                                    class="cursor-pointer border-b-2 pb-0.5 uppercase transition hover:text-white focus-visible:outline-none focus-visible:text-white"
                                    data-umami-event="film_filter"
                                    data-umami-event-category="<?php echo esc_attr($cat->slug); ?>">
                                <?php echo esc_html($cat->name); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <?php if ($films->have_posts()) : ?>
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                <?php
                $lb_film_index = 0;
                while ($films->have_posts()) :
                    $films->the_post();
                    get_template_part('template-parts/film-card', null, ['index' => $lb_film_index]);
                    $lb_film_index++;
                endwhile;
                ?>
            </div>
        <?php else : ?>
            <p class="text-center text-white/80"><?php esc_html_e('Aucun film à afficher', 'lb3'); ?></p>
        <?php endif; wp_reset_postdata(); ?>

    </div>
</section>
