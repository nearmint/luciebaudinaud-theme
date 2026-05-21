import { test, expect } from '@playwright/test';

test.describe('Filtres films', () => {
  test('clic sur un filtre réduit le nombre de films affichés', async ({ page }) => {
    await page.goto('/');

    const filterButtons = page.locator('[role="tab"][data-filter-slug]');
    const count = await filterButtons.count();
    test.skip(count < 2, 'Pas assez de catégories pour tester le filtrage.');

    const allCards = page.locator('.film');
    const totalVisibleBefore = await allCards.evaluateAll(
      (nodes) => nodes.filter((n) => (n as HTMLElement).offsetParent !== null).length
    );
    expect(totalVisibleBefore).toBeGreaterThan(0);

    // Clique le premier filtre de catégorie (index 1, 0 étant "Tout").
    const target = filterButtons.nth(1);
    const slug = await target.getAttribute('data-filter-slug');
    await target.click();

    await expect(target).toHaveAttribute('aria-selected', 'true');

    // Le statut aria-live doit s'être mis à jour.
    const status = page.locator('[role="status"][aria-live="polite"]').first();
    await expect(status).toContainText(/film/i);

    // Seuls les films de la catégorie sélectionnée doivent être visibles.
    const visibleWithSlug = await allCards.evaluateAll(
      (nodes, s) =>
        nodes
          .filter((n) => (n as HTMLElement).offsetParent !== null)
          .every((n) => (n.getAttribute('data-cats') || '').split(' ').includes(s as string)),
      slug
    );
    expect(visibleWithSlug).toBe(true);
  });
});
