import { test, expect, type Page } from '@playwright/test';
import { ctaLocations, expectNoHorizontalScroll, expectNoNgWords, jsonLdGraph } from './helpers/page-checks';
import { publishedCaseIds, trash, untrash, wpCli } from './helpers/wp';

// WP-CLI を呼ぶテストは遅いことがあるため、長めに待つ。
test.describe.configure({ timeout: 180_000 });

const ARCHIVE = '/cases/';
const ROW_LABELS = ['品目', '素材', 'シミ', '他店処理', '処置', '仕上がり', '所要日数'];

/** 一覧の最初の事例の詳細ページのパス。 */
async function firstCasePath(page: Page): Promise<string> {
  await page.goto(ARCHIVE);
  const href = await page.locator('.spc-diagnosis__no a').first().getAttribute('href');
  expect(href).toBeTruthy();
  return new URL(href as string, page.url()).pathname;
}

/** h1 の文字（仮バッジの「仮」を除く）。 */
async function titleOf(page: Page): Promise<string> {
  const text = await page.locator('h1').evaluate((el) => {
    const clone = el.cloneNode(true) as HTMLElement;
    clone.querySelectorAll('.spc-ph-badge').forEach((b) => b.remove());
    return clone.textContent ?? '';
  });
  return text.replace(/\s+/g, ' ').trim();
}

test.describe('施工事例の一覧（/cases/）', () => {
  test('h1「施工事例」、data-page-type="case"、診断票3件の見出しは h2 で詳細へのリンク', async ({ page }) => {
    await page.goto(ARCHIVE);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('h1')).toHaveText('施工事例');
    await expect(page.locator('body')).toHaveAttribute('data-page-type', 'case');
    await expect(page.locator('.spc-breadcrumbs')).toContainText('施工事例');
    const cards = page.locator('main .spc-diagnosis');
    await expect(cards).toHaveCount(3);
    for (let i = 0; i < 3; i++) {
      const heading = cards.nth(i).locator('h2.spc-diagnosis__no');
      await expect(heading).toHaveCount(1);
      const href = await heading.locator('a').getAttribute('href');
      expect(href).toMatch(/\/cases\/[^/]+\/$/);
    }
    await expect(page.locator('main article h3')).toHaveCount(0);
    // 件数が少ないときはページ送りを出さない。
    await expect(page.locator('nav.pagination')).toHaveCount(0);
  });

  test('一覧は灰白地で、1列（375px）・2列（768px）・3列（1280px）', async ({ page }) => {
    for (const [width, expected] of [
      [375, 1],
      [768, 2],
      [1280, 3],
    ] as const) {
      await page.setViewportSize({ width, height: 900 });
      await page.goto(ARCHIVE);
      const columns = await page
        .locator('.spc-cases__list')
        .evaluate((el) => getComputedStyle(el).gridTemplateColumns.split(' ').length);
      expect(columns).toBe(expected);
    }
    await expect(page.locator('section.spc-cases')).toHaveClass(/spc-section--mist/);
  });

  test('10件にすると1ページ目は9件で「次へ」が2ページ目へ。2ページ目は1件で「前へ」', async ({ page }) => {
    const ids = wpCli(['post', 'generate', '--post_type=spc_case', '--count=7', '--format=ids'])
      .split(/\s+/)
      .filter(Boolean);
    expect(ids).toHaveLength(7);
    try {
      await page.goto(ARCHIVE);
      await expect(page.locator('main article.spc-diagnosis')).toHaveCount(9);
      const next = page.locator('nav.pagination a.next');
      await expect(next).toHaveCount(1);
      await expect(next).toHaveText('次へ');
      await expect(page.locator('nav.pagination a.prev')).toHaveCount(0);
      expect(await page.locator('nav.pagination').textContent()).not.toMatch(/[→←»«>]/);
      await next.click();
      await expect(page).toHaveURL(/\/cases\/page\/2\/$/);
      await expect(page.locator('main article.spc-diagnosis')).toHaveCount(1);
      await expect(page.locator('nav.pagination a.prev')).toHaveText('前へ');
      await expect(page.locator('h1')).toHaveText('施工事例');
    } finally {
      wpCli(['post', 'delete', ...ids, '--force']);
    }
  });

  test('全部ゴミ箱に入れると、200・案内文・相談ブロックだけで、robots に noindex', async ({ page }) => {
    const ids = publishedCaseIds();
    expect(ids.length).toBeGreaterThan(0);
    try {
      trash(ids);
      const res = await page.goto(ARCHIVE);
      expect(res?.status()).toBe(200);
      await expect(page.locator('h1')).toHaveText('施工事例');
      await expect(page.locator('.spc-breadcrumbs')).toContainText('ホーム');
      await expect(page.locator('main .spc-diagnosis')).toHaveCount(0);
      await expect(page.locator('main')).toContainText('掲載できる施工事例を準備しています');
      await expect(page.locator('.spc-cta-block')).toHaveCount(1);
      await expect(page.locator('nav.pagination')).toHaveCount(0);
      const robots = await page.locator('meta[name="robots"]').getAttribute('content');
      expect(robots).toContain('noindex');
      expect(robots).toContain('follow');
    } finally {
      untrash(ids);
    }
    await page.goto(ARCHIVE);
    await expect(page.locator('main .spc-diagnosis')).toHaveCount(3);
    const robots = (await page.locator('meta[name="robots"]').getAttribute('content')) ?? '';
    expect(robots).not.toContain('noindex');
  });

  test('CTAはヘッダー・相談ブロック・固定CTA・QRモーダルだけ、NG表現なし、375pxで横スクロールなし', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(ARCHIVE);
    const locations = await ctaLocations(page);
    for (const loc of locations) {
      expect(['header', 'final', 'sticky', 'qr_modal']).toContain(loc);
    }
    expect(locations.filter((l) => l === 'final')).toHaveLength(1);
    await expectNoNgWords(page);
    await expectNoHorizontalScroll(page);
  });
});

