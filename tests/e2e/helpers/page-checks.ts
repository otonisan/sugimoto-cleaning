import { expect, type Page } from '@playwright/test';

// DESIGN.md §8 の NG表現（誇大表現・景品表示法への配慮）と、感嘆符・矢印。
export const NG_WORDS = ['！', '!', '→', '必ず落ちる', '100%', 'どんなシミも', '完全に元通り', 'No.1', '日本一', '最高級', '今だけ', '残りわずか'];

/** main の本文に NG表現が出ていない。 */
export async function expectNoNgWords(page: Page): Promise<void> {
  const text = await page.locator('main').innerText();
  for (const ng of NG_WORDS) {
    expect(text, ng).not.toContain(ng);
  }
}

/** 横スクロールが出ていない（scrollWidth <= innerWidth）。 */
export async function expectNoHorizontalScroll(page: Page): Promise<void> {
  const { scrollWidth, innerWidth } = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth: window.innerWidth,
  }));
  expect(scrollWidth).toBeLessThanOrEqual(innerWidth);
}

/** JSON-LD（1つだけのはず）の @graph。 */
export async function jsonLdGraph(page: Page): Promise<any[]> {
  const scripts = page.locator('script[type="application/ld+json"]');
  await expect(scripts).toHaveCount(1);
  const data = JSON.parse((await scripts.textContent()) ?? '{}');
  expect(data['@context']).toBe('https://schema.org');
  return data['@graph'] as any[];
}

/** ページ内のすべての CTA の設置場所（data-cta-location）。 */
export async function ctaLocations(page: Page): Promise<string[]> {
  return page.locator('[data-spc-cta]').evaluateAll((els) => els.map((el) => el.getAttribute('data-cta-location') ?? ''));
}
