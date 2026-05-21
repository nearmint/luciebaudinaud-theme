# luciebaudinaud-theme

> Custom WordPress theme for cinematographer Lucie Baudinaud (AFC)

[![CI](https://github.com/nearmint/luciebaudinaud-theme/actions/workflows/ci.yml/badge.svg)](https://github.com/nearmint/luciebaudinaud-theme/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](./LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.4+-21759B?logo=wordpress&logoColor=white)](https://wordpress.org/)

Portfolio theme for [luciebaudinaud.com](https://luciebaudinaud.com), the personal website of Lucie Baudinaud, director of photography and member of AFC (Association Française des directeurs de la photographie Cinématographique).

---

## Stack

| Layer | Tech |
|---|---|
| Backend | WordPress 6.4+, PHP 8.3 |
| Build | Vite 6 |
| CSS | Tailwind CSS v4 |
| JS | Alpine.js 3 |
| Lightbox | PhotoSwipe 5 |
| Fonts | @fontsource (self-hosted, RGPD) |
| CMS | ACF Pro + ACF Gallery Field |
| i18n | Polylang + ACF Options for Polylang (BeAPI) |
| Analytics | Umami (cookieless) |

---

## Features

- Bilingual (FR / EN) with Polylang, automatic `hreflang` alternates
- Film grid with category filters, slugs-based (not hardcoded IDs)
- Photo gallery with PhotoSwipe lightbox and masonry layout
- Video modal with lazy-loaded Vimeo / YouTube embeds
- Sticky header that reveals on scroll, integrated with sticky film filters
- Film detail pages with cover, trailer, technical details, stills gallery
- SEO: `title-tag`, OG + Twitter Cards, JSON-LD (`Person`, `CreativeWork`, `VideoObject`), sitemap via Polylang
- Accessibility: WCAG AA contrast, keyboard navigation, focus traps, `prefers-reduced-motion`
- Performance: lazy iframes, preconnect CDN video, `srcset`, `modulepreload`, native PhotoSwipe chunk splitting
- Anti-FOUC loader on first load
- Umami analytics with manual tracking (no cookies, no GDPR banner)

---

## Requirements

- Node.js 20 LTS
- PHP 8.1+ (production runs on 8.3)
- WordPress 6.4+
- WordPress plugins: ACF Pro, ACF Gallery Field, Polylang, ACF Options for Polylang (BeAPI)

---

## Installation

```bash
# Clone into wp-content/themes/
git clone https://github.com/nearmint/luciebaudinaud-theme.git wp-content/themes/lb3
cd wp-content/themes/lb3

# Install dependencies
npm install

# Build assets
npm run build
```

Then activate the theme: `Admin → Appearance → Themes → lb3`.

---

## Scripts

| Command | Purpose |
|---|---|
| `npm run build` | Production build to `dist/` |
| `npm run build -- --watch` | Rebuild on source change |
| `npm run dev` | Vite dev server (HMR) |

---

## Architecture

```
luciebaudinaud-theme/
├── style.css                      WordPress theme header
├── functions.php                  Module loader (no business logic)
├── header.php, footer.php, front-page.php, single.php, index.php, 404.php
├── template-parts/
│   ├── hero.php
│   ├── films-grid.php             Film grid + category filters (Alpine)
│   ├── film-card.php
│   ├── photos-gallery.php         PhotoSwipe gallery
│   ├── cv.php, contact.php
│   ├── section-divider.php
│   ├── single-navbar.php
│   ├── lang-switcher.php
│   ├── site-header.php            Sticky header on home
│   └── breadcrumbs.php
├── inc/
│   ├── i18n.php                   Textdomain + Polylang helpers
│   ├── setup.php                  Theme supports, image sizes, ACF config
│   ├── cleanup.php                Head cleanup (emoji, block library)
│   ├── security.php               Version hiding, XML-RPC disable, headers
│   ├── loader.php                 Anti-FOUC loader
│   ├── assets.php                 Vite manifest enqueue
│   ├── seo.php                    Meta, OG, Twitter, hreflang, JSON-LD
│   ├── acf-fields.php             Field groups (PHP fallback for acf-json)
│   ├── umami.php                  Analytics script (auto-track disabled)
│   ├── render.php                 wp_footer callbacks (modals, loader)
│   ├── template.php               Template helpers
│   ├── indexnow.php               IndexNow endpoint + hooks
│   ├── admin-alt-validator.php    Admin warning for missing alt text
│   ├── tinymce-lang-button.php    TinyMCE custom button for lang attr
│   └── acf-cache.php              get_field() cache wrapper
├── src/
│   ├── css/app.css                Tailwind entry + design tokens
│   └── js/
│       ├── app.js                 Entry point
│       └── modules/
│           ├── films-filter.js
│           ├── video-modal.js
│           ├── video-facade.js
│           ├── gallery.js         PhotoSwipe init
│           ├── site-header.js
│           ├── back-to-top.js
│           ├── scroll-progress.js
│           ├── web-vitals.js
│           ├── outbound-links.js
│           ├── scroll-depth.js
│           └── analytics.js       Manual Umami tracking
├── acf-json/                      ACF field groups (synced)
├── languages/                     .pot / .po / .mo
├── dist/                          Vite output (gitignored, generated on deploy)
├── .github/
│   └── workflows/
│       └── ci.yml                 Lint + build on PR
├── package.json, vite.config.js
└── docs/
    ├── GUIDE-LUCIE.md             Editorial guide (French)
    └── MIGRATION.md               Migration notes
```

---

## Development workflow

### Editing PHP templates
Direct edit, WordPress serves without rebuild.

### Editing CSS / JS
```bash
npm run build -- --watch
```
Saves in `src/` → new `dist/` → WordPress reads new manifest.

### Adding an ACF field
Create the field group in WP admin. ACF syncs it as JSON in `/acf-json/`. Commit the JSON.

### Adding a translatable string
Use `__('Text', 'lb3')` in templates, then:
```bash
wp i18n make-pot . languages/lb3.pot --domain=lb3
```
Open `languages/lb3-en_US.po` in Poedit, translate, save (regenerates `.mo`).

---

## Deployment

Manual SFTP deploy via `bin/deploy-sftp.sh` (uses `lftp`).

### One-time setup

```bash
brew install lftp
cp .env.example .env
# Edit .env to fill in your OVH SFTP credentials
```

### Deploy workflow

```bash
# 1. Test connection
bash bin/deploy-sftp.sh --connect-test

# 2. Preview changes (no upload)
bash bin/deploy-sftp.sh --dry-run

# 3. Real deploy (confirmation prompt)
bash bin/deploy-sftp.sh

# 4. Post-deploy sanity check
bash bin/check-headers.sh https://luciebaudinaud.com
```

CI workflow (`.github/workflows/ci.yml`) validates PHP lint and Vite build on every PR, but does NOT deploy. Production deploys are always manual under explicit user validation, following Nicolas's "2 validations séparées" convention (push GitHub → deploy SFTP).

---

## Performance targets

| Metric | Target | Actual (avg) |
|---|---|---|
| LCP (Largest Contentful Paint) | < 2.5s | ~1.2s |
| CLS (Cumulative Layout Shift) | < 0.1 | ~0.02 |
| INP (Interaction to Next Paint) | < 200ms | ~80ms |
| CSS bundle (gzip) | — | ~8.8 KB |
| JS bundle (gzip) | — | ~22 KB |
| PhotoSwipe chunk (lazy, gzip) | — | ~17.5 KB |

Monitored via Web Vitals → Umami events.

---

## Analytics (Umami)

Site ID: `1af9b296-c131-4967-a38a-802d148809ea`

Umami is loaded with `data-auto-track="false"` to prevent its capture-phase click listener from forcing navigation on gallery links (which would bypass PhotoSwipe's preventDefault).

All tracking is **manual**, handled in `src/js/modules/analytics.js`:
- Pageview on load
- Events via `data-umami-event="name"` attributes (delegated bubble-phase listener, no interference with other handlers)

To track a new event:
```html
<button data-umami-event="my_event" data-umami-event-param1="value">…</button>
```

Admins and non-production environments are excluded from tracking.

---

## Accessibility

- Skip link to main content
- HTML5 landmarks (`<main>`, `<nav>`, `<article>`, `<header>`)
- `aria-current` on active states (language, filter, nav)
- Focus trap on video modal and PhotoSwipe
- `prefers-reduced-motion` respected on all animations
- WCAG AA color contrast validated
- Keyboard navigation tested (Tab, Shift+Tab, arrows, ESC)

---

## SEO

- `title-tag` theme support
- Dynamic meta descriptions (ACF field on home, excerpt on single)
- Open Graph + Twitter Cards
- `hreflang` alternates via Polylang
- Canonical URLs
- JSON-LD schemas: `Person` on home, `CreativeWork` + `VideoObject` on single films, `BreadcrumbList` everywhere
- IndexNow endpoint for Bing / Yandex fast indexing

---

## Credits

- **Design & development**: [Nicolas Rapp](https://www.nicolasrapp.co)
- **Client**: Lucie Baudinaud, AFC
- **Photography**: Lucie Baudinaud
- **Site**: [luciebaudinaud.com](https://luciebaudinaud.com)

---

## License

[MIT](./LICENSE) — Copyright (c) 2026 Nicolas Rapp

This theme includes imagery, content, and brand identity belonging to Lucie Baudinaud, which are **not** covered by this license and are the exclusive property of their owner. The MIT license applies only to the theme code (PHP, JS, CSS, templates, configuration).
