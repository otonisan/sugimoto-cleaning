import { test, expect } from '@playwright/test';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { ctaLocations, expectNoHorizontalScroll, expectNoNgWords, jsonLdGraph } from './helpers/page-checks';
import { wpCli } from './helpers/wp';

const PATH = '/about/';
const SUMI = 'rgb(35, 33, 30)'; // --color-sumi (#23211E)

// DESIGN.md §4 の並び（職人は墨、作業場は白、会社概要は灰白）。
const ORDER: Array<{ cls: string; tone: 'sumi' | 'paper' | 'mist' }> = [
  { cls: 'spc-about-craftsmen', tone: 'sumi' },
  { cls: 'spc-about-workshop', tone: 'paper' },
  { cls: 'spc-about-company', tone: 'mist' },
];

/** 固定ページの本文を、ファイル経由で（二重引用符を含む HTML でも）書き換える。 */
function setPageContent(id: string, html: string): void {
  // wp-env のコンテナに見えるのはテーマのフォルダだけ。一時ファイルはそこに置き、使い終わったら消す。
  const dir = mkdtempSync(path.join(process.cwd(), 'wp-content', 'themes', 'spc-theme', 'tmp-about-'));
  try {
    writeFileSync(path.join(dir, 'content.html'), html, 'utf8');
    const rel = `/var/www/html/wp-content/themes/spc-theme/${path.basename(dir)}/content.html`;
    wpCli(['post', 'update', id, rel]);
  } finally {
    rmSync(dir, { recursive: true, force: true });
  }
}