test.describe('施工事例の詳細（/cases/<slug>/）', () => {
  test('h1は事例タイトル、パンくずは「ホーム／施工事例／タイトル」、項目名7つ、診断票の見出しは p', async ({ page }) => {
    const path = await firstCasePath(page);
    await page.goto(path);
    await expect(page.locator('h1')).toHaveCount(1);
    const title = await titleOf(page);
    expect(title.length).toBeGreaterThan(0);
    await expect(page.locator('body')).toHaveAttribute('data-page-type', 'case');
    await expect(page.locator('.spc-page__title-row .spc-ph-badge')).toHaveCount(1);

    const crumbs = (await page.locator('.spc-breadcrumbs li').allTextContents()).map((t) => t.replace(/\s+/g, ' ').trim());
    expect(crumbs).toEqual(['ホーム', '施工事例', title]);

    await expect(page.locator('main .spc-diagnosis')).toHaveCount(1);
    await expect(page.locator('.spc-diagnosis')).toHaveClass(/spc-diagnosis--wide/);
    await expect(page.locator('.spc-diagnosis p.spc-diagnosis__no')).toHaveCount(1);
    await expect(page.locator('main h2.spc-diagnosis__no, main h3.spc-diagnosis__no')).toHaveCount(0);
    expect((await page.locator('.spc-diagnosis__row dt').allTextContents()).map((t) => t.trim())).toEqual(ROW_LABELS);
  });

  test('Before/After の切り替えが動く', async ({ page }) => {
    await page.goto(await firstCasePath(page));
    const after = page.locator('[data-spc-ba-show="after"]');
    const before = page.locator('[data-spc-ba-show="before"]');
    await expect(after).toBeVisible();
    await expect(before).toHaveAttribute('aria-pressed', 'true');
    await after.click();
    await expect(after).toHaveAttribute('aria-pressed', 'true');
    await expect(before).toHaveAttribute('aria-pressed', 'false');
    await expect(page.locator('[data-spc-ba-pane="after"]')).toBeVisible();
    await expect(page.locator('[data-spc-ba-pane="before"]')).toBeHidden();
  });

  test('JSON-LD の Article：headline は h1 と同じ。公開日・更新日・publisher・mainEntityOfPage があり、画像が無ければ image は無い', async ({ page }) => {
    const path = await firstCasePath(page);
    await page.goto(path);
    const title = await titleOf(page);
    const graph = await jsonLdGraph(page);
    const article = graph.find((n) => n['@type'] === 'Article');
    expect(article).toBeDefined();
    expect(article.headline).toBe(title);
    expect(article.datePublished).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/);
    expect(article.dateModified).toMatch(/^\d{4}-\d{2}-\d{2}T/);
    expect(article.publisher['@id']).toMatch(/\/#organization$/);
    expect(new URL(article.mainEntityOfPage).pathname).toBe(path);
    expect(article).not.toHaveProperty('image');
    expect(graph.find((n) => n['@type'] === 'BreadcrumbList')).toBeDefined();
  });

  test('1280pxで写真が項目の左にある。375pxでは写真の下に項目、横スクロールなし', async ({ page }) => {
    const path = await firstCasePath(page);
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(path);
    const photos = await page.locator('.spc-diagnosis__photos').boundingBox();
    const rows = await page.locator('.spc-diagnosis__rows').boundingBox();
    expect(photos && rows).toBeTruthy();
    expect(photos!.x).toBeLessThan(rows!.x);
    expect(photos!.x + photos!.width).toBeLessThanOrEqual(rows!.x + 1);

    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(path);
    const sp = await page.locator('.spc-diagnosis__photos').boundingBox();
    const spRows = await page.locator('.spc-diagnosis__rows').boundingBox();
    expect(spRows!.y).toBeGreaterThan(sp!.y + sp!.height - 1);
    await expectNoHorizontalScroll(page);
  });

  test('前後の事例へのリンクと一覧へのリンク。無い側のリンクは出さない', async ({ page }) => {
    await page.goto(ARCHIVE);
    const hrefs = await page
      .locator('.spc-diagnosis__no a')
      .evaluateAll((els) => els.map((el) => (el as HTMLAnchorElement).pathname));
    expect(hrefs).toHaveLength(3);
    const seen: Array<{ prev: number; next: number }> = [];
    for (const href of hrefs) {
      await page.goto(href);
      const nav = page.locator('nav.spc-case-nav');
      await expect(nav).toHaveCount(1);
      await expect(nav.getByRole('link', { name: '施工事例の一覧へ' })).toHaveAttribute('href', /\/cases\/$/);
      const prev = await nav.getByRole('link', { name: '前の事例' }).count();
      const next = await nav.getByRole('link', { name: '次の事例' }).count();
      seen.push({ prev, next });
      expect(await nav.textContent()).not.toMatch(/[→←»«>]/);
    }
    // 3件：最初は「前」なし、最後は「次」なし、真ん中は両方。
    expect(seen.filter((s) => s.prev === 0)).toHaveLength(1);
    expect(seen.filter((s) => s.next === 0)).toHaveLength(1);
    expect(seen.filter((s) => s.prev === 1 && s.next === 1)).toHaveLength(1);
  });

  test('CTAは相談ブロックの1つだけ（ほかはヘッダー・固定CTA・QR）、NG表現なし', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(await firstCasePath(page));
    const locations = await ctaLocations(page);
    for (const loc of locations) {
      expect(['header', 'final', 'sticky', 'qr_modal']).toContain(loc);
    }
    expect(locations.filter((l) => l === 'final')).toHaveLength(1);
    await expectNoNgWords(page);
  });
});
