/**
 * Barre de progression du scroll (position relative dans la page).
 * Throttle via rAF. Recalcule aussi au resize (viewport ou contenu change).
 */
export function scrollProgress() {
    return {
        progress: 0,
        _ticking: false,
        init() {
            const compute = () => {
                if (this._ticking) return;
                this._ticking = true;
                window.requestAnimationFrame(() => {
                    const max = document.documentElement.scrollHeight - window.innerHeight;
                    this.progress = max > 0
                        ? Math.min(100, Math.max(0, (window.scrollY / max) * 100))
                        : 0;
                    this._ticking = false;
                });
            };
            window.addEventListener('scroll', compute, { passive: true });
            window.addEventListener('resize', compute, { passive: true });
            compute();
        },
    };
}
