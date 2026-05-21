<?php
/**
 * Bande séparatrice avec image de fond + titre.
 * Remplace les <section id="intros"> répétés (même ID) de l'ancien thème.
 *
 * Args :
 *  - $args['title']       string  Titre affiché (obligatoire)
 *  - $args['image']       string  URL image de fond (optionnel)
 *  - $args['hide_mobile'] bool    Masquer sur mobile (défaut true, comme l'ancien)
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

$title       = $args['title'] ?? '';
$image       = $args['image'] ?? '';
$hide_mobile = $args['hide_mobile'] ?? true;

if (!$title) {
    return;
}

$visibility = $hide_mobile ? 'hidden lg:flex' : 'flex';
?>
<div class="<?php echo esc_attr($visibility); ?> relative h-72 items-center justify-center overflow-hidden bg-ink md:h-80 lg:h-96"
     aria-hidden="true">
    <?php if ($image) : ?>
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat lg:bg-fixed"
             style="background-image: url('<?php echo esc_url($image); ?>');"></div>
    <?php endif; ?>
    <h2 class="relative z-10 text-xl font-bold uppercase tracking-[0.3em] text-white drop-shadow-[0_2px_8px_rgba(0,0,0,0.8)] sm:text-2xl md:text-3xl">
        <?php echo esc_html($title); ?>
    </h2>
</div>
