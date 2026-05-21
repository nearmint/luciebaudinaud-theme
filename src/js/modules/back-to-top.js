/**
 * Bouton flottant "Retour en haut".
 * Apparaît au-delà de 600 px de scroll, throttle via rAF.
 * Scroll instantané si prefers-reduced-motion, sinon smooth.
 */
export function backToTop() {
    return {
        visible: false,
        _ticking: false,
        init() {
            const compute = () => {
                if (this._ticking) return;
                this._ticking = true;
                window.requestAnimationFrame(() => {
                    this.visible = window.scrollY > 600;
                    this._ticking = false;
                });
            };
            window.addEventListener('scroll', compute, { passive: true });
            compute();
        },
        scrollToTop() {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
        },
    };
}
