import { test, expect } from '@playwright/test';

test.describe('Switch FR ↔ EN', () => {
  test('clic FR→EN change l\'URL et la langue du document', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('html')).toHaveAttribute('lang', /^fr/i);

    const toggle = page.locator('.lang-toggle a[hreflang^="en"]').first();
    await expect(toggle).toBeVisible();

    const href = await toggle.getAttribute('href');
    expect(href).toBeTruthy();

    await Promise.all([
      page.waitForURL(/\/en\/?/),
      toggle.click(),
    ]);

    await expect(page.locator('html')).toHaveAttribute('lang', /^en/i);
  });
});
