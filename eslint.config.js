/**
 * ESLint flat config (ESLint 9+).
 *
 * Cible : src/js/**.js (code thème, chargé côté front WordPress).
 * On étend `@eslint/js` recommended et on déclare les globals browser + Alpine.
 */

import js from '@eslint/js';
import globals from 'globals';

export default [
  {
    ignores: ['dist/**', 'node_modules/**', 'vendor/**', '.husky/_/**'],
  },

  js.configs.recommended,

  {
    files: ['src/js/**/*.js'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        // Alpine est exposé global via app.js (`window.Alpine = Alpine`).
        Alpine: 'readonly',
        // Umami : chargé async par le script tiers, peut être undefined → on teste avant usage.
        umami: 'readonly',
      },
    },
    rules: {
      'no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
      'no-console': ['warn', { allow: ['warn', 'error'] }],
      eqeqeq: ['error', 'smart'],
      'prefer-const': 'warn',
    },
  },

  {
    // Fichiers de config (Vite, ESLint lui-même) = Node, pas Browser.
    files: ['*.config.{js,mjs,cjs}', 'vite.config.js', 'eslint.config.js'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.node,
      },
    },
  },
];
