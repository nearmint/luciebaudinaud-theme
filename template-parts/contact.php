<?php
/**
 * Section Bio / Contact.
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

$texte_bio = get_field('texte_bio', lb3_get_fr_front_page_id());
?>
<div class="mx-auto max-w-3xl px-4 sm:px-8">
    <div class="pb-8 sm:hidden sm:pb-0">
        <h2 class="text-center text-2xl font-bold uppercase tracking-[0.3em] text-white">
            <?php esc_html_e('Bio / Contact', 'lb3'); ?>
        </h2>
    </div>

    <?php if ($texte_bio) : ?>
        <div class="prose-cv"
             x-data
             x-init="$el.querySelectorAll('a[href^=mailto]').forEach(a => a.setAttribute('data-umami-event', 'contact_mail_click'))">
            <?php echo wp_kses_post($texte_bio); ?>
        </div>
    <?php endif; ?>
</div>
