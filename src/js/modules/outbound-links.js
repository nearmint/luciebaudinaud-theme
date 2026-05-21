/**
 * Tracking des clics sur liens sortants → Umami.
 *
 * - Listener délégué au document : zéro binding par lien (performant + fonctionne
 *   pour les nœuds injectés dynamiquement : modale vidéo, PhotoSwipe, etc.).
 * - Event custom `social_click` avec { platform } pour IMDb / Vimeo / Instagram.
 * - Event custom `outbound_click` avec { url, hostname } pour le reste.
 * - Ignore les liens internes, les ancres (#), mailto/tel/javascript.
 */

const SOCIAL_HOSTS = {
    'imdb.com':            'imdb',
    'www.imdb.com':        'imdb',
    'm.imdb.com':          'imdb',
    'vimeo.com':           'vimeo',
    'www.vimeo.com':       'vimeo',
    'player.vimeo.com':    'vimeo',
    'instagram.com':       'instagram',
    'www.instagram.com':   'instagram',
};

function classifyLink(href) {
    if (!href) return null;
    const trimmed = href.trim();
    if (trimmed.startsWith('#')
        || trimmed.startsWith('mailto:')
        || trimmed.startsWith('tel:')
        || trimmed.toLowerCase().startsWith('javascript:')) {
        return null;
    }

    let url;
    try {
        url = new URL(trimmed, window.location.origin);
    } catch {
        return null;
    }

    if (url.hostname === window.location.hostname) return null;

    const hostname = url.hostname.toLowerCase();
    const platform = SOCIAL_HOSTS[hostname] || null;

    return { url: url.toString(), hostname, platform };
}

export function initOutboundTracking() {
    if (typeof window === 'undefined' || typeof document === 'undefined') return;

    document.addEventListener('click', (event) => {
        // Uniquement clic gauche sans modificateurs (les Cmd/Ctrl+clic ouvrent dans
        // un nouvel onglet → on les tracke aussi pour ne rien perdre).
        if (event.button !== undefined && event.button > 1) return;

        const anchor = event.target && event.target.closest
            ? event.target.closest('a[href]')
            : null;
        if (!anchor) return;

        const info = classifyLink(anchor.getAttribute('href'));
        if (!info) return;

        if (typeof window.umami === 'undefined' || typeof window.umami.track !== 'function') {
            return;
        }

        if (info.platform) {
            window.umami.track('social_click', {
                platform: info.platform,
                url: info.url,
            });
        } else {
            window.umami.track('outbound_click', {
                url: info.url,
                hostname: info.hostname,
            });
        }
    }, { passive: true, capture: true });
}
