<?php
/**
 * Section CV.
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

$home_id  = lb3_get_fr_front_page_id();
$texte_cv = get_field('texte_cv', $home_id);
$cv_pdf   = (string) get_field('cv_pdf', $home_id);
?>
<div class="mx-auto max-w-3xl px-4 sm:px-8">
    <div class="pb-8 sm:hidden sm:pb-0">
        <h2 class="text-center text-2xl font-bold uppercase tracking-[0.3em] text-white">
            <?php esc_html_e('CV', 'lb3'); ?>
        </h2>
    </div>

    <?php if ($texte_cv) : ?>
        <div class="prose-cv">
            <?php echo wp_kses_post($texte_cv); ?>
        </div>
    <?php endif; ?>

    <?php if ($cv_pdf !== '') : ?>
        <p class="mt-12 text-center">
            <a href="<?php echo esc_url($cv_pdf); ?>"
               target="_blank"
               rel="noopener"
               download
               data-umami-event="cv_download"
               class="inline-flex items-center gap-3 rounded-full border border-white/60 px-6 py-3 text-xs font-bold uppercase tracking-[0.15em] text-white transition hover:border-white hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-4 focus-visible:ring-offset-ink">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                <?php esc_html_e('Télécharger le CV (PDF)', 'lb3'); ?>
            </a>
        </p>
    <?php endif; ?>
</div>
