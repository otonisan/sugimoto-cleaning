import { test, expect } from '@playwright/test';
import { expectNoHorizontalScroll, expectNoNgWords } from './helpers/page-checks';
import { setPostContent, wpCli } from './helpers/wp';

const CHILDREN = [
  { path: '/legal/terms/', title: '利用規約' },
  { path: '/legal/tokushoho/', title: '特定商取引法に基づく表記' },
  { path: '/legal/privacy/', title: '個人情報・相談写真の取扱い' },
];

test.describe('規約・法定表示（/legal/）', () => {
  test('一覧：h1は1つ、リンク3つが menu_order 順、相談ブロックだけがCTA', async ({ page }) => {
    await page.goto('/legal/');
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('body')).toHaveAttribute('data-page-type', 'legal');
    const links = page.locator('main ul[role="list"].spc-legal-list a');
    await expect(links).toHaveCount(3);
    expect(await links.allTextContents()).toEqual(CHILDREN.map((c) => c.title));
    const hrefs = await links.evaluateAll((els) => els.map((e) => new URL((e as HTMLAnchorElement).href).pathname));
    expect(hrefs).toEqual(CHILDREN.map((c) => c.path));
    await expect(page.locator('main h2')).toHaveCount(1); // 相談ブロックの見出しだけ（空のh2なし）
    // 共通のヘッダー・固定CTA・QRを除き、本文内のCTAは相談ブロックだけ
    expect(await page.locator('main [data-spc-cta]').evaluateAll((els) => els.map((e) => e.getAttribute('data-cta-location')))).toEqual(['final']);
    await expectNoNgWords(page);
  });

  for (const c of CHILDREN) {
    test(`子ページ ${c.path}：h1は1つ、パンくず3段、data-page-type=legal、200`, async ({ page }) => {
      const res = await page.goto(c.path);
      expect(res?.status()).toBe(200);
      await expect(page.locator('h1')).toHaveCount(1);
      await expect(page.locator('h1')).toHaveText(c.title);
      await expect(page.locator('body')).toHaveAttribute('data-page-type', 'legal');
      await expect(page.locator('.spc-breadcrumb li, .spc-breadcrumbs li, nav[aria-label="パンくず"] li')).toHaveCount(3);
      await expectNoNgWords(page);
    });
  }

  test('特商法：表に「販売業者」「有限会社杉本クリーニング商会」、長い値でも375pxで横スクロールなし', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 800 });
    await page.goto('/legal/tokushoho/');
    const table = page.locator('.spc-prose.spc-table-content table');
    await expect(table).toContainText('販売業者');
    await expect(table).toContainText('有限会社杉本クリーニング商会');
    const style = await table.evaluate((el) => {
      const cs = getComputedStyle(el);
      return { layout: cs.tableLayout, collapse: cs.borderCollapse };
    });
    expect(style).toEqual({ layout: 'fixed', collapse: 'collapse' });
    expect(await table.locator('tr:first-child > *').count()).toBe(2);
    await expectNoHorizontalScroll(page);

    const id = Number(wpCli(['post', 'list', '--post_type=page', '--name=tokushoho', '--field=ID']));
    const original = wpCli(['post', 'get', String(id), '--field=post_content']);
    try {
      const long = 'あ'.repeat(100);
      setPostContent(id, `<table><tbody><tr><th scope="row">販売業者</th><td>${long}</td></tr></tbody></table>`);
      await page.goto('/legal/tokushoho/');
      await expectNoHorizontalScroll(page);
      expect(await table.locator('tr:first-child > *').count()).toBe(2);
    } finally {
      setPostContent(id, original);
    }
  });

  test('個人情報：h2「相談写真の保管期間と削除」「アクセス解析」、本文幅は35em前後', async ({ page }) => {
    await page.goto('/legal/privacy/');
    await expect(page.getByRole('heading', { level: 2, name: '相談写真の保管期間と削除' })).toHaveCount(1);
    await expect(page.getByRole('heading', { level: 2, name: /アクセス解析/ })).toHaveCount(1);
    const { width, font } = await page.locator('.spc-prose').evaluate((el) => {
      const cs = getComputedStyle(el);
      return { width: el.getBoundingClientRect().width, font: parseFloat(cs.fontSize) };
    });
    expect(width / font).toBeGreaterThan(33);
    expect(width / font).toBeLessThanOrEqual(36);
  });

  test('フッターの規約リンク3つがすべて200', async ({ page, request }) => {
    await page.goto('/legal/');
    for (const c of CHILDREN) {
      const link = page.locator('footer a', { hasText: c.title.split('・')[0] }).first();
      await expect(link).toHaveAttribute('href', new RegExp(`${c.path}$`));
      const res = await request.get(c.path);
      expect(res.status()).toBe(200);
    }
  });
});
