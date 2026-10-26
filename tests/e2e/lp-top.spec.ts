import { test, expect, type Page } from '@playwright/test';
import { setSpcSetting } from './helpers/wp';

const H1 = '諦めかけた、その一着に。';
const SUB = '創業70年以上の技術を受け継ぐ職人が、衣類の状態を丁寧に確認します。';
const STANCE = '必ず落とすとは言いません。職人が状態を見て、可能性とリスクを正直にお伝えします。';

// 環境が遅いことがあるため、設定を書き換えるテストは長めに待つ。
test.describe.configure({ timeout: 120_000 });

function h1WritingMode(page: Page): Promise<string> {
  return page.locator('h1').evaluate((el) => getComputedStyle(el).writingMode);
}

// h1 の読み上げ用テキストが完全一致していること（縦書き・横書きどちらでも）。
async function expectExactH1(page: Page): Promise<void> {
  await expect(page.locator('h1')).toHaveCount(1);
  expect(await page.locator('h1').textContent()).toBe(H1);
  await expect(page.getByRole('heading', { level: 1, name: H1, exact: true })).toBeVisible();
}

// 375×812 の最初の画面に、要素がスクロールせずに収まっていること。
async function expectAboveFold(page: Page): Promise<void> {
  const targets = {
    h1: page.locator('h1'),
    sub: page.locator('.hero').getByText(SUB, { exact: true }),
    menu: page.locator('.hero').getByText('特殊シミ抜き', { exact: false }),
    cta: page.locator('.hero [data-cta-location="fv"]'),
  };
  for (const [name, locator] of Object.entries(targets)) {
    await expect(locator, name).toBeVisible();
    const box = await locator.boundingBox();
    expect(box, name).not.toBeNull();
    expect(box!.y, name).toBeGreaterThanOrEqual(0);
    expect(box!.y + box!.height, name).toBeLessThanOrEqual(812);
  }
}

