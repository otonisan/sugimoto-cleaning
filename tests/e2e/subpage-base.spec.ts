import { test, expect, type Page } from '@playwright/test';
import { ctaLocations, expectNoHorizontalScroll, expectNoNgWords, jsonLdGraph } from './helpers/page-checks';

const PATH = '/legal/terms/';
const CRUMBS = ['ホーム', '規約・法定表示', '利用規約'];

async function dataLayer(page: Page): Promise<Array<Record<string, unknown>>> {
  return page.evaluate(() => JSON.parse(JSON.stringify((window as any).dataLayer ?? [])));
}

test.describe('下層ページの共通部品（/legal/terms/）', () => {
  test('パンくず：ホーム／規約・法定表示／利用規約。最後だけリンクなしで aria-current="page"。JSON-LD の BreadcrumbList も同じ', async ({ page }) => {
    await page.goto(PATH);
    const nav = page.locator('nav.spc-breadcrumbs');
    await expect(nav).toHaveCount(1);
    await expect(nav).toHaveAttribute('aria-label', 'パンくずリスト');
    const items = nav.locator('ol > li');
    expect((await items.allTextContents()).map((t) => t.trim())).toEqual(CRUMBS);
    await expect(items.nth(0).locator('a')).toHaveCount(1);
    await expect(items.nth(1).locator('a')).toHaveCount(1);
    await expect(items.nth(2).locator('a')).toHaveCount(0);
    await expect(nav.locator('[aria-current="page"]')).toHaveCount(1);
    await expect(items.nth(2).locator('[aria-current="page"]')).toHaveText('利用規約');
    // 区切りは CSS の「／」（矢印は使わない）。
    const sep = await items.nth(1).evaluate((el) => getComputedStyle(el, '::before').content);
    expect(sep).toContain('／');

    const graph = await jsonLdGraph(page);
    const crumbs = graph.find((n) => n['@type'] === 'BreadcrumbList');
    expect(crumbs).toBeDefined();
    const list = crumbs.itemListElement as Array<{ position: number; name: string; item: string }>;
    expect(list.map((i) => i.name)).toEqual(CRUMBS);
    expect(list.map((i) => i.position)).toEqual([1, 2, 3]);
    expect(new URL(list[2].item).pathname).toBe(PATH);
    expect(graph.find((n) => n['@type'] === 'Organization')).toBeDefined();
    expect(graph.find((n) => n['@type'] === 'FAQPage')).toBeUndefined();
  });

  test('h1は1つで「利用規約」、body[data-page-type="legal"]', async ({ page }) => {
    await page.goto(PATH);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('h1')).toHaveText('利用規約');
    await expect(page.locator('body')).toHaveAttribute('data-page-type', 'legal');
    await expectNoNgWords(page);
  });

  test('375px：相談ブロックのCTAクリックで line_click {cta_location: final, page_type: legal}', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.route('https://lin.ee/**', (route) => route.fulfill({ status: 200, contentType: 'text/html', body: 'ok' }));
    await page.goto(PATH);
    await expectNoHorizontalScroll(page);
    await page.evaluate(() => {
      window.addEventListener('click', (e) => e.preventDefault());
    });
    await page.locator('.spc-cta-block [data-cta-location="final"]').click();
    const clicks = (await dataLayer(page)).filter((e) => e.event === 'line_click');
    expect(clicks).toEqual([{ event: 'line_click', cta_location: 'final', page_type: 'legal' }]);
  });

  test('CTAはヘッダー・相談ブロック・固定CTA・QRモーダルだけ', async ({ page }) => {
    await page.goto(PATH);
    const locations = await ctaLocations(page);
    expect(locations.length).toBeGreaterThan(0);
    for (const loc of locations) {
      expect(['header', 'final', 'sticky', 'qr_modal']).toContain(loc);
    }
    expect(locations.filter((l) => l === 'final')).toHaveLength(1);
  });

  test('sections.css と pages.css を読み込み、lp.css は読み込まない', async ({ page }) => {
    await page.goto(PATH);
    await expect(page.locator('link[rel="stylesheet"][href*="/assets/css/sections.css"]')).toHaveCount(1);
    await expect(page.locator('link[rel="stylesheet"][href*="/assets/css/pages.css"]')).toHaveCount(1);
    await expect(page.locator('link[rel="stylesheet"][href*="/assets/css/lp.css"]')).toHaveCount(0);
  });
});
