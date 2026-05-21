/**
 * Filtre de la grille de films.
 *
 * Chaque .film a un data-cats="slug1 slug2" (voir film-card.php).
 * - matches(cats)    → x-show par catégorie
 * - statusMessage    → région aria-live="polite" (annonce nombre de films)
 *
 * La navigation clavier est assurée par le Tab natif sur chaque [data-film-link]
 * visible. Pas de role="grid" ni de roving tabindex — markup sémantique simple
 * (cf. films-grid.php : nav > ul > li > button pour les filtres, div > article
 * pour la grille).
 *
 * Alpine auto-transitions (x-transition) sur .film → fade + scale fluide.
 */
const DEFAULT_LABELS = {
    all_singular: '1 film affiché',
    all_plural:   '%d films affichés',
    cat_singular: '%s : 1 film',
    cat_plural:   '%s : %d films',
};

export function filmsFilter(opts = {}) {
    return {
        active: '*',
        statusMessage: '',
        _labels: Object.assign({}, DEFAULT_LABELS, opts.labels || {}),

        init() {
            this.$nextTick(() => this._computeStatus());
        },

        setFilter(slug) {
            this.active = slug || '*';
            this._computeStatus();
            this._scrollToSectionIfBelow();
        },

        _scrollToSectionIfBelow() {
            const section = document.getElementById('films');
            if (!section) {
                return;
            }
            const rect = section.getBoundingClientRect();
            // Si on est déjà au-dessus du haut de la section, ne pas sauter.
            if (rect.top >= 0) {
                return;
            }
            const headerH = parseInt(
                getComputedStyle(document.documentElement).getPropertyValue('--lb-header-h') || '64',
                10
            );
            const filtersEl = document.querySelector('[data-films-filters]');
            // La barre de filtres est à l'intérieur de la section ; son offsetHeight inclut
            // déjà py-3 + texte. On soustrait header + filtres + 1px pour rester DANS la
            // section afin de garder le sticky des filtres actif après le scroll.
            const filtersH = filtersEl ? filtersEl.offsetHeight : 0;
            const targetY = rect.top + window.scrollY - headerH - filtersH + 1;
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: targetY, behavior: reduced ? 'auto' : 'smooth' });
        },

        matches(cats) {
            if (this.active === '*') {
                return true;
            }
            if (!cats) {
                return false;
            }
            return cats.split(/\s+/).includes(this.active);
        },

        _categoryLabel(slug) {
            const btn = document.querySelector(`[data-filter-slug="${slug}"]`);
            return btn ? btn.textContent.trim() : slug;
        },

        _computeStatus() {
            let count = 0;
            document.querySelectorAll('.film').forEach(el => {
                const cats = el.dataset.cats || '';
                if (this.active === '*' || cats.split(/\s+/).includes(this.active)) {
                    count++;
                }
            });

            let tpl;
            if (this.active === '*') {
                tpl = count > 1 ? this._labels.all_plural : this._labels.all_singular;
                this.statusMessage = tpl.replace('%d', count);
            } else {
                tpl = count > 1 ? this._labels.cat_plural : this._labels.cat_singular;
                this.statusMessage = tpl
                    .replace('%s', this._categoryLabel(this.active))
                    .replace('%d', count);
            }
        },
    };
}