test.describe('トップLP前半（FV・考え方・3ステップ・メニュー）', () => {
  test('h1はページに1つだけで、文言が完全一致。FVは固定CTAを隠す対象', async ({ page }) => {
    await page.goto('/');
    await expectExactH1(page);
    const hero = page.locator('section.hero');
    await expect(hero).toHaveCount(1);
    await expect(hero).toHaveAttribute('data-sticky-hide', '');
    await expect(hero.locator('h1')).toHaveCount(1);
    await expect(page.locator('h1')).toHaveCSS('font-family', /Shippori Mincho/);
    await expect(page.locator('h1')).toHaveCSS('font-weight', '700');
    await expect(hero.locator('[data-cta-location="fv"]')).toHaveCount(1);
    await expect(hero.locator('.spc-ph-image')).toHaveAttribute('aria-label', '仮｜繊維マクロ写真 16:10');
  });

  test('1280px幅：h1は縦書き（vertical-rl）', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/');
    expect(await h1WritingMode(page)).toBe('vertical-rl');
  });

  test('375px幅：縦書き設定なら vertical-rl、横書き設定なら horizontal-tb（PCは縦書きのまま）', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/');
    await expect(page.locator('section.hero')).not.toHaveClass(/hero--sp-horizontal/);
    expect(await h1WritingMode(page)).toBe('vertical-rl');

    const restore = setSpcSetting('hero_sp_writing', 'horizontal');
    try {
      await page.reload();
      await expect(page.locator('section.hero')).toHaveClass(/(^|\s)hero hero--sp-horizontal(\s|$)/);
      expect(await h1WritingMode(page)).toBe('horizontal-tb');
      await expectExactH1(page);
      // 横書きは2行（「諦めかけた、」／「その一着に。」）。
      const lines = page.locator('h1 .hero__line');
      await expect(lines).toHaveText(['諦めかけた、', 'その一着に。']);
      const [a, b] = [await lines.nth(0).boundingBox(), await lines.nth(1).boundingBox()];
      expect(b!.y).toBeGreaterThan(a!.y);
      await expectAboveFold(page);

      await page.setViewportSize({ width: 1280, height: 800 });
      expect(await h1WritingMode(page)).toBe('vertical-rl');
    } finally {
      restore();
    }
  });

  test('375×812：h1・サブコピー・「特殊シミ抜き」・FVのCTAがスクロールせずに見える', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/');
    await expectExactH1(page);
    await expectAboveFold(page);
    // 横スクロールが出ない。
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(375);
  });

  test('FV画像はぼけからピントが合う動き（900ms・ease-out）、動きを減らす設定では filter: none', async ({ page }) => {
    await page.goto('/');
    const media = page.locator('.hero__media');
    await expect(media).toHaveCount(1);
    const anim = await media.evaluate((el) => {
      const s = getComputedStyle(el);
      return { name: s.animationName, duration: s.animationDuration, timing: s.animationTimingFunction };
    });
    expect(anim).toEqual({ name: 'spc-hero-focus', duration: '0.9s', timing: 'ease-out' });
    // 自動で動くのはFV画像の1か所だけ。
    // 操作したときだけ動くもの（CTAのホバー、施工事例の施工前／施工後の切り替え [data-spc-ba] など）は数えない。
    const animated = await page.evaluate(() =>
      [...document.querySelectorAll('body *')].filter(
        (el) => getComputedStyle(el).animationName !== 'none' && !el.closest('[data-spc-ba]'),
      ).length,
    );
    expect(animated).toBe(1);

    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.reload();
    await expect(media).toHaveCSS('filter', 'none');
    await expect(media).toHaveCSS('animation-name', 'none');
  });

  test('悩みと考え方：見出し・悩み3つ・考え方の文が完全一致', async ({ page }) => {
    await page.goto('/');
    const section = page.locator('.spc-concerns');
    await expect(section.locator('h2')).toHaveCount(1);
    await expect(section.locator('ul > li')).toHaveCount(3);
    await expect(section.getByText(STANCE, { exact: true })).toBeVisible();
  });

  test('3ステップは <ol> で項目が3つ、一次回答の目安とCTAがある', async ({ page }) => {
    await page.goto('/');
    const section = page.locator('.spc-steps');
    await expect(section.locator('h2')).toHaveCount(1);
    await expect(section.locator('ol')).toHaveCount(1);
    await expect(section.locator('ol > li')).toHaveCount(3);
    await expect(section.locator('ol > li').nth(1)).toContainText('洗濯表示');
    await expect(section.locator('ol > li').nth(2)).toContainText('営業時間内24時間以内');
    await expect(section.locator('[data-cta-location="steps"]')).toHaveCount(1);
  });

  test('対応メニューの4つのリンク先', async ({ page }) => {
    await page.goto('/');
    const links = page.locator('.spc-menu a');
    await expect(links).toHaveCount(4);
    const expected = [
      ['特殊シミ抜き', '/stain-removal/'],
      ['黄ばみ・汗汚れ', '/stain-removal/#yellowing'],
      ['高級ダウン', '/down-coat/'],
      ['コート・ジャケット', '/down-coat/#coat'],
    ];
    for (const [i, [label, path]] of expected.entries()) {
      const link = links.nth(i);
      await expect(link).toHaveText(label);
      const url = new URL((await link.getAttribute('href')) ?? '', 'http://invalid/');
      expect(url.origin + url.pathname + url.hash).toBe(`http://localhost:8888${path}`);
    }
    await expect(page.locator('.spc-menu .spc-ph-image')).toHaveCount(4);
  });

  test('セクションの順番、lp.css はトップだけ、文言に「！」「→」と誇大表現がない', async ({ page }) => {
    await page.goto('/');
    const order = await page.locator('main > section').evaluateAll((els) => els.map((el) => el.className));
    expect(order.slice(0, 4).map((c) => c.split(' ').find((n) => /^(hero|spc-(concerns|steps|menu))$/.test(n)))).toEqual([
      'hero',
      'spc-concerns',
      'spc-steps',
      'spc-menu',
    ]);
    await expect(page.locator('link#spc-lp-css')).toHaveCount(1);
    const text = await page.locator('main').innerText();
    for (const ng of ['！', '!', '→', '必ず落ちる', '100%', 'どんなシミも', '完全に元通り', 'No.1', '日本一', '最高級']) {
      expect(text, ng).not.toContain(ng);
    }

    await page.goto('/price/');
    await expect(page.locator('link#spc-lp-css')).toHaveCount(0);
  });
});
