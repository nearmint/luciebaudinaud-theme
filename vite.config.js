import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';
import fs from 'node:fs';

/**
 * Vite config for WordPress theme lb3.
 *
 * Entries :
 *   - src/js/app.js            → bundle principal (+ CSS app.css)
 *   - src/css/critical.css     → bundle CSS critical inliné dans <head> côté PHP
 *
 * Assets additionnels émis par le plugin `lb3-sw` :
 *   - dist/sw.js               → service worker (version stampée au build)
 *   - dist/offline.html        → page offline servie par le SW
 *
 * En dev : HMR sur localhost:5173. En prod : lit manifest.json depuis PHP.
 */

/**
 * Plugin Vite interne : copie sw.js (avec version stampée) et offline.html
 * depuis src/sw/ vers dist/ au build. Pas de hash sur ces fichiers : le SW
 * doit rester accessible à une URL stable (/sw.js via template_redirect).
 */
function lb3ServiceWorker() {
  return {
    name: 'lb3-sw',
    apply: 'build',
    generateBundle() {
      const version = 'v-' + Date.now().toString(36);

      const swPath = path.resolve(__dirname, 'src/sw/sw.js');
      if (fs.existsSync(swPath)) {
        const swSource = fs.readFileSync(swPath, 'utf-8').replace(/__LB3_SW_VERSION__/g, version);
        this.emitFile({ type: 'asset', fileName: 'sw.js', source: swSource });
      }

      const offlinePath = path.resolve(__dirname, 'src/sw/offline.html');
      if (fs.existsSync(offlinePath)) {
        this.emitFile({
          type: 'asset',
          fileName: 'offline.html',
          source: fs.readFileSync(offlinePath, 'utf-8'),
        });
      }
    },
  };
}

export default defineConfig(({ mode }) => ({
  base: mode === 'development' ? '/' : '',
  plugins: [tailwindcss(), lb3ServiceWorker()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
  build: {
    manifest: true,
    outDir: 'dist',
    emptyOutDir: true,
    assetsDir: 'assets',
    rollupOptions: {
      input: {
        app: path.resolve(__dirname, 'src/js/app.js'),
        // Bundle CSS séparé, inliné dans <head> par inc/assets.php.
        critical: path.resolve(__dirname, 'src/css/critical.css'),
      },
    },
  },
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    origin: 'http://127.0.0.1:5173',
    cors: true,
  },
}));
