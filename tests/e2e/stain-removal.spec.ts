import { test, expect } from '@playwright/test';
import { ctaLocations, expectNoHorizontalScroll, expectNoNgWords, jsonLdGraph } from './helpers/page-checks';
import { wpCli } from './helpers/wp';

const PATH = '/stain-removal/';

// DESIGN.md §4 の並びと地（paper = 白、mist = 灰白）。
const ORDER: Array<{ cls: string; tone: 'paper' | 'mist' }> = [
  { cls: 'spc-stain-examples', tone: 'paper' },
  { cls: 'spc-stain-factors', tone: 'mist' },
  { cls: 'spc-stain-yellowing', tone: 'paper' },
  { cls: 'spc-limits', tone: 'mist' },
  { cls: 'spc-price', tone: 'paper' },
  { cls: 'spc-cases', tone: 'mist' },
  { cls: 'spc-faq', tone: 'paper' },
];

test.describe('特殊シミ抜き（/stain-removal/）', () => {
  test('h1は1つ、data-page-type="service"、セクションの順と地（白と灰白が交互）', async ({ page }) => {
    await page.goto(PATH);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('body')).toHaveAttribute('data-page-type', 'service');

    const known = ORDER.map((o) => o.cls);
    const sections = await page.locator('main section.spc-section').evaluateAll(
      (els, classes) =>
        els
          .map((el) => ({
            cls: classes.find((c) => el.classList.contains(c)) ?? '',
            mist: el.classList.contains('spc-section--mist'),
          }))
          .filter((s) => s.cls !== ''),
      known,
    );
    expect(sections.map((s) => s.cls)).toEqual(known);
    expect(sections.map((s) => (s.mist ? 'mist' : 'paper'))).toEqual(ORDER.map((o) => o.tone));
    // 見出しは各セクションに h2 が1つ。
    for (const o of ORDER) {
      await expect(page.locator(`main section.${o.cls} h2`)).toHaveCount(1);
    }
  });

  test('相談できるシミの例は8項目、落ちやすさを左右するものは4項目と仮の写真枠', async ({ page }) => {
    await page.goto(PATH);
    await expect(page.locator('section.spc-stain-examples ul[role="list"] > li')).toHaveCount(8);
    await expect(page.locator('section.spc-stain-examples')).toContainText('写真を拝見して、落とせる可能性をお伝えします');
    await expect(page.locator('section.spc-stain-factors dl dt')).toHaveCount(4);
    await expect(page.locator('section.spc-stain-factors dl dd')).toHaveCount(4);
    await expect(page.locator('section.spc-stain-factors .spc-ph-image[aria-label="仮｜手元・作業の写真 3:2"]')).toHaveCount(1);
    await expect(page.locator('section.spc-stain-yellowing .spc-ph-image[aria-label="仮｜黄ばみ・汗汚れの写真 3:2"]')).toHaveCount(1);
    const labels = await page.locator('main .spc-ph-image').evaluateAll((els) => els.map((e) => e.getAttribute('aria-label') ?? ''));
    expect(labels.length).toBeGreaterThanOrEqual(2);
    for (const label of labels) {
      expect(label.startsWith('仮｜')).toBe(true);
    }
  });

  test('トップの「黄ばみ・汗汚れ」リンクから #yellowing が画面内に出る', async ({ page }) => {
    await page.goto('/');
    await page.locator('a.spc-menu__link', { hasText: '黄ばみ・汗汚れ' }).click();
    await expect(page).toHaveURL(/\/stain-removal\/#yellowing$/);
    await expect(page.locator('#yellowing')).toBeInViewport();
  });

  test('料金表は「（仮）特殊シミ抜き」「（仮）黄ばみ・汗汚れ」の2行だけ', async ({ page }) => {
    await page.goto(PATH);
    const names = await page.locator('section.spc-price tbody th[scope="row"]').allTextContents();
    expect(names.map((n) => n.trim())).toEqual(['（仮）特殊シミ抜き', '（仮）黄ばみ・汗汚れ']);
    await expect(page.locator('section.spc-price .spc-price__step')).toHaveCount(0);
  });

  test('関連する施工事例：仮-001 と 仮-003 だけ（仮-002 はない）、診断票の見出しは h3', async ({ page }) => {
    await page.goto(PATH);
    const section = page.locator('section.spc-cases');
    await expect(section.locator('h2')).toHaveText('関連する施工事例');
    await expect(section).toContainText(/No\.\s*仮-001/);
    await expect(section).toContainText(/No\.\s*仮-003/);
    await expect(section).not.toContainText(/No\.\s*仮-002/);
    await expect(section.locator('h3').first()).toBeVisible();
    await expect(section.locator('article h2')).toHaveCount(0);
    await expect(section.locator('[data-spc-cta]')).toHaveCount(0);
    await expect(section.locator('a[href$="/cases/"]')).toHaveCount(1);
  });

  test('FAQは分類 stain の2件だけ。JSON-LD の FAQPage と Service がページと一致', async ({ page }) => {
    await page.goto(PATH);
    // 仮バッジ（「仮」）を除いた質問文。
    const summaries = (
      await page.locator('section.spc-faq summary').evaluateAll((els) =>
        els.map((el) => {
          const clone = el.cloneNode(true) as HTMLElement;
          clone.querySelectorAll('.spc-ph-badge').forEach((b) => b.remove());
          return clone.textContent ?? '';
        }),
      )
    ).map((t) => t.replace(/\s+/g, ' ').trim());
    expect(summaries).toHaveLength(2);

    const graph = await jsonLdGraph(page);
    const faq = graph.find((n) => n['@type'] === 'FAQPage');
    expect(faq).toBeDefined();
    const names = (faq.mainEntity as Array<{ name: string }>).map((q) => q.name.replace(/\s+/g, ' ').trim());
    expect(names).toEqual(summaries);

    const service = graph.find((n) => n['@type'] === 'Service');
    expect(service).toBeDefined();
    expect(service.name).toBe('特殊シミ抜き');
    expect(service.areaServed).toBe('JP');
    expect(service.provider['@id']).toMatch(/#organization$/);
    expect(graph.find((n) => n['@type'] === 'BreadcrumbList')).toBeDefined();
  });

  test('関連メニューの事例を全部 down にすると、関連する施工事例セクションごと出ない', async ({ page }) => {
    test.setTimeout(180_000);
    const rows = JSON.parse(wpCli(['post', 'list', '--post_type=spc_case', '--post_status=any', '--fields=ID', '--format=json'])) as Array<{ ID: number }>;
    const before = new Map<number, string>();
    try {
      for (const r of rows) {
        before.set(r.ID, wpCli(['post', 'meta', 'get', String(r.ID), 'spc_case_menu']));
      }
      for (const r of rows) {
        wpCli(['post', 'meta', 'update', String(r.ID), 'spc_case_menu', 'down']);
      }
      await page.goto(PATH);
      await expect(page.locator('section.spc-cases')).toHaveCount(0);
      await expect(page.getByRole('heading', { name: '関連する施工事例' })).toHaveCount(0);
    } finally {
      for (const [id, menu] of before) {
        wpCli(['post', 'meta', 'update', String(id), 'spc_case_menu', menu]);
      }
    }
  });

  test('お知らせ・補足：本文が空なら見出しなし、入力すると見出しと本文が出る', async ({ page }) => {
    test.setTimeout(120_000);
    const id = wpCli(['post', 'list', '--post_type=page', '--name=stain-removal', '--field=ID']).split(/\s+/)[0];
    const original = wpCli(['post', 'get', id, '--field=post_content']);
    try {
      wpCli(['post', 'update', id, '--post_content=']);
      await page.goto(PATH);
      await expect(page.getByRole('heading', { name: 'お知らせ・補足' })).toHaveCount(0);

      wpCli(['post', 'update', id, '--post_content=<p>年末年始の受付について（テスト）</p>']);
      await page.goto(PATH);
      await expect(page.getByRole('heading', { level: 2, name: 'お知らせ・補足' })).toHaveCount(1);
      await expect(page.locator('.spc-page-notes')).toContainText('年末年始の受付について（テスト）');
      // 順序：FAQ → お知らせ・補足 → 相談ブロック
      const order = await page.locator('main .spc-faq, main .spc-page-notes, main .spc-cta-block').evaluateAll((els) =>
        els.map((e) => (e.classList.contains('spc-faq') ? 'faq' : e.classList.contains('spc-page-notes') ? 'notes' : 'cta')),
      );
      expect(order).toEqual(['faq', 'notes', 'cta']);
    } finally {
      wpCli(['post', 'update', id, `--post_content=${original}`]);
    }
  });

  test('CTAはヘッダー・相談ブロック・固定CTA・QRモーダルだけ、NG表現なし、375pxで横スクロールなし', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(PATH);
    const locations = await ctaLocations(page);
    for (const loc of locations) {
      expect(['header', 'final', 'sticky', 'qr_modal']).toContain(loc);
    }
    expect(locations.filter((l) => l === 'final')).toHaveLength(1);
    await expectNoNgWords(page);
    await expectNoHorizontalScroll(page);
  });
});
