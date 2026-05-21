<?php
/**
 * Asset pipeline : enqueue des CSS/JS buildés par Vite.
 *
 * Architecture :
 *  - src/css/critical.css → bundle séparé, inliné dans <head> (priorité 1)
 *    pour éliminer le blocage de rendu above-the-fold.
 *  - src/css/app.css       → préchargé via `<link rel="preload" as="style"
 *    onload="this.rel='stylesheet'">` + <noscript> fallback. Non bloquant.
 *  - src/js/app.js         → enqueue standard WP, module ES, defer natif.
 *
 * En prod on lit dist/.vite/manifest.json pour résoudre les fichiers hashés.
 * En dev (manifest absent) : warning discret côté admin, rien n'est servi.
 *
 * Maintenance : si tu modifies le contenu above-the-fold (hero, nav en haut),
 * pense à mettre à jour src/css/critical.css.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Retourne le manifest Vite décodé, ou null si absent.
 *
 * @return array<string, array<string, mixed>>|null
 */
function lb3_vite_manifest(): ?array
{
    static $manifest = null;

    if ($manifest !== null) {
        return $manifest ?: null;
    }

    $path = LB3_DIR . '/dist/.vite/manifest.json';
    if (!is_readable($path)) {
        $manifest = false;
        return null;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    $manifest = is_array($decoded) ? $decoded : false;

    return $manifest ?: null;
}

/**
 * Chemin absolu (URL) vers un fichier du dossier dist/.
 */
function lb3_dist_url(string $relative): string
{
    return LB3_URI . '/dist/' . ltrim($relative, '/');
}

/**
 * Chemin disque absolu vers un fichier du dossier dist/ (pour file_get_contents).
 */
function lb3_dist_path(string $relative): string
{
    return LB3_DIR . '/dist/' . ltrim($relative, '/');
}

/**
 * Cherche un asset hashé dans dist/assets/ par motif glob.
 * Utile pour les fonts (fontsource) dont les URLs hashées ne sont pas dans le manifest.
 */
function lb3_find_asset_url(string $glob_pattern): ?string
{
    static $cache = [];
    if (isset($cache[$glob_pattern])) {
        return $cache[$glob_pattern];
    }

    $matches = glob(LB3_DIR . '/dist/assets/' . $glob_pattern);
    if (!$matches) {
        return $cache[$glob_pattern] = null;
    }

    // Stable : le premier match alphabétique (on ne veut pas d'ordre aléatoire).
    sort($matches);
    return $cache[$glob_pattern] = LB3_URI . '/dist/assets/' . basename($matches[0]);
}

/**
 * Récupère le fichier CSS hashé de app.css depuis le manifest Vite.
 */
function lb3_app_css_url(): ?string
{
    $manifest = lb3_vite_manifest();
    if (!$manifest) {
        return null;
    }

    $entry = $manifest['src/js/app.js'] ?? null;
    if (!$entry || empty($entry['css']) || !is_array($entry['css'])) {
        return null;
    }

    return lb3_dist_url((string) $entry['css'][0]);
}

/**
 * Récupère le chemin disque du critical.css buildé depuis le manifest.
 */
function lb3_critical_css_path(): ?string
{
    $manifest = lb3_vite_manifest();
    if (!$manifest) {
        return null;
    }

    $entry = $manifest['src/css/critical.css'] ?? null;
    if (!$entry || empty($entry['file'])) {
        return null;
    }

    $path = lb3_dist_path((string) $entry['file']);
    return is_readable($path) ? $path : null;
}

/**
 * 1. Inline critical.css dans <head> — priorité 1 pour passer AVANT
 *    tous les autres outputs wp_head (meta, preconnect, etc. viennent ensuite).
 *
 * Réécriture des URLs relatives : Vite produit `url(./font-xxx.woff2)` dans
 * critical.css (relatif au dossier `dist/assets/`). Une fois inliné dans le
 * HTML d'une page comme `/films/xxx/`, le navigateur résout ces URLs contre
 * le path de la page → 404. On les convertit donc en URLs absolues vers
 * `dist/assets/` avant d'émettre le <style>.
 */
add_action('wp_head', static function (): void {
    if (is_admin()) {
        return;
    }

    $path = lb3_critical_css_path();
    if (!$path) {
        return;
    }

    $css = (string) file_get_contents($path);
    if ($css === '') {
        return;
    }

    $assets_url = LB3_URI . '/dist/assets/';
    // Couvre url(./xxx), url("./xxx"), url('./xxx') — cas émis par Vite.
    $css = preg_replace(
        '#url\(\s*([\'"]?)\./#',
        'url($1' . $assets_url,
        $css
    );

    // On laisse le CSS brut : pas d'échappement (c'est du CSS, pas du HTML).
    // Contenu généré par Vite → source de confiance.
    echo "<style id=\"lb3-critical\">{$css}</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 1);

/**
 * 2. Preload hero cover (home) + police Montserrat 700 — priorité 2.
 *    Objectif : donner au browser un hint "high priority" sur le LCP.
 */
add_action('wp_head', static function (): void {
    if (is_admin()) {
        return;
    }

    // Font critique (Montserrat 700 latin) : utilisée sur tous les titres du site.
    // Le fichier woff2 est hashé par Vite ; on le retrouve via glob.
    $font_url = lb3_find_asset_url('montserrat-latin-700-normal-*.woff2');
    if ($font_url) {
        echo '<link rel="preload" href="' . esc_url($font_url) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }

    // Image hero : uniquement sur la home, fetchpriority=high pour booster le LCP.
    // On préload via imagesrcset pour que le browser pick la taille qui matche
    // le viewport — indispensable sur mobile pour éviter de tirer le 2048w
    // quand un 768w ou 1024w suffit.
    // Si des variantes AVIF ont été générées (cf. bin/convert-images.sh), on
    // préload en image/avif : ~30-50 % plus léger que JPG à qualité égale.
    // Les navigateurs sans support AVIF ignorent ce preload — aucun coût.
    if (is_front_page()) {
        $cover_url = (string) get_field('cover', lb3_get_fr_front_page_id());
        $cover_id  = $cover_url ? attachment_url_to_postid($cover_url) : 0;
        $cover_src = $cover_id ? wp_get_attachment_image_src($cover_id, 'full') : false;
        if ($cover_src) {
            $jpg_srcset  = (string) wp_get_attachment_image_srcset($cover_id, 'full');
            $avif_srcset = function_exists('lb3_alt_srcset')
                ? lb3_alt_srcset($jpg_srcset ?: $cover_src[0] . ' ' . $cover_src[1] . 'w', 'avif')
                : '';
            $avif_href   = function_exists('lb3_attachment_avif_url')
                ? lb3_attachment_avif_url($cover_id, 'full')
                : '';

            if ($avif_href !== '') {
                echo '<link rel="preload" as="image" type="image/avif"'
                    . ' href="' . esc_url($avif_href) . '"'
                    . ($avif_srcset ? ' imagesrcset="' . esc_attr($avif_srcset) . '"' : '')
                    . ' imagesizes="100vw"'
                    . ' fetchpriority="high">' . "\n";
            } else {
                echo '<link rel="preload" as="image"'
                    . ' href="' . esc_url($cover_src[0]) . '"'
                    . ($jpg_srcset ? ' imagesrcset="' . esc_attr($jpg_srcset) . '"' : '')
                    . ' imagesizes="100vw"'
                    . ' fetchpriority="high">' . "\n";
            }
        } elseif ($cover_url !== '') {
            echo '<link rel="preload" href="' . esc_url($cover_url) . '" as="image" fetchpriority="high">' . "\n";
        }
    }
}, 2);

/**
 * 3. Preload app.css (non bloquant) + fallback noscript — priorité 3.
 */
add_action('wp_head', static function (): void {
    if (is_admin()) {
        return;
    }

    $app_css = lb3_app_css_url();
    if (!$app_css) {
        return;
    }

    // Preload + swap au load (technique Filament Group, compatible Safari/Firefox/Chromium).
    echo '<link rel="preload" href="' . esc_url($app_css) . '" as="style" onload="this.onload=null;this.rel=&quot;stylesheet&quot;">' . "\n";
    echo '<noscript><link rel="stylesheet" href="' . esc_url($app_css) . '"></noscript>' . "\n";
}, 3);

/**
 * 4. JS principal.
 */
add_action('wp_enqueue_scripts', static function (): void {
    $manifest = lb3_vite_manifest();

    if ($manifest === null) {
        // Build absent : warning discret en admin uniquement.
        if (current_user_can('manage_options')) {
            add_action('wp_footer', static function (): void {
                echo '<!-- lb3: dist/.vite/manifest.json introuvable. Lancez `npm install && npm run build`. -->';
            });
        }
        return;
    }

    $entry_key = 'src/js/app.js';
    if (!isset($manifest[$entry_key])) {
        return;
    }

    $entry = $manifest[$entry_key];

    // JS principal — module ES, chargé en fin de body.
    wp_enqueue_script(
        'lb3-app',
        lb3_dist_url($entry['file']),
        [],
        null,
        true
    );

    // Imports dynamiques → modulepreload pour meilleure perf.
    if (!empty($entry['imports']) && is_array($entry['imports'])) {
        foreach ($entry['imports'] as $import_key) {
            if (isset($manifest[$import_key]['file'])) {
                $import_url = lb3_dist_url($manifest[$import_key]['file']);
                add_action('wp_head', static function () use ($import_url): void {
                    echo '<link rel="modulepreload" crossorigin href="' . esc_url($import_url) . '">' . "\n";
                });
            }
        }
    }
}, 10);

/**
 * Force le type="module" sur le bundle Vite.
 */
add_filter('script_loader_tag', static function (string $tag, string $handle): string {
    if ($handle !== 'lb3-app') {
        return $tag;
    }
    if (!str_contains($tag, 'type="module"')) {
        $tag = str_replace('<script ', '<script type="module" ', $tag);
    }
    return $tag;
}, 10, 2);

/**
 * Détecte si la page courante contient au moins une vidéo réellement embarquée
 * au rendu initial (single film avec iframe ACF).
 *
 * NB : sur la home les preconnects étaient déclenchés dès qu'un film portait
 * une URL vidéo, mais comme aucun <iframe> n'est présent au chargement
 * (facade click-to-load + modale), les connexions partaient en vain et PSI les
 * flaggait « unused preconnect ». On limite donc aux pages single.
 */
function lb3_has_video_on_page(): bool
{
    static $result = null;
    if ($result !== null) {
        return $result;
    }

    if (is_singular('post')) {
        return $result = function_exists('lb3_film_has_video')
            ? lb3_film_has_video(get_queried_object_id())
            : false;
    }

    return $result = false;
}

/**
 * Preconnect/DNS-prefetch pour YouTube/Vimeo — uniquement si la page a une vidéo.
 * Gain réel sur le premier play.
 */
add_action('wp_head', static function (): void {
    if (is_admin() || !lb3_has_video_on_page()) {
        return;
    }
    echo '<link rel="preconnect" href="https://player.vimeo.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://i.vimeocdn.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://www.youtube-nocookie.com" crossorigin>' . "\n";
    echo '<link rel="dns-prefetch" href="https://player.vimeo.com">' . "\n";
    echo '<link rel="dns-prefetch" href="https://www.youtube-nocookie.com">' . "\n";
}, 5);
