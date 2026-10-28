import { test, expect, type APIRequestContext, type Page } from '@playwright/test';
import { expectNoHorizontalScroll, expectNoNgWords, jsonLdGraph } from './helpers/page-checks';

// 全ページ共通のチェック。個別の中身は各ページの spec が見ている。
const FIXED_PATHS = ['/', '/stain-removal/', '/down-coat/', '/price/', '/cases/', '/about/', '/guide/', '/legal/', '/legal/terms/', '/legal/tokushoho/', '/legal/privacy/'];

let paths: string[] = [];
// 同じURLは spec 全体で1回だけ確認する。
const linkStatus = new Map<string, number>();

test.beforeAll(async ({ browser, baseURL }) => {
  const page = await browser.newPage({ baseURL });
  await page.goto('/cases/');
  const href = await page.locator('a[href*="/cases/"]').evaluateAll((els) =>
    (els as HTMLAnchorElement[]).map((a) => new URL(a.href).pathname).find((p) => /^\/cases\/[^/]+\/$/.test(p)),
  );
  await page.close();
  expect(href, '事例詳細へのリンクが /cases/ にある').toBeTruthy();
  paths = [...FIXED_PATHS, href as string];
});

async function checkLinks(page: Page, request: APIRequestContext): Promise<void> {
  const origin = new URL(page.url()).origin;
  const urls = await page.locator('a[href]').evaluateAll((els, o) => {
    const out = new Set<string>();
    for (const a of els as HTMLAnchorElement[]) {
      const raw = a.getAttribute('href') ?? '';
      if (/^(mailto:|tel:)/.test(raw)) continue;
      let u: URL;
      try {
        u = new URL(a.href);
      } catch {
        continue;
      }
      if (u.origin !== o) continue;
      u.hash = '';
      out.add(u.href);
    }
    return [...out];
  }, origin);
  for (const url of urls) {
    if (!linkStatus.has(url)) {
      linkStatus.set(url, (await request.get(url)).status());
    }
    expect(linkStatus.get(url), url).toBe(200);
  }
}

for (const index of Array.from({ length: FIXED_PATHS.length + 1 }, (_, i) => i)) {
  test.describe(`全ページ共通チェック ${FIXED_PATHS[index] ?? "事例詳細"}`, () => {
    test('構造・見出し・JSON-LD・NG表現・内部リンク', async ({ page, request }) => {
      const path = paths[index];
      const res = await page.goto(path);
      expect(res?.status(), path).toBe(200);

      await expect(page.locator('h1'), 'h1は1つ').toHaveCount(1);

      const ids = await page.evaluate(() => Array.from(document.querySelectorAll('[id]')).map((e) => e.id));
      const dup = ids.filter((id, i) => ids.indexOf(id) !== i);
      expect(dup, 'id の重複').toEqual([]);

      const levels = await page.evaluate(() => Array.from(document.querySelectorAll('h1,h2,h3,h4,h5,h6')).map((h) => Number(h.tagName[1])));
      let prev = 0;
      for (const lv of levels) {
        expect(lv - prev, `見出しの階層を飛ばさない（${levels.join(',')}）`).toBeLessThanOrEqual(1);
        prev = lv;
      }

      const graph = await jsonLdGraph(page);
      if (path !== '/') {
        expect(graph.find((n) => n['@type'] === 'BreadcrumbList'), 'BreadcrumbList').toBeDefined();
      }

      await expectNoNgWords(page);
      await checkLinks(page, request);
    });

    test('375px：横スクロールなし・固定CTAあり', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 812 });
      await page.goto(paths[index]);
      await expectNoHorizontalScroll(page);
      await expect(page.locator('.spc-sticky-cta [data-cta-location="sticky"]')).toHaveCount(1);
    });

    test('1280px：ヘッダーのCTAと相談ブロック（トップは最終CTA）', async ({ page }) => {
      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto(paths[index]);
      await expect(page.locator('header [data-cta-location="header"]')).toBeVisible();
      await expect(page.locator('[data-cta-location="final"]')).toHaveCount(1);
      if (paths[index] !== '/') {
        await expect(page.locator('.spc-cta-block [data-cta-location="final"]')).toHaveCount(1);
      }
    });
  });
}
