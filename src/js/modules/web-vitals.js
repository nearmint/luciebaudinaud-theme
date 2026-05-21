/**
 * Web Vitals → Umami.
 *
 * Mesure LCP, CLS, INP, FCP, TTFB sur 10 % des sessions (random sampling)
 * et pousse les valeurs comme events custom dans Umami.
 *
 * Contraintes :
 *  - Chargement dynamique via import() + requestIdleCallback pour ne pas
 *    concurrencer le chemin critique (Alpine, PhotoSwipe).
 *  - Sampling 10 % : on a assez de données statistiques sans spammer.
 *  - Pas d'envoi si Umami n'est pas chargé (dev local, admins exclus).
 */

const SAMPLE_RATE = 0.1; // 10 % des sessions

function sendToUmami(metric) {
    if (typeof window === 'undefined') return;
    if (typeof window.umami === 'undefined' || typeof window.umami.track !== 'function') {
        return;
    }

    window.umami.track('web_vital', {
        name: metric.name,
        value: Math.round(metric.value),
        rating: metric.rating || 'unknown',
        navigationType: metric.navigationType || '',
    });
}

function loadVitals() {
    import('web-vitals')
        .then(({ onLCP, onCLS, onINP, onFCP, onTTFB }) => {
            onLCP(sendToUmami);
            onCLS(sendToUmami);
            onINP(sendToUmami);
            onFCP(sendToUmami);
            onTTFB(sendToUmami);
        })
        .catch(() => {
            // Silencieux : un échec d'import ne doit jamais casser la page.
        });
}

export function initWebVitals() {
    if (typeof window === 'undefined') return;

    // Sampling déterministe pour la session en cours.
    if (Math.random() > SAMPLE_RATE) return;

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(loadVitals, { timeout: 3000 });
    } else {
        // Safari < 17 : fallback sur setTimeout après onload.
        if (document.readyState === 'complete') {
            setTimeout(loadVitals, 1);
        } else {
            window.addEventListener('load', () => setTimeout(loadVitals, 1), { once: true });
        }
    }
}
