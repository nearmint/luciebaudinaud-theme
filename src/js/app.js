/**
 * lb3 — JavaScript entry point.
 *
 * Orchestre :
 *  - Alpine.js (interactions déclaratives : filtres films, modale vidéo, nav mobile)
 *  - PhotoSwipe (lightbox galerie home + single film)
 *
 * Note : le CSS est importé ici pour que Vite le traite via le même manifest
 *        (une seule entry "app.js" → un JS + un CSS dans dist/).
 */

import '../css/app.css';

import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';

import { filmsFilter } from './modules/films-filter.js';
import { videoModal } from './modules/video-modal.js';
import { videoFacade } from './modules/video-facade.js';
import { initGallery } from './modules/gallery.js';
import { siteHeader } from './modules/site-header.js';
import { backToTop } from './modules/back-to-top.js';
import { scrollProgress } from './modules/scroll-progress.js';
import { initWebVitals } from './modules/web-vitals.js';
import { initOutboundTracking } from './modules/outbound-links.js';
import { initScrollDepth } from './modules/scroll-depth.js';
import { initAnalytics } from './modules/analytics.js';

// Les `<script type="module">` sont implicitement `defer` : app.js peut
// s'exécuter après DOMContentLoaded. Un listener posé ici ne se déclenchera
// alors jamais. Ce helper fait une vérif synchrone sur readyState.
function whenReady(cb) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', cb, { once: true });
    } else {
        cb();
    }
}

// ─────────────────────────────────────────────────────────
// Alpine
// ─────────────────────────────────────────────────────────
Alpine.plugin(intersect);
Alpine.data('filmsFilter', filmsFilter);
Alpine.data('videoModal', videoModal);
Alpine.data('videoFacade', videoFacade);
Alpine.data('siteHeader', siteHeader);
Alpine.data('backToTop', backToTop);
Alpine.data('scrollProgress', scrollProgress);

window.Alpine = Alpine;
Alpine.start();

// ─────────────────────────────────────────────────────────
// PhotoSwipe — init sur tous les conteneurs présents
// ─────────────────────────────────────────────────────────
whenReady(() => {
    initGallery('#lb-photoswipe-gallery'); // home — légendes visibles
    initGallery('#lb-photoswipe-film-gallery', { showCaption: false }); // single film — photogrammes sans légende
});

// ─────────────────────────────────────────────────────────
// Modules analytics : tous déférés derrière requestIdleCallback pour sortir
// du main thread pendant le startup (Alpine.start() est déjà synchrone et
// lourd sur la home). Fallback setTimeout(2000 ms) couvre Safari et tout
// contexte où le browser ne prend jamais d'idle (onglet caché, low-power).
// ─────────────────────────────────────────────────────────
function deferIdle(cb, fallbackMs = 2000) {
    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(cb, { timeout: fallbackMs });
    } else {
        setTimeout(cb, fallbackMs);
    }
}

deferIdle(() => {
    initAnalytics();        // Umami pageview + event delegation
    initOutboundTracking(); // → social_click / outbound_click
    initScrollDepth();      // home + pages légales
    initWebVitals();        // LCP/CLS/INP/FCP/TTFB → Umami
});
