<?php
/**
 * Fil d'Ariane visuel.
 *
 * Ne rend rien sur la home. Les items proviennent de lb3_get_breadcrumbs().
 * Le JSON-LD BreadcrumbList correspondant est émis séparément dans inc/seo.php.
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

if (!function_exists('lb3_get_breadcrumbs')) {
    return;
}

$crumbs = lb3_get_breadcrumbs();
if (empty($crumbs)) {
    return;
}

$last = count($crumbs) - 1;
?>
<nav class="lb-breadcrumbs mt-6 mb-6 text-xs uppercase tracking-[0.1em] text-white/70"
     aria-label="<?php esc_attr_e('Fil d\'Ariane', 'lb3'); ?>">
    <ol class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1">
        <?php foreach ($crumbs as $i => $crumb) : ?>
            <li class="flex items-center gap-2">
                <?php if ($i < $last) : ?>
                    <a href="<?php echo esc_url($crumb['url']); ?>"
                       class="border-b border-transparent transition hover:border-white hover:text-white focus-visible:border-white focus-visible:text-white focus-visible:outline-none">
                        <?php echo esc_html($crumb['label']); ?>
                    </a>
                    <span aria-hidden="true" class="text-white/40">›</span>
                <?php else : ?>
                    <span aria-current="page" class="text-white"><?php echo esc_html($crumb['label']); ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
