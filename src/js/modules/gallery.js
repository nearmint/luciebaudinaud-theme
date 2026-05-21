/**
 * PhotoSwipe v5 — initialisation sur un conteneur donné.
 *
 * Markup attendu : <div id="X"><a href="FULL" data-pswp-width data-pswp-height>…</a></div>
 *
 * PhotoSwipe gère nativement :
 *  - clavier (flèches, ESC, Tab)
 *  - touch (swipe, pinch zoom)
 *  - focus trap
 *  - ARIA
 */
import PhotoSwipeLightbox from 'photoswipe/lightbox';

export function initGallery(selector, options = {}) {
    const el = document.querySelector(selector);
    if (!el) {
        return null;
    }

    const { showCaption = true } = options;

    const lightbox = new PhotoSwipeLightbox({
        gallery: selector,
        children: 'a',
        pswpModule: () => import('photoswipe'),
        bgOpacity: 0.95,
        showHideAnimationType: 'fade',
        loop: true,
    });

    // Caption (titre de l'image) — désactivé sur les galeries sans légende
    // (ex. photogrammes du single film).
    if (showCaption) {
        lightbox.on('uiRegister', () => {
            lightbox.pswp.ui.registerElement({
                name: 'caption',
                order: 9,
                isButton: false,
                appendTo: 'root',
                html: '',
                onInit: (captionEl, pswp) => {
                    pswp.on('change', () => {
                        const currSlide = pswp.currSlide;
                        if (!currSlide) return;
                        const captionText = currSlide.data.element?.getAttribute('data-pswp-caption') || '';
                        captionEl.innerHTML = captionText;
                    });
                },
            });
        });
    }

    lightbox.init();
    return lightbox;
}
