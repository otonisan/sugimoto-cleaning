import { test, expect } from '@playwright/test';
import { ctaLocations, expectNoHorizontalScroll, expectNoNgWords } from './helpers/page-checks';
import { wpCli } from './helpers/wp';

const PATH = '/price/';

// DESIGN.md §4 の並びと地（paper = 白、mist = 灰白）。
const ORDER: Array<{ cls: string; tone: 'paper' | 'mist' }> = [
  { cls: 'spc-price-page-steps', tone: 'paper' },
  { cls: 'spc-price-page-items', tone: 'mist' },
  { cls: 'spc-price-page-shipping', tone: 'paper' },
  { cls: 'spc-price-page-payment', tone: 'mist' },
];

async function sectionOrder(page: import('@playwright/test').Page) {
  const known = ORDER.map((o) => o.cls);
  return page.locator('main section.spc-section').evaluateAll(
    (els, classes) =>
      els
        .map((el) => ({
          cls: classes.find((c) => el.classList.contains(c)) ?? '',
          mist: el.classList.contains('spc-section--mist'),
        }))
        .filter((s) => s.cls !== ''),
    known,
  );
}

async function rowsOf(page: import('@playwright/test').Page, cls: string) {
  return page.locator(`section.${cls} tbody tr`).evaluateAll((trs) =>
    trs.map((tr) => ({
      name: (tr.querySelector('th')?.textContent ?? '').trim(),
      value: (tr.querySelector('.spc-price__value')?.textContent ?? '').trim(),
      badge: tr.querySelectorAll('.spc-ph-badge').length,
    })),
  );
}

test.describe('料金・送料（/price/）', () => {
  test('h1は1つ、data-page-type="price"、セクションの順と地（白と灰白が交互）', async ({ page }) => {
    await page.goto(PATH);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('body')).toHaveAttribute('data-page-type', 'price');
    const sections = await sectionOrder(page);
    expect(sections.map((s) => s.cls)).toEqual(ORDER.map((o) => o.cls));
    expect(sections.map((s) => (s.mist ? 'mist' : 'paper'))).toEqual(ORDER.map((o) => o.tone));
    for (const o of ORDER) {
      await expect(page.locator(`main section.${o.cls} h2`)).toHaveCount(1);
    }
    await expect(page.locator('main section.spc-price-page-steps ol.spc-price__steps > li')).toHaveCount(3);
  });

  test('品目別の表は4行、送料・手数料の表は3行。仮の値と仮バッジ', async ({ page }) => {
    await page.goto(PATH);
    const items = await rowsOf(page, 'spc-price-page-items');
    expect(items).toHaveLength(4);
    const down = items.find((r) => r.name === '（仮）高級ダウン');
    expect(down?.value).toBe('15,000円〜');
    for (const r of items) expect(r.badge).toBeGreaterThanOrEqual(1);

    const shipping = await rowsOf(page, 'spc-price-page-shipping');
    expect(shipping).toHaveLength(3);
    expect(shipping.find((r) => r.name === '（仮）診断料')?.value).toBe('無料（仮）');
    expect(shipping.find((r) => r.name === '（仮）未承認時の返送料')?.value).toBe('実費（仮）');
    for (const r of shipping) expect(r.badge).toBeGreaterThanOrEqual(1);
  });

  test('表の caption は見出しと同じ文字なので読み上げ専用。離島の注記あり、自分自身へのリンクなし', async ({ page }) => {
    await page.goto(PATH);
    for (const cls of ['spc-price-page-items', 'spc-price-page-shipping']) {
      const caption = page.locator(`section.${cls} caption`);
      await expect(caption).toHaveClass(/screen-reader-text/);
      await expect(caption).toHaveText(await page.locator(`section.${cls} h2`).innerText());
    }
    await expect(page.locator('section.spc-price-page-shipping .spc-price__footnote')).toContainText('離島');
    await expect(page.getByRole('link', { name: '料金・送料の詳細' })).toHaveCount(0);
    await expect(page.locator('main .spc-price__disclaimer')).toHaveCount(1);
  });

  test('お支払いとキャンセルは dl の3項目で、金額を書かない', async ({ page }) => {
    await page.goto(PATH);
    const sec = page.locator('section.spc-price-page-payment');
    await expect(sec.locator('dl dt')).toHaveCount(3);
    await expect(sec.locator('dl dd')).toHaveCount(3);
    await expect(sec.locator('dl dt').nth(0)).toHaveText('お支払いの時期');
    await expect(sec.locator('dl dt').nth(2)).toHaveText('お見積りのあとにお断りいただいた場合');
    expect(await sec.innerText()).not.toMatch(/[0-9,]+円/);
  });

  test('LP の料金セクションは「料金の目安」の見出しと3段のまま（共通部品）', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('section.spc-price ol.spc-price__steps > li')).toHaveCount(3);
    await expect(page.locator('section.spc-price caption.screen-reader-text')).toHaveCount(0);
  });

  test('料金を全部ゴミ箱に入れると、品目別・送料の2セクションは出ず、残りは残る。空の h2 なし', async ({ page }) => {
    test.setTimeout(120_000);
    const ids = wpCli(['post', 'list', '--post_type=spc_price', '--post_status=publish', '--format=ids']).trim().split(/\s+/).filter(Boolean);
    expect(ids.length).toBeGreaterThan(0);
    try {
      wpCli(['post', 'update', ...ids, '--post_status=trash']);
      await page.goto(PATH);
      await expect(page.locator('section.spc-price-page-items')).toHaveCount(0);
      await expect(page.locator('section.spc-price-page-shipping')).toHaveCount(0);
      await expect(page.locator('section.spc-price-page-steps')).toHaveCount(1);
      await expect(page.locator('section.spc-price-page-payment')).toHaveCount(1);
      // 表が出ないので、表を指す一文も出さない。
      await expect(page.locator('section.spc-price-page-payment')).not.toContainText('「送料・手数料」の表');
      const empty = await page.locator('main h1, main h2, main h3').evaluateAll(
        (els) => els.filter((e) => (e.textContent ?? '').trim() === '').length,
      );
      expect(empty).toBe(0);
    } finally {
      wpCli(['post', 'update', ...ids, '--post_status=publish']);
    }
  });

  test('注記に60文字の値を入れても375pxで横スクロールなし', async ({ page }) => {
    test.setTimeout(120_000);
    const id = wpCli(['post', 'list', '--post_type=spc_price', '--post_status=publish', '--orderby=ID', '--order=ASC', '--posts_per_page=1', '--format=ids']).trim().split(/\s+/)[0];
    const original = wpCli(['post', 'meta', 'get', id, 'spc_note']);
    try {
      wpCli(['post', 'meta', 'update', id, 'spc_note', 'A'.repeat(60)]);
      await page.setViewportSize({ width: 375, height: 812 });
      await page.goto(PATH);
      await expect(page.locator('section.spc-price-page-items')).toContainText('A'.repeat(60));
      await expectNoHorizontalScroll(page);
    } finally {
      wpCli(['post', 'meta', 'update', id, 'spc_note', original]);
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
