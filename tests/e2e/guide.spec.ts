import { test, expect } from '@playwright/test';
import { ctaLocations, expectNoHorizontalScroll, expectNoNgWords, jsonLdGraph } from './helpers/page-checks';
import { wpCli } from './helpers/wp';

const PATH = '/guide/';

// DESIGN.md §4 の並びと地（paper = 白、mist = 灰白）。
const ORDER: Array<{ cls: string; tone: 'paper' | 'mist' }> = [
  { cls: 'spc-flow', tone: 'paper' },
  { cls: 'spc-guide-photos', tone: 'mist' },
  { cls: 'spc-guide-packing', tone: 'paper' },
  { cls: 'spc-faq', tone: 'mist' },
];

// spc-core の分類の選択肢の順（ラベル）。
const GROUP_LABELS = ['送料・配送', '料金', 'お支払い', '写真相談', 'シミ抜き', 'ダウン・コート', 'その他'];

async function faqSummaries(page: import('@playwright/test').Page): Promise<string[]> {
  return (
    await page.locator('section.spc-faq summary').evaluateAll((els) =>
      els.map((el) => {
        const clone = el.cloneNode(true) as HTMLElement;
        clone.querySelectorAll('.spc-ph-badge').forEach((b) => b.remove());
        return clone.textContent ?? '';
      }),
    )
  ).map((t) => t.replace(/\s+/g, ' ').trim());
}

test.describe('ご利用の流れ・よくある質問（/guide/）', () => {
  test('h1は1つ、data-page-type="guide"、#flow と #faq、流れは7項目、セクションの順と地', async ({ page }) => {
    await page.goto(PATH);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('body')).toHaveAttribute('data-page-type', 'guide');
    await expect(page.locator('#flow')).toHaveCount(1);
    await expect(page.locator('#faq')).toHaveCount(1);
    await expect(page.locator('#flow ol > li')).toHaveCount(7);

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
    await expect(page.locator('section.spc-guide-photos dl dt')).toHaveCount(3);
    await expect(page.locator('section.spc-guide-photos dl dd')).toHaveCount(3);
    await expect(page.locator('section.spc-guide-packing ul > li')).toHaveCount(4);
  });

  test('FAQ の h3 は分類の順、FAQ のない分類は出ない。details は全件', async ({ page }) => {
    await page.goto(PATH);
    const h3 = (await page.locator('section.spc-faq h3').allTextContents()).map((t) => t.trim());
    expect(h3.length).toBeGreaterThan(0);
    const idx = h3.map((t) => GROUP_LABELS.indexOf(t));
    for (const i of idx) expect(i).toBeGreaterThanOrEqual(0);
    expect(idx).toEqual([...idx].sort((a, b) => a - b));
    expect(new Set(h3).size).toBe(h3.length);
    // 仮データでは「料金」の FAQ は無い。
    expect(h3).not.toContain('料金');
    expect(h3[0]).toBe('送料・配送');
    expect(h3[h3.length - 1]).toBe('その他');

    const total = Number(wpCli(['post', 'list', '--post_type=spc_faq', '--post_status=publish', '--format=count']));
    await expect(page.locator('section.spc-faq details')).toHaveCount(total);
    await expect(page.locator('section.spc-faq h3 + details')).toHaveCount(h3.length);
  });

  test('分類を空にしたFAQは「その他」の下に出る', async ({ page }) => {
    test.setTimeout(120_000);
    const title = '分類なしのテスト質問';
    const id = wpCli(['post', 'create', '--post_type=spc_faq', '--post_status=publish', `--post_title=${title}`, '--porcelain']).trim();
    try {
      await page.goto(PATH);
      const last = page.locator('section.spc-faq .spc-faq__group').last();
      await expect(last.locator('h3')).toHaveText('その他');
      await expect(last.locator('summary', { hasText: title })).toHaveCount(1);
    } finally {
      wpCli(['post', 'delete', id, '--force']);
    }
  });

  test('JSON-LD の FAQPage の質問が全 summary と一致', async ({ page }) => {
    await page.goto(PATH);
    const summaries = await faqSummaries(page);
    const graph = await jsonLdGraph(page);
    const faqs = graph.filter((n) => n['@type'] === 'FAQPage');
    expect(faqs).toHaveLength(1);
    expect(faqs[0]['@id']).toMatch(/\/guide\/#faq$/);
    const names = (faqs[0].mainEntity as Array<{ name: string }>).map((q) => q.name.replace(/\s+/g, ' ').trim());
    // ページは分類ごとに並べ替えるため、順番は問わず同じ質問であることを見る。
    expect(names).toHaveLength(summaries.length);
    expect([...names].sort()).toEqual([...summaries].sort());
    expect(graph.find((n) => n['@type'] === 'BreadcrumbList')).toBeDefined();
  });

  test('トップの「よくある質問をすべて見る」から #faq が画面内に出る', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('link', { name: 'よくある質問をすべて見る' }).click();
    await expect(page).toHaveURL(/\/guide\/#faq$/);
    await expect(page.locator('#faq')).toBeInViewport();
  });

  test('ヘッダーの「ご利用の流れ」から #flow が画面内に出る', async ({ page }) => {
    await page.goto('/');
    await page.locator('header a[href$="/guide/#flow"]').first().click();
    await expect(page).toHaveURL(/\/guide\/#flow$/);
    await expect(page.locator('#flow')).toBeInViewport();
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
