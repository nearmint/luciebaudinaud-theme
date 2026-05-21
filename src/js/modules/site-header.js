/**
 * Sticky header (home) — visible uniquement quand le hero #intro n'est pas visible.
 *
 * Pourquoi IntersectionObserver plutôt que scroll listener : pas de repaint par frame,
 * une seule détection d'entrée/sortie du hero, pas de math sur scrollY.
 *
 * Respecte prefers-reduced-motion via les transitions CSS (pas ici).
 */
export function siteHeader() {
    return {
        visible: false,
        _observer: null,
        _resize: null,

        init() {
            // Synchronise --lb-header-h avec la hauteur réelle du header (desktop ≠ mobile
            // à cause du burger h-10). La barre de filtres films s'appuie dessus pour
            // coller sans gap au bas du header.
            this._syncHeaderHeight();
            if (typeof ResizeObserver !== 'undefined') {
                this._resize = new ResizeObserver(() => this._syncHeaderHeight());
                this._resize.observe(this.$el);
            }

            const sentinel = document.getElementById('intro');
            if (!sentinel) {
                // Pas de hero (ex. page autre que home) : on reste visible.
                this.visible = true;
                return;
            }

            this._observer = new IntersectionObserver(
                (entries) => {
                    for (const entry of entries) {
                        // visible = le hero n'est PAS (plus) dans le viewport.
                        this.visible = !entry.isIntersecting;
                    }
                },
                { rootMargin: '-64px 0px 0px 0px', threshold: 0 }
            );
            this._observer.observe(sentinel);
        },

        _syncHeaderHeight() {
            const h = this.$el.offsetHeight;
            if (h > 0) {
                document.documentElement.style.setProperty('--lb-header-h', h + 'px');
            }
        },

        destroy() {
            if (this._observer) {
                this._observer.disconnect();
                this._observer = null;
            }
            if (this._resize) {
                this._resize.disconnect();
                this._resize = null;
            }
        },
    };
}
