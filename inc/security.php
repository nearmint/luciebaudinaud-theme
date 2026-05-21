<?php
/**
 * Durcissement runtime côté thème.
 *
 * Inventaire :
 *   - masque la version WP dans les URL d'assets (évite le fingerprinting)
 *   - désactive XML-RPC
 *   - redirige ?author= et /author/{slug}/ (énumération users)
 *   - désactive l'édition de fichiers depuis l'admin
 *   - verrouille la REST API pour les anonymes (whitelist explicite)
 *   - envoie les headers de sécurité (CSP, X-Content-Type-Options, etc.)
 *   - rate-limit fallback sur wp-login (bascule sur plugin dédié si présent)
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// Masque la version WP dans les URL d'assets.
add_filter('style_loader_src', 'lb3_strip_ver', 9999);
add_filter('script_loader_src', 'lb3_strip_ver', 9999);

function lb3_strip_ver(string $src): string
{
    if (str_contains($src, 'ver=' . get_bloginfo('version'))) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}

// Désactive XML-RPC (attaqué en masse, jamais utilisé ici).
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Empêche l'énumération des users :
 *   - ?author=1 → 301 home
 *   - /author/{slug}/ → 301 home
 * Le site n'affiche pas d'archives auteur, pas d'usage légitime.
 */
add_action('template_redirect', static function (): void {
    if (is_admin()) {
        return;
    }
    if (isset($_GET['author']) || is_author()) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
});

// Désactive l'édition de fichiers depuis l'admin (si pas déjà fait dans wp-config.php).
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

// ─────────────────────────────────────────────────────────
// REST API — lockdown pour anonymes
// ─────────────────────────────────────────────────────────

/**
 * On ferme par défaut l'ensemble de la REST API pour les utilisateurs non connectés,
 * et on whitelist uniquement les endpoints publics :
 *   - GET /wp/v2/posts et /wp/v2/posts/{id}   → lecture films (usage futur : RSS, feeds externes)
 *   - GET /wp/v2/pages et /wp/v2/pages/{id}   → lecture pages légales
 *   - /oembed/*                               → oEmbed (requis pour certains partages)
 *
 * Tout le reste (users, comments, settings, media, plugins…) renvoie 401 côté anon.
 * Rationale : zéro surface d'attaque sur un site vitrine qui n'a aucun front-end
 * consommant la REST autre que les embeds. Les plugins qui ont besoin de la REST
 * (Gutenberg, ACF interne…) fonctionnent car l'admin est toujours connectée.
 */
add_filter('rest_pre_dispatch', static function ($result, \WP_REST_Server $server, \WP_REST_Request $request) {
    if ($result !== null) {
        // Déjà une réponse / erreur préparée par un autre filtre : ne pas interférer.
        return $result;
    }
    if (is_user_logged_in()) {
        return $result;
    }

    $route  = (string) $request->get_route();
    $method = strtoupper((string) $request->get_method());

    // Whitelist d'endpoints publics (route regex, méthodes autorisées).
    $allowed = [
        ['GET', '#^/wp/v2/posts(?:/\d+)?$#'],
        ['GET', '#^/wp/v2/pages(?:/\d+)?$#'],
        ['GET', '#^/oembed/#'],
    ];

    foreach ($allowed as [$allowed_method, $pattern]) {
        if ($allowed_method === $method && preg_match($pattern, $route)) {
            return $result;
        }
    }

    return new \WP_Error(
        'rest_forbidden',
        __('Accès à la REST API réservé aux utilisateurs authentifiés.', 'lb3'),
        ['status' => 401]
    );
}, 10, 3);

// ─────────────────────────────────────────────────────────
// Headers de sécurité (HTML front uniquement)
// ─────────────────────────────────────────────────────────

/**
 * Policy CSP — principes :
 *   - 'self' pour JS/CSS/fonts (tout est self-hosted sauf Umami + embeds)
 *   - 'unsafe-inline' sur script-src : nécessaire pour
 *       • le `onload=` du preload CSS (inc/assets.php)
 *       • le petit script de tracking 404 (404.php)
 *       • les directives Alpine (x-on, @click) — parsées côté Alpine, pas inline JS au sens CSP
 *   - 'unsafe-eval' sur script-src : Alpine.js utilise `new Function()` pour évaluer ses
 *     directives (`x-show`, `@click`, `:class`…). Passer au build `@alpinejs/csp`
 *     imposerait un refactor profond de 8+ composants pour n'autoriser que des
 *     références de méthodes. Compromis acceptable : le site ne rend aucun HTML
 *     venant d'input utilisateur — seules les sources de scripts listées ci-dessous
 *     peuvent émettre du code, donc la surface d'injection est nulle en pratique.
 *   - 'unsafe-inline' sur style-src : Tailwind v4 inline via @theme + <style id="lb3-critical">
 *   - img-src large (data: + https:) : attachments WP + thumbnails Vimeo/YouTube hébergés ailleurs
 *   - frame-src limité aux trois origines vidéo (player.vimeo, youtube, youtube-nocookie)
 *
 * Mode : `Content-Security-Policy-Report-Only` par défaut. Bascule en enforcement
 * strict via la constante `LB3_CSP_ENFORCE = true` (wp-config.php) après validation
 * d'une semaine de reports console sans violation.
 */
