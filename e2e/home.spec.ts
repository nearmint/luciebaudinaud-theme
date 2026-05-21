import { test, expect } from '@playwright/test';

test.describe('Home', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('hero, grille films et footer sont visibles', async ({ page }) => {
    const hero = page.locator('#intro');
    await expect(hero).toBeVisible();
    await expect(hero.getByRole('heading', { level: 1 })).toContainText(/Lucie Baudinaud/i);

    await expect(page.locator('#films')).toBeVisible();
    await expect(page.locator('.film')).not.toHaveCount(0);

    const footer = page.getByRole('contentinfo');
    await expect(footer).toBeVisible();
    await expect(footer).toContainText(/Lucie Baudinaud/);
  });

  test('le statut aria-live annonce le nombre de films', async ({ page }) => {
    const status = page.locator('[role="status"][aria-live="polite"]').first();
    await expect(status).toHaveText(/film/i, { timeout: 5000 });
  });
});
