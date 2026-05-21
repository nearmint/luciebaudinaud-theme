/**
 * Façade vidéo — Alpine component.
 *
 * Appliqué sur chaque bloc généré par inc/video-facade.php :
 *  - au clic, remplace le placeholder par l'iframe stockée dans data-iframe-html
 *  - ajoute ?autoplay=1 pour démarrer la lecture immédiatement après injection
 *
 * Aucune requête réseau vers Vimeo/YouTube n'est effectuée tant que load() n'a
 * pas été appelé.
 */
export function videoFacade() {
    return {
        loaded: false,

        load() {
            if (this.loaded) {
                return;
            }
            const html = this.$el.dataset.iframeHtml || '';
            if (!html) {
                return;
            }
            this.loaded = true;
            this.$nextTick(() => {
                const container = this.$refs.container;
                if (!container) {
                    return;
                }
                container.innerHTML = html;

                const iframe = container.querySelector('iframe');
                if (iframe && iframe.src) {
                    try {
                        const url = new URL(iframe.src, window.location.origin);
                        if (/vimeo\.com|youtube\.com|youtube-nocookie\.com|youtu\.be/.test(url.hostname)) {
                            url.searchParams.set('autoplay', '1');
                            iframe.src = url.toString();
                        }
                    } catch (_) {
                        // src invalide : on laisse tel quel.
                    }
                    iframe.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
                }
            });
        },
    };
}
