<?php
/**
 * Umami analytics — chargement du script.
 *
 * Site ID public : 1af9b296-c131-4967-a38a-802d148809ea.
 * Script officiel : https://cloud.umami.is/script.js.
 *
 * `data-auto-track="false"` désactive l'auto-tracking interne du script :
 * plus de listener click en capture phase sur `document` qui forçait
 * `location.href` après beacon sur les liens vers fichiers (jpg, pdf...),
 * ce qui contournait `preventDefault` et cassait PhotoSwipe.
 *
 * Le tracking (pageview + events) est fait manuellement depuis
 * `src/js/modules/analytics.js` via `window.umami.track()`. Les events
 * restent déclarés dans le HTML via `data-umami-event` / `data-umami-event-*`.
 *
 * Pas de cookies, pas d'IP stockée → zéro bandeau RGPD requis.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const LB3_UMAMI_SITE_ID = '1af9b296-c131-4967-a38a-802d148809ea';
const LB3_UMAMI_SCRIPT  = 'https://cloud.umami.is/script.js';

add_action('wp_head', static function (): void {
    // Ne pas tracker les admins connectés (bruit analytics).
    if (is_user_logged_in() && current_user_can('edit_posts')) {
        return;
    }

    // Ne pas tracker en local/dev.
    if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE !== 'production') {
        return;
    }

    printf(
        '<script defer src="%s" data-website-id="%s" data-auto-track="false"></script>' . "\n",
        esc_url(LB3_UMAMI_SCRIPT),
        esc_attr(LB3_UMAMI_SITE_ID)
    );
}, 99);
