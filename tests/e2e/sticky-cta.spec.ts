import { test, expect } from '@playwright/test';

test.describe.configure({ timeout: 120_000 });

test.describe('スマホ固定CTA', () => {
  test('375px：先頭では非表示、FVを過ぎると表示、最終CTAが見えると非表示', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/');
    const bar = page.locator('.spc-sticky-cta');
    await expect(bar).toBeHidden();

    await page.evaluate(() => {
      const hero = document.querySelector('.hero') as HTMLElement;
      window.scrollTo(0, hero.offsetTop + hero.offsetHeight + 40);
    });
    await expect(bar).toBeVisible();
    await expect(bar.locator('[data-cta-location="sticky"]')).toHaveText('写真を送って無料相談');
    // 高さ 64px（セーフエリア 0）、画面下端に固定、影なし、上罫線 1px。
    const box = await bar.boundingBox();
    expect(box!.height).toBe(64);
    expect(box!.y + box!.height).toBe(812);
    await expect(bar).toHaveCSS('box-shadow', 'none');
    await expect(bar).toHaveCSS('border-top-width', '1px');

    await page.evaluate(() => {
      const el = document.querySelector('.spc-final-cta') as HTMLElement;
      window.scrollTo(0, el.offsetTop);
    });
    await expect(bar).toBeHidden();

    // 先頭に戻ると再び非表示。
    await page.evaluate(() => window.scrollTo(0, 0));
    await expect(bar).toBeHidden();
  });

  test('375px：body の下に固定バーと同じ高さの余白があり、フォーカス時のスクロールも固定バーの分をあける', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/');
    // セーフエリア（ホームバー）の分も足す。テスト環境では env(safe-area-inset-bottom) は 0。
    await expect(page.locator('body')).toHaveCSS('padding-bottom', '64px');
    await expect(page.locator('html')).toHaveCSS('scroll-padding-bottom', '64px');
  });

  test('375px：data-sticky-hide のないページ（/price/）でも、相談ブロックが画面外ならスクロール後に表示される', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 400 });
    await page.goto('/price/');
    await expect(page.locator('.spc-sticky-cta')).toBeHidden();
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await expect(page.locator('.spc-sticky-cta')).toBeVisible();
  });

  test('1024px：スクロールしても常に非表示。body に余白もない', async ({ page }) => {
    await page.setViewportSize({ width: 1024, height: 800 });
    await page.goto('/');
    await page.evaluate(() => window.scrollTo(0, 1500));
    await expect(page.locator('.spc-sticky-cta')).toBeHidden();
    await expect(page.locator('body')).toHaveCSS('padding-bottom', '0px');
    await expect(page.locator('html')).not.toHaveCSS('scroll-padding-bottom', '64px');
  });

  test('固定CTAのクリックは line_click {cta_location: sticky}', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 400 });
    await page.goto('/price/');
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await expect(page.locator('.spc-sticky-cta')).toBeVisible();
    await page.evaluate(() => {
      window.addEventListener('click', (e) => e.preventDefault());
    });
    await page.locator('.spc-sticky-cta [data-spc-cta]').click();
    const clicks = await page.evaluate(() => (window as any).dataLayer.filter((e: any) => e.event === 'line_click'));
    expect(clicks).toEqual([{ event: 'line_click', cta_location: 'sticky', page_type: 'price' }]);
  });
});
