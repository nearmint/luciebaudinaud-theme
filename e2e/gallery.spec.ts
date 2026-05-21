import { test, expect } from '@playwright/test';

test.describe('PhotoSwipe galerie', () => {
  test('clic photo ouvre la lightbox, flèche suivant, ESC ferme', async ({ page }) => {
    await page.goto('/');

    // La section Photos est en dessous du fold → scroll pour déclencher lazy.
    await page.locator('#photos').scrollIntoViewIfNeeded();

    const firstPhoto = page.locator('#lb-photoswipe-gallery a').first();
    await expect(firstPhoto).toBeVisible();
    await firstPhoto.click();

    const pswp = page.locator('.pswp');
    await expect(pswp).toBeVisible();

    // Flèche suivante — navigation PhotoSwipe standard.
    await page.keyboard.press('ArrowRight');
    // Laisse à PhotoSwipe le temps de transitionner.
    await page.waitForTimeout(250);

    await page.keyboard.press('Escape');
    await expect(pswp).toBeHidden({ timeout: 5000 });
  });
});
