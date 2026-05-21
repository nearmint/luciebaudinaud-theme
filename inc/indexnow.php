<?php
/**
 * IndexNow — notification de Bing / Yandex à la publication d'un post.
 *
 * Principe :
 *   1. Une clé UUID est générée et stockée dans l'option `lb3_indexnow_key`.
 *   2. Cette clé est servie en texte brut à l'URL `/{key}.txt` (vérification du
 *      protocole par les moteurs).
 *   3. À chaque `transition_post_status` → `publish` (post_type=post), on POST
 *      l'URL du film vers https://api.indexnow.org/indexnow (non bloquant).
 *
 * Spec : https://www.indexnow.org/documentation
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const LB3_INDEXNOW_OPTION   = 'lb3_indexnow_key';
const LB3_INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';

/**
 * Retourne la clé IndexNow, en la générant si absente.
 * Format : 32 caractères hexadécimaux (UUID sans tirets, accepté par la spec).
 */
function lb3_indexnow_key(): string
{
    $key = (string) get_option(LB3_INDEXNOW_OPTION, '');
    if ($key !== '' && preg_match('#^[a-f0-9]{8,128}$#i', $key)) {
        return $key;
    }

    $key = str_replace('-', '', wp_generate_uuid4());
    update_option(LB3_INDEXNOW_OPTION, $key, false);
    return $key;
}

/**
 * URL publique du fichier de vérification.
 */
function lb3_indexnow_key_location(): string
{
    return home_url('/' . lb3_indexnow_key() . '.txt');
}

/**
 * Sert `/{key}.txt` en texte brut sans dépendre d'une rewrite rule
 * (plus simple : on reconnaît l'URL tôt dans la requête).
 */
add_action('parse_request', static function (\WP $wp): void {
    $request = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path    = (string) parse_url($request, PHP_URL_PATH);
    if ($path === '' || !preg_match('#^/([a-f0-9]{8,128})\.txt$#i', $path, $m)) {
        return;
    }

    $stored = lb3_indexnow_key();
    if (!hash_equals(strtolower($stored), strtolower($m[1]))) {
        // Clé non reconnue : on laisse WP continuer (404 standard).
        return;
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    echo $stored;
    exit;
});

/**
 * Notifie IndexNow à chaque passage d'un post en "publish".
 * Fire-and-forget : blocking=false, timeout court.
 */
add_action('transition_post_status', static function (string $new_status, string $old_status, \WP_Post $post): void {
    if ($new_status !== 'publish') {
        return;
    }
    if ($post->post_type !== 'post') {
        return;
    }
    if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) {
        return;
    }

    $url = (string) get_permalink($post);
    if ($url === '') {
        return;
    }

    $key  = lb3_indexnow_key();
    $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    if ($host === '') {
        return;
    }

    $payload = [
        'host'        => $host,
        'key'         => $key,
        'keyLocation' => lb3_indexnow_key_location(),
        'urlList'     => [$url],
    ];

    wp_remote_post(LB3_INDEXNOW_ENDPOINT, [
        'headers'  => ['Content-Type' => 'application/json; charset=utf-8'],
        'body'     => wp_json_encode($payload),
        'blocking' => false,
        'timeout'  => 3,
    ]);
}, 10, 3);
