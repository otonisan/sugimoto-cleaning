import { test, expect } from '@playwright/test';

test('トップが表示できる', async ({ page }) => {
  const res = await page.goto('/');
  expect(res?.status()).toBe(200);
  await expect(page.locator('html')).toHaveAttribute('lang', 'ja');
  await expect(page.locator('link[href*="spc-theme/style.css"]')).toHaveCount(1);
});
