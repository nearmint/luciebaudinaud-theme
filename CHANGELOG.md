# Changelog

All notable changes to this project will be documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Changed
- Deploy workflow switched from FTPS (port 21) to SFTP (port 22) via 
  wlixcc/SFTP-Deploy-Action. Better aligned with OVH SSH dedicated 
  account and enforces sftp_only mode (no fallback to unencrypted FTP).

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
