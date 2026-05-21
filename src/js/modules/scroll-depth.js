/**
 * Scroll depth → Umami.
 *
 * Émet un event `scroll_depth` aux paliers 25 / 50 / 75 / 100 %.
 * Chaque palier n'est envoyé qu'une fois par session (mémorisation en mémoire).
 *
 * Gated via la classe `lb-track-scroll` sur <body> (ajoutée côté PHP sur home
 * et pages légales — les contenus longs linéaires où le scroll a du sens).
 *
 * Throttle : ~200 ms via rAF + timestamp.
 */

const THRESHOLDS = [25, 50, 75, 100];
const THROTTLE_MS = 200;

export function initScrollDepth() {
  if (typeof window === 'undefined' || typeof document === 'undefined') return;
  if (!document.body.classList.contains('lb-track-scroll')) return;

  const fired = new Set();
  let lastRun = 0;
  let ticking = false;

  const compute = () => {
    ticking = false;
    const now = Date.now();
    if (now - lastRun < THROTTLE_MS) return;
    lastRun = now;

    const max = document.documentElement.scrollHeight - window.innerHeight;
    if (max <= 0) return;

    const pct = Math.min(100, Math.max(0, (window.scrollY / max) * 100));

    for (const threshold of THRESHOLDS) {
      if (pct >= threshold && !fired.has(threshold)) {
        fired.add(threshold);
        if (typeof window.umami !== 'undefined' && typeof window.umami.track === 'function') {
          window.umami.track('scroll_depth', {
            depth: threshold,
            path: location.pathname,
          });
        }
      }
    }

    // Détache le listener quand tous les paliers sont atteints.
    if (fired.size === THRESHOLDS.length) {
      window.removeEventListener('scroll', onScroll);
    }
  };

  const onScroll = () => {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(compute);
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  // Check initial (si la page charge déjà scrollée).
  compute();
}
