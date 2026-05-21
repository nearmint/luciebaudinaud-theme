/**
 * Tracking Umami manuel.
 *
 * Le script Umami est chargé avec data-auto-track="false" (voir inc/umami.php)
 * pour éviter que son listener click en capture phase force la navigation
 * sur les <a> qui pointent vers des fichiers (jpg, pdf, etc.), ce qui
 * casse PhotoSwipe.
 *
 * On fait donc :
 * - pageview manuel au chargement
 * - events délégués en bubble phase passive (n'intercepte rien)
 */

export function trackPageview() {
    if (typeof window.umami === 'undefined') return;
    try { window.umami.track(); } catch (e) {}
}

export function trackEvent(name, data = {}) {
    if (typeof window.umami === 'undefined') return;
    try { window.umami.track(name, data); } catch (e) {}
}

// Polling toutes les 250 ms, 20 tentatives max (5 s). Le script Umami est
// chargé en `defer` → disponible peu après le parsing HTML.
function waitForUmami(cb, attempts = 0) {
    if (typeof window.umami !== 'undefined') {
        cb();
        return;
    }
    if (attempts >= 20) return;
    setTimeout(() => waitForUmami(cb, attempts + 1), 250);
}

export function initAnalytics() {
    if (typeof window === 'undefined' || typeof document === 'undefined') return;

    waitForUmami(() => trackPageview());

    // Délégation bubble + passive : on observe, on ne bloque rien.
    document.addEventListener('click', (e) => {
        const el = e.target && e.target.closest
            ? e.target.closest('[data-umami-event]')
            : null;
        if (!el) return;

        const eventName = el.getAttribute('data-umami-event');
        if (!eventName) return;

        const data = {};
        for (const attr of el.attributes) {
            if (attr.name.startsWith('data-umami-event-') && attr.name !== 'data-umami-event') {
                const key = attr.name.slice('data-umami-event-'.length);
                data[key] = attr.value;
            }
        }
        trackEvent(eventName, data);
    }, { passive: true, capture: false });
}
