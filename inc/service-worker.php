<?php
/**
 * Kill switch pour le Service Worker.
 *
 * Le module Service Worker a été retiré du thème (source de bugs prod sur
 * les mises à jour de bundle : le SW servait du HTML cache pointant vers
 * des chunks JS disparus après chaque build → `PhotoSwipeLightbox is not
 * defined` sur les galeries).
 *
 * Ce fichier reste en place temporairement pour :
 *   1. Désinscrire côté client les SW installés par les versions précédentes
 *      du thème (script injecté dans le footer).
 *   2. Servir à /sw.js une version "suicide" du worker (voir src/sw/sw.js),
 *      afin que les navigateurs qui font leur update check récupèrent un
 *      worker qui se désinscrit lui-même au prochain `activate`. Si on
 *      répondait 404 ici, l'update check échouerait et le vieux SW
 *      resterait actif indéfiniment.
 *
 * À supprimer définitivement (ce fichier + la référence dans functions.php
 * + src/sw/sw.js + la route /sw.js dans vite.config.js) dans 2-3 mois, une
 * fois que la majorité des visiteurs récurrents auront rechargé le site
 * au moins une fois.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Intercepte /sw.js avant que WP ne parte en 404, pour servir la version
 * suicide buildée dans dist/sw.js. Tant que cette route répond, les vieux
 * SW déclenchent leur update check → install → activate → self-unregister.
 */
add_action('parse_request', static function (\WP $wp): void {
    $request = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path    = (string) parse_url($request, PHP_URL_PATH);

    if ($path !== '/sw.js') {
        return;
    }

    $file = LB3_DIR . '/dist/sw.js';
    if (!is_readable($file)) {
        return; // pas buildé → WP continue, 404 standard
    }

    header('Content-Type: application/javascript; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    readfile($file);
    exit;
});

/**
 * Inject le kill switch côté client. Au prochain chargement, tout visiteur
 * ayant un SW actif (installé par une ancienne version du thème) se le voit
 * désinscrire et ses caches `lb3-*` purgés.
 */
add_action('wp_footer', static function (): void {
    if (is_admin()) {
        return;
    }
    ?>
    <script>
    (function () {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function (regs) {
                regs.forEach(function (reg) { reg.unregister(); });
            }).catch(function () {});
        }
        if (window.caches && typeof caches.keys === 'function') {
            caches.keys().then(function (keys) {
                keys.forEach(function (k) { caches.delete(k); });
            }).catch(function () {});
        }
    })();
    </script>
    <?php
}, 99);