function lb3_build_csp(): string
{
    $directives = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.umami.is https://*.umami.dev https://player.vimeo.com https://www.youtube.com",
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data: https:",
        "font-src 'self'",
        "frame-src https://player.vimeo.com https://www.youtube.com https://www.youtube-nocookie.com",
        "connect-src 'self' https://*.umami.is https://*.umami.dev",
        "base-uri 'self'",
        "form-action 'self'",
    ];
    return implode('; ', $directives);
}

add_action('send_headers', static function (): void {
    if (is_admin()) {
        return;
    }

    // CSP — report-only par défaut ; enforcement via constante.
    $csp_header = (defined('LB3_CSP_ENFORCE') && LB3_CSP_ENFORCE)
        ? 'Content-Security-Policy'
        : 'Content-Security-Policy-Report-Only';
    header($csp_header . ': ' . lb3_build_csp());

    // Anti-sniffing MIME.
    header('X-Content-Type-Options: nosniff');

    // Referrer : envoie l'origine sur cross-site HTTPS→HTTPS, rien sur HTTPS→HTTP.
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions-Policy : coupe les API sensibles jamais utilisées ici.
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

    // Empêche l'iframing du site depuis un autre domaine (clickjacking).
    header('X-Frame-Options: SAMEORIGIN');
});

// ─────────────────────────────────────────────────────────
// Login rate-limit — fallback si plugin absent
// ─────────────────────────────────────────────────────────

/**
 * Recommandation production : activer le plugin « Limit Login Attempts Reloaded ».
 * Fallback ci-dessous : bloque une IP après 5 échecs en 15 minutes, uniquement si
 * aucun plugin de rate-limit détecté. Protection rudimentaire mais utile contre
 * les bruteforce automatisés sur /wp-login.php.
 */
function lb3_login_limiter_plugin_present(): bool
{
    return class_exists('Limit_Login_Attempts')
        || class_exists('LLAR\\Limit_Login_Attempts')
        || function_exists('limit_login_setup_cookie')
        || defined('LIMIT_LOGIN_DIRECT_ADDR');
}

/**
 * IP client — on reste strict (REMOTE_ADDR uniquement, pas d'en-têtes X-Forwarded-*
 * qui sont trivialement falsifiables en l'absence de proxy de confiance).
 */
function lb3_client_ip(): string
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

function lb3_login_fail_transient(string $ip): string
{
    return 'lb3_login_fail_' . md5($ip);
}

add_action('wp_login_failed', static function (string $username): void {
    if (lb3_login_limiter_plugin_present()) {
        return;
    }
    $ip = lb3_client_ip();
    if ($ip === '') {
        return;
    }
    $key   = lb3_login_fail_transient($ip);
    $count = (int) get_transient($key);
    set_transient($key, $count + 1, 15 * MINUTE_IN_SECONDS);
});

add_filter('authenticate', static function ($user, string $username, string $password) {
    if (lb3_login_limiter_plugin_present()) {
        return $user;
    }
    // Laisse passer les requêtes vides (chargement du formulaire).
    if ($username === '' && $password === '') {
        return $user;
    }
    $ip = lb3_client_ip();
    if ($ip === '') {
        return $user;
    }
    $count = (int) get_transient(lb3_login_fail_transient($ip));
    if ($count >= 5) {
        return new \WP_Error(
            'lb3_too_many_login_attempts',
            __('Trop de tentatives de connexion. Réessaie dans 15 minutes.', 'lb3')
        );
    }
    return $user;
}, 30, 3);

/**
 * Remise à zéro du compteur sur une connexion réussie.
 */
add_action('wp_login', static function (): void {
    if (lb3_login_limiter_plugin_present()) {
        return;
    }
    $ip = lb3_client_ip();
    if ($ip === '') {
        return;
    }
    delete_transient(lb3_login_fail_transient($ip));
});
