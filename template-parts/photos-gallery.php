<?php
/**
 * Galerie de photos → PhotoSwipe v5.
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

$gallery_raw = get_field('galerie', lb3_get_fr_front_page_id());
$photos      = lb3_normalize_gallery($gallery_raw);

if (empty($photos)) {
    return;
}
?>
<div class="mx-auto max-w-7xl px-4 sm:px-8">
    <h2 class="sr-only"><?php esc_html_e('Galerie photos', 'lb3'); ?></h2>

    <div id="lb-photoswipe-gallery"
         class="columns-2 gap-2 sm:columns-3 sm:gap-3 md:columns-4 lg:columns-5">
        <?php foreach ($photos as $i => $photo) : ?>
            <?php
            $lqip  = (!empty($photo['id']) && function_exists('lb3_get_lqip')) ? lb3_get_lqip((int) $photo['id']) : '';
            $style = $lqip !== ''
                ? 'background-image:url(\'' . esc_attr($lqip) . '\');background-size:cover;background-position:center;'
                : '';
            ?>
            <a href="<?php echo esc_url($photo['url']); ?>"
                data-pswp-width="<?php echo (int) $photo['width']; ?>"
                data-pswp-height="<?php echo (int) $photo['height']; ?>"
                data-pswp-caption="<?php echo esc_attr($photo['title']); ?>"
                data-umami-event="gallery_open"
                data-umami-event-index="<?php echo (int) $i; ?>"
                <?php echo $style !== '' ? 'style="' . $style . '"' : ''; ?>
                class="group relative mb-2 block break-inside-avoid overflow-hidden bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink sm:mb-3">
                <img src="<?php echo esc_url($photo['thumb']); ?>"
                     alt="<?php echo esc_attr($photo['alt']); ?>"
                     loading="lazy"
                     decoding="async"
                     width="<?php echo (int) $photo['width']; ?>"
                     height="<?php echo (int) $photo['height']; ?>"
                     x-data="{ loaded: false }"
                     x-init="loaded = ($el.complete && $el.naturalWidth > 0)"
                     @load="loaded = true"
                     :class="{ 'opacity-100': loaded, 'opacity-0': !loaded }"
                     class="block h-auto w-full transition duration-500 group-hover:scale-[1.04]">
            </a>
        <?php endforeach; ?>
    </div>
</div>
