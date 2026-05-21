import { defineConfig, devices } from '@playwright/test';

/**
 * lb3 — Playwright config.
 *
 * baseURL : surcharge via PLAYWRIGHT_BASE_URL (défaut : staging).
 * En CI, le workflow passe l'URL de staging après deploy.
 * En local : `PLAYWRIGHT_BASE_URL=http://localhost:10003 npx playwright test`.
 *
 * Playwright compile les .ts natifs via esbuild — pas besoin de TypeScript installé.
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 2 : undefined,
  reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',

  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'https://staging.luciebaudinaud.com',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    locale: 'fr-FR',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
    {
      name: 'mobile-chrome',
      use: { ...devices['Pixel 7'] },
    },
  ],
});