test.describe('職人・運営会社（/about/）', () => {
  test('h1は1つ、data-page-type="about"、セクションの順と地、墨地はページに1つだけ', async ({ page }) => {
    await page.goto(PATH);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('body')).toHaveAttribute('data-page-type', 'about');

    const known = ORDER.map((o) => o.cls);
    const sections = await page.locator('main section.spc-section').evaluateAll(
      (els, classes) =>
        els
          .map((el) => ({
            cls: classes.find((c) => el.classList.contains(c)) ?? '',
            sumi: el.classList.contains('spc-section--sumi'),
            mist: el.classList.contains('spc-section--mist'),
          }))
          .filter((s) => s.cls !== ''),
      known,
    );
    expect(sections.map((s) => s.cls)).toEqual(known);
    expect(sections.map((s) => (s.sumi ? 'sumi' : s.mist ? 'mist' : 'paper'))).toEqual(ORDER.map((o) => o.tone));
    for (const o of ORDER) {
      await expect(page.locator(`main section.${o.cls} h2`)).toHaveCount(1);
    }

    // 墨地（背景色が --color-sumi の section）はページ全体で1つ。
    const dark = await page.locator('section').evaluateAll(
      (els, sumi) => els.filter((el) => getComputedStyle(el).backgroundColor === sumi).length,
      SUMI,
    );
    expect(dark).toBe(1);
  });

  test('職人：名前（仮）と顔写真の仮枠、名前は h3', async ({ page }) => {
    await page.goto(PATH);
    const section = page.locator('section.spc-about-craftsmen');
    const people = section.locator('.spc-craftsmen__person');
    const count = await people.count();
    expect(count).toBeGreaterThanOrEqual(1);
    await expect(section.locator('h3.spc-craftsmen__name', { hasText: '職人名（仮）' }).first()).toBeVisible();
    await expect(section.locator('.spc-ph-image[aria-label="仮｜職人の顔写真 4:5"]')).toHaveCount(count);
  });

  test('作業場：写真枠は3つ（キャプション付き）と使い分けの説明', async ({ page }) => {
    await page.goto(PATH);
    const section = page.locator('section.spc-about-workshop');
    const labels = await section.locator('.spc-ph-image').evaluateAll((els) => els.map((e) => e.getAttribute('aria-label') ?? ''));
    expect(labels).toHaveLength(3);
    for (const label of labels) {
      expect(label.startsWith('仮｜')).toBe(true);
      expect(label).toContain('3:2');
    }
    await expect(section.locator('figcaption')).toHaveCount(3);
    await expect(section).toContainText('ドライクリーニング');
    await expect(section).toContainText('ウェットクリーニング');
  });

  test('会社概要：表に「有限会社杉本クリーニング商会」「1954年」、お知らせ・補足は出ない', async ({ page }) => {
    await page.goto(PATH);
    const table = page.locator('section.spc-about-company .spc-table-content table');
    await expect(table).toHaveCount(1);
    await expect(table).toContainText('有限会社杉本クリーニング商会');
    await expect(table).toContainText('1954年');
    await expect(page.getByRole('heading', { name: 'お知らせ・補足' })).toHaveCount(0);
    await expect(page.locator('.spc-page-notes')).toHaveCount(0);
    // .spc-table と同じ見た目（横罫線・固定レイアウト）
    const style = await table.evaluate((el) => {
      const cs = getComputedStyle(el);
      return { layout: cs.tableLayout, collapse: cs.borderCollapse, top: cs.borderTopStyle };
    });
    expect(style).toEqual({ layout: 'fixed', collapse: 'collapse', top: 'solid' });
  });

  test('JSON-LD：Person の数はページの職人と同じ。worksFor は Organization、住所・電話なし', async ({ page }) => {
    await page.goto(PATH);
    const shown = await page.locator('section.spc-about-craftsmen .spc-craftsmen__person').count();
    const graph = await jsonLdGraph(page);
    const persons = graph.filter((n) => n['@type'] === 'Person');
    expect(shown).toBeGreaterThanOrEqual(1);
    expect(persons).toHaveLength(shown);
    const names = await page.locator('section.spc-about-craftsmen .spc-craftsmen__name').allTextContents();
    expect(persons.map((p) => p.name)).toEqual(names.map((n) => n.trim()));
    for (const p of persons) {
      expect(p.worksFor['@id']).toMatch(/\/#organization$/);
      expect(p.jobTitle).toBe('役職（仮）');
    }
    const org = graph.find((n) => n['@type'] === 'Organization');
    expect(org.foundingDate).toBe('1954');
    expect(org.address).toBeUndefined();
    expect(org.telephone).toBeUndefined();
    expect(graph.find((n) => n['@type'] === 'BreadcrumbList')).toBeDefined();
  });

  test('会社概要の値が80文字でも375pxで横スクロールなし（本文は元に戻す）', async ({ page }) => {
    test.setTimeout(120_000);
    const id = wpCli(['post', 'list', '--post_type=page', '--name=about', '--field=ID']).split(/\s+/)[0];
    const original = wpCli(['post', 'get', id, '--field=post_content']);
    try {
      const long = 'あ'.repeat(40) + 'ABCDEFGHIJ'.repeat(4);
      setPageContent(id, `<table><tbody><tr><th>所在地</th><td>${long}</td></tr><tr><th>会社名</th><td>有限会社杉本クリーニング商会</td></tr></tbody></table>`);
      await page.setViewportSize({ width: 375, height: 812 });
      await page.goto(PATH);
      await expect(page.locator('section.spc-about-company')).toContainText(long);
      await expectNoHorizontalScroll(page);
    } finally {
      setPageContent(id, original);
    }
  });

  test('本文が空なら会社概要はセクションごと出ない（本文は元に戻す）', async ({ page }) => {
    test.setTimeout(120_000);
    const id = wpCli(['post', 'list', '--post_type=page', '--name=about', '--field=ID']).split(/\s+/)[0];
    const original = wpCli(['post', 'get', id, '--field=post_content']);
    try {
      wpCli(['post', 'update', id, '--post_content=']);
      await page.goto(PATH);
      await expect(page.locator('section.spc-about-company')).toHaveCount(0);
      await expect(page.getByRole('heading', { name: '会社概要' })).toHaveCount(0);
      await expect(page.locator('section.spc-about-craftsmen')).toHaveCount(1);
    } finally {
      setPageContent(id, original);
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
