# Changelog

All notable changes to this project will be documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Changed
- Deploy workflow: replaced GitHub Actions auto-deploy (FTPS) with local 
  SFTP script bin/deploy-sftp.sh (lftp), matching the workflow used on 
  Lamixtape. Credentials stay 100% local (.env gitignored), no secret 
  in GitHub. CI workflow preserved for PHP lint + Vite build validation.

### Fixed
- SEO : JSON-LD Person enrichi avec sameAs (IMDB, AFC, Allociné, Unifrance, 
  La Fémis, Femmes à la caméra, Film-Documentaire, MUBI, Vimeo, Instagram), 
  alumniOf (La Fémis), knowsAbout, pour favoriser knowledge panel Google
- SEO : fix hreflang Polylang sur fiches films — la version EN pointait vers /en/ 
  (home) au lieu de la vraie traduction. Maintenant : seules les langues 
  effectivement traduites apparaissent en hreflang alternate
- SEO : noindex,nofollow sur archives auteur/catégorie/tag/date/recherche 
  (en plus du 301 redirect existant sur /author/)

### Context
- Suite à un hack résolu (cloaking bots servant du spam Kavbet aux crawlers)
- 326 backlinks spam disavowed via GSC
- Réindexation manuelle demandée sur la home

### Removed
- .github/workflows/deploy.yml (replaced by bin/deploy-sftp.sh)

## [1.0.0] — 2026-04-22

Initial public release.

### Added
- WordPress theme from scratch for Lucie Baudinaud (cinematographer, AFC)
- Bilingual FR / EN via Polylang with automatic `hreflang` alternates
- Film grid with category filters (slugs-based for resilience)
- PhotoSwipe 5 gallery with masonry layout on home, stills on single film
- Video modal with lazy-loaded Vimeo / YouTube embeds
- Sticky header that reveals on scroll, integrated lang switcher
- Anti-FOUC loader on first load
- ACF Pro integration with `acf-json` sync and PHP fallback definitions
- SEO: `title-tag`, dynamic meta, OG, Twitter Cards, JSON-LD (Person, CreativeWork, VideoObject, BreadcrumbList), IndexNow endpoint
- Accessibility: WCAG AA, keyboard navigation, focus traps, `prefers-reduced-motion`
- Umami analytics (cookieless, manual tracking)
- Admin improvements: custom columns, category filter, detailed ACF instructions
- Drag-and-drop film ordering via publication date (Quick Edit)
- Downloadable CV PDF via ACF field
- Anti-FOUC loader
- CSP headers, XML-RPC disable, author enumeration block
- Skill link to editorial guide in French (`docs/GUIDE-LUCIE.md`)

### Stack
- PHP 8.3 (8.1 minimum)
- WordPress 6.4+
- Vite 6, Tailwind CSS v4, Alpine.js 3
- PhotoSwipe 5
- @fontsource (self-hosted fonts, Latin + Latin-ext subsets)
