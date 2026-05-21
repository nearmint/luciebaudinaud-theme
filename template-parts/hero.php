<?php
/**
 * Hero — plein écran avec image de fond + nav ancres.
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

$home_id   = lb3_get_fr_front_page_id();
$cover_url = (string) get_field('cover', $home_id);
$home_url  = lb3_home_url_localized();

// LCP : on sert l'image hero comme un vrai <picture>/<img> (srcset +
// fetchpriority) plutôt qu'un background-image. Permet au browser de préloader
// la bonne taille via <link rel=preload imagesrcset> dans <head> (inc/assets.php).
// Le helper émet <picture> avec AVIF/WebP si les fichiers jumeaux existent sur
// disque (cf. bin/convert-images.sh), sinon <img> JPG classique.
$cover_id = $cover_url ? attachment_url_to_postid($cover_url) : 0;
?>
<section id="intro"
         class="relative flex min-h-svh flex-col overflow-hidden bg-ink text-white">

    <?php if ($cover_id) : ?>
        <?php lb3_the_attachment_picture($cover_id, 'full', [
            'class'         => 'absolute inset-0 h-full w-full object-cover object-center',
            'sizes'         => '100vw',
            'alt'           => '',
            'aria-hidden'   => 'true',
            'decoding'      => 'async',
            'fetchpriority' => 'high',
        ]); ?>
    <?php elseif ($cover_url) : ?>
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
             style="background-image: url('<?php echo esc_url($cover_url); ?>');"
             aria-hidden="true"></div>
    <?php endif; ?>

    <?php
    // Switch langue : ancré dans le hero (absolute) → disparaît au scroll quand
    // le header sticky prend le relais avec son propre switcher inline.
    get_template_part('template-parts/lang-switcher');
    ?>

    <div class="relative z-10 flex flex-1 flex-col">
        <nav class="pt-8" aria-label="<?php esc_attr_e('Navigation principale', 'lb3'); ?>">
            <ul class="flex flex-wrap justify-center gap-6 text-xs font-bold uppercase tracking-[0.1em] drop-shadow-[0_2px_8px_rgba(0,0,0,0.8)] sm:gap-8">
                <li><a href="#films" class="border-b-2 border-transparent pb-0.5 transition hover:border-white focus-visible:border-white focus-visible:outline-none"><?php esc_html_e('Films', 'lb3'); ?></a></li>
                <li><a href="#photos" class="border-b-2 border-transparent pb-0.5 transition hover:border-white focus-visible:border-white focus-visible:outline-none"><?php esc_html_e('Photos', 'lb3'); ?></a></li>
                <li><a href="#cv" class="border-b-2 border-transparent pb-0.5 transition hover:border-white focus-visible:border-white focus-visible:outline-none"><?php esc_html_e('CV', 'lb3'); ?></a></li>
                <li><a href="#contact" class="border-b-2 border-transparent pb-0.5 transition hover:border-white focus-visible:border-white focus-visible:outline-none"><?php esc_html_e('Contact', 'lb3'); ?></a></li>
            </ul>
        </nav>

        <div class="flex flex-1 items-center justify-center px-4 text-center">
            <div class="max-w-3xl">
                <h1 class="text-2xl font-bold uppercase tracking-[0.06em] drop-shadow-[0_2px_8px_rgba(0,0,0,0.8)] sm:text-3xl md:text-4xl">
                    <?php esc_html_e('Lucie Baudinaud, AFC', 'lb3'); ?>
                </h1>
                <p class="mt-3 text-base font-bold uppercase tracking-[0.1em] drop-shadow-[0_2px_8px_rgba(0,0,0,0.8)] sm:text-lg md:text-xl">
                    <?php esc_html_e('Directrice de la photographie', 'lb3'); ?>
                </p>
            </div>
        </div>

        <a href="#films"
           class="mx-auto mb-8 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/40 text-white/80 drop-shadow-[0_2px_8px_rgba(0,0,0,0.8)] transition hover:border-white hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
           aria-label="<?php esc_attr_e('Descendre vers les films', 'lb3'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
        </a>
    </div>
</section>
