/**
 * Modale vidéo singleton.
 *
 * Écoute l'event global `lb:open-video` (détail : { html: string }).
 * Injecte l'iframe à l'ouverture (lazy) — évite de charger les embeds au pageload.
 * Vide l'iframe à la fermeture — stoppe la lecture et libère la mémoire.
 *
 * Accessibilité :
 *  - role="dialog" + aria-modal sur le wrapper (dans render.php)
 *  - focus trap réel : Tab et Shift+Tab cyclent uniquement dans la modale,
 *    l'iframe est elle-même tabbable → la lecture vidéo reste pilotable clavier
 *  - ESC ferme (listener dans render.php)
 *  - click extérieur ferme
 *  - scroll body bloqué pendant l'ouverture
 *  - restitution du focus sur le trigger d'origine à la fermeture
 */

const TABBABLE_SELECTOR = [
    'a[href]',
    'area[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    'iframe',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

export function videoModal() {
    return {
        isOpen: false,
        iframeHtml: '',
        _previousFocus: null,
        _onKeydown: null,

        init() {
            this._onKeydown = (event) => this.trapFocus(event);
            window.addEventListener('keydown', this._onKeydown);
        },

        destroy() {
            if (this._onKeydown) {
                window.removeEventListener('keydown', this._onKeydown);
            }
        },

        open(detail) {
            if (!detail || !detail.html) {
                return;
            }
            this._previousFocus = document.activeElement;
            this.iframeHtml = detail.html;
            this.isOpen = true;
            document.body.style.overflow = 'hidden';

            this.$nextTick(() => {
                // Premier élément tabbable = bouton de fermeture (fixé en haut à droite).
                const focusables = this.getFocusableElements();
                if (focusables.length > 0) {
                    focusables[0].focus();
                } else {
                    this.$el.focus();
                }
            });
        },

        close() {
            this.isOpen = false;
            this.iframeHtml = '';
            document.body.style.overflow = '';

            if (this._previousFocus && typeof this._previousFocus.focus === 'function') {
                this._previousFocus.focus();
                this._previousFocus = null;
            }
        },

        /**
         * Retourne tous les éléments tabbables visibles à l'intérieur de la modale.
         * L'iframe vidéo (Vimeo/YouTube) est incluse — son tabindex interne est géré
         * par le lecteur embarqué.
         */
        getFocusableElements() {
            if (!this.$el) return [];
            return Array.from(this.$el.querySelectorAll(TABBABLE_SELECTOR))
                .filter((el) => !el.hasAttribute('disabled')
                    && el.getAttribute('aria-hidden') !== 'true'
                    && (el.offsetParent !== null || el.tagName === 'IFRAME'));
        },

        /**
         * Focus trap :
         *  - ignore si la modale est fermée ou si la touche n'est pas Tab
         *  - si le focus est déjà hors modale (cas marginal), le ramène à l'intérieur
         *  - fait cycler first ↔ last selon shiftKey
         */
        trapFocus(event) {
            if (!this.isOpen || event.key !== 'Tab') return;

            const focusables = this.getFocusableElements();
            if (focusables.length === 0) {
                event.preventDefault();
                if (this.$el && typeof this.$el.focus === 'function') {
                    this.$el.focus();
                }
                return;
            }

            const first  = focusables[0];
            const last   = focusables[focusables.length - 1];
            const active = document.activeElement;

            if (!this.$el.contains(active)) {
                event.preventDefault();
                first.focus();
                return;
            }

            if (event.shiftKey && active === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && active === last) {
                event.preventDefault();
                first.focus();
            }
        },
    };
}
