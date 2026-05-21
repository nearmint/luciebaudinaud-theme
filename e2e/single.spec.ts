import { test, expect } from '@playwright/test';

test.describe('Single film', () => {
  test('accès depuis la home : titre, cover, fil d\'Ariane présents', async ({ page }) => {
    await page.goto('/');

    const firstFilmLink = page.locator('[data-film-link]').first();
    await expect(firstFilmLink).toBeVisible();

    await Promise.all([
      page.waitForURL(/\/[^/]+\/$/),
      firstFilmLink.click(),
    ]);

    // h1 présent et non vide.
    const h1 = page.getByRole('heading', { level: 1 });
    await expect(h1).toBeVisible();
    await expect(h1).not.toHaveText('');

    // Breadcrumbs (Accueil > Films > titre).
    const breadcrumbs = page.locator('.lb-breadcrumbs');
    await expect(breadcrumbs).toBeVisible();
    await expect(breadcrumbs).toContainText(/Accueil|Home/);
    await expect(breadcrumbs).toContainText(/Films/);

    // JSON-LD CreativeWork présent.
    const jsonLd = await page.locator('script[type="application/ld+json"]').allTextContents();
    expect(jsonLd.some((txt) => txt.includes('"@type":"CreativeWork"') || txt.includes('"@type": "CreativeWork"')))
      .toBeTruthy();
  });

});
