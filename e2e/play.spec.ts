import { test, expect } from '@playwright/test';

test.describe('Modale vidéo', () => {
  test('clic Play ouvre la modale, ESC ferme', async ({ page }) => {
    await page.goto('/');

    const playBtn = page.locator('[data-umami-event="film_play"]').first();
    await expect(playBtn).toBeVisible();
    await playBtn.click();

    const modal = page.locator('#lb-video-modal');
    await expect(modal).toBeVisible();
    await expect(modal).toHaveAttribute('aria-modal', 'true');

    const iframe = modal.locator('iframe');
    await expect(iframe).toBeVisible();
    const src = await iframe.getAttribute('src');
    expect(src).toMatch(/vimeo\.com|youtube\.com|youtube-nocookie\.com/);

    // ESC ferme et libère le scroll.
    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();
    await expect(page.locator('body')).not.toHaveCSS('overflow', 'hidden');
  });
});
