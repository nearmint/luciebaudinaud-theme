<?php
/**
 * Façade vidéo : remplace tout <iframe> Vimeo / YouTube dans le contenu rendu
 * par un placeholder léger (vignette + bouton play). L'iframe réelle n'est
 * instanciée qu'au clic utilisateur (cf. src/js/modules/video-facade.js).
 *
 * Hooks :
 *  - the_content (priorité 30, après wpautop/shortcodes)
 *  - acf/format_value/type=wysiwyg (priorité 30)
 *
 * Le champ ACF dédié `iframe` (film-card / single) n'est pas transformé : son
 * HTML est sérialisé dans un attribut et injecté par la modale vidéo à la
 * demande.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Détecte plateforme + ID vidéo à partir d'un bloc <iframe>.
 *
 * @return array{platform:string,id:string}|null
 */
function lb3_parse_video_iframe(string $iframe_html): ?array
{
    if (!preg_match('#<iframe\b[^>]*\ssrc\s*=\s*["\']([^"\']+)["\']#i', $iframe_html, $m)) {
        return null;
    }
    $src = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);

    if (preg_match('#(?:youtube\.com|youtube-nocookie\.com)/embed/([a-zA-Z0-9_-]{6,20})#i', $src, $ym)
        || preg_match('#youtube\.com/watch\?[^"\']*v=([a-zA-Z0-9_-]{6,20})#i', $src, $ym)
        || preg_match('#youtu\.be/([a-zA-Z0-9_-]{6,20})#i', $src, $ym)
    ) {
        return ['platform' => 'youtube', 'id' => $ym[1]];
    }

    if (preg_match('#player\.vimeo\.com/video/(\d+)#i', $src, $vm)
        || preg_match('#vimeo\.com/(\d+)#i', $src, $vm)
    ) {
        return ['platform' => 'vimeo', 'id' => $vm[1]];
    }

    return null;
}

/**
 * URL de vignette pour une vidéo, avec cache 7 jours en transient.
 * Retourne une chaîne vide si aucune vignette n'est disponible.
 */
function lb3_get_video_thumbnail(string $platform, string $video_id): string
{
    $cache_key = 'lb3_vthumb_' . $platform . '_' . md5($video_id);
    $cached    = get_transient($cache_key);
    if (is_string($cached)) {
        return $cached;
    }

    $url = '';

    if ($platform === 'youtube') {
        // Toujours disponible pour les vidéos publiques, pas besoin d'appel API.
        $url = 'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg';
    } elseif ($platform === 'vimeo') {
        $oembed_url = 'https://vimeo.com/api/oembed.json?url=' . rawurlencode('https://vimeo.com/' . $video_id);
        $response   = wp_remote_get($oembed_url, ['timeout' => 5]);
        if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode((string) wp_remote_retrieve_body($response), true);
            if (is_array($body) && !empty($body['thumbnail_url'])) {
                $url = (string) $body['thumbnail_url'];
            }
        }
    }

    // On cache même l'échec (chaîne vide) pour ne pas tabasser l'API à chaque render.
    set_transient($cache_key, $url, 7 * DAY_IN_SECONDS);
    return $url;
}

/**
 * Construit le HTML placeholder pour une iframe donnée.
 */
function lb3_build_video_facade(string $iframe_html): string
{
    $meta  = lb3_parse_video_iframe($iframe_html);
    $thumb = $meta ? lb3_get_video_thumbnail($meta['platform'], $meta['id']) : '';

    $title = '';
    if (preg_match('#<iframe\b[^>]*\stitle\s*=\s*["\']([^"\']+)["\']#i', $iframe_html, $tm)) {
        $title = html_entity_decode($tm[1], ENT_QUOTES | ENT_HTML5);
    }
    if ($title === '') {
        $title = __('Lire la vidéo', 'lb3');
    }

    ob_start();
    ?>
    <div class="lb-video-facade relative my-6 aspect-video overflow-hidden bg-black"
         x-data="videoFacade"
         data-iframe-html="<?php echo esc_attr($iframe_html); ?>">
        <button type="button"
                x-show="!loaded"
                @click="load()"
                class="group relative block h-full w-full cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink"
                aria-label="<?php echo esc_attr($title); ?>">
            <?php if ($thumb !== '') : ?>
                <img src="<?php echo esc_url($thumb); ?>"
                     alt=""
                     loading="lazy"
                     decoding="async"
                     class="absolute inset-0 h-full w-full object-cover">
            <?php endif; ?>
            <span class="absolute inset-0 bg-ink/30 transition group-hover:bg-ink/20" aria-hidden="true"></span>
            <span class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur-sm transition group-hover:bg-white/30">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M8 5.14v14l11-7-11-7z"/></svg>
                </span>
            </span>
        </button>
        <div x-show="loaded" x-cloak x-ref="container" class="absolute inset-0 [&>iframe]:h-full [&>iframe]:w-full"></div>
    </div>
    <?php
    return (string) ob_get_clean();
}

/**
 * Remplace les iframes vidéo dans un bloc HTML par des façades.
 * Ignore les iframes non-vidéo (maps, audio players tiers, etc.).
 */
function lb3_replace_video_iframes(string $html): string
{
    if ($html === '' || stripos($html, '<iframe') === false) {
        return $html;
    }

    return (string) preg_replace_callback(
        '#<iframe\b[^>]*>[\s\S]*?</iframe>#i',
        static function (array $m): string {
            $iframe = $m[0];
            if (lb3_parse_video_iframe($iframe) === null) {
                return $iframe;
            }
            return lb3_build_video_facade($iframe);
        },
        $html
    );
}

add_filter('the_content', 'lb3_replace_video_iframes', 30);

/**
 * Applique aussi la façade aux WYSIWYG ACF — sauf au champ `iframe` qui est
 * consommé tel quel par la modale vidéo (film-card / single).
 */
add_filter('acf/format_value/type=wysiwyg', static function ($value, $post_id, $field) {
    if (!is_string($value) || $value === '') {
        return $value;
    }
    if (isset($field['name']) && $field['name'] === 'iframe') {
        return $value;
    }
    return lb3_replace_video_iframes($value);
}, 30, 3);
