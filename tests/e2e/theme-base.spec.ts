import { test, expect, type Page } from '@playwright/test';
import { setSpcSetting, wpCli } from './helpers/wp';

const CTA_TEXT = '写真を送って無料相談';
const PAGES = ['/', '/price/', '/legal/privacy/', '/no-such-page-e2e/'];

// spc-core の spc_line_url() が返している現在のLINE URL。
function currentLineUrl(): string {
  return wpCli(['eval', 'echo spc_line_url();']);
}

// Tabキーでヘッダーの CTA にフォーカスが当たるまで移動する。
async function tabToHeaderCta(page: Page): Promise<void> {
  for (let i = 0; i < 20; i++) {
    await page.keyboard.press('Tab');
    const hit = await page.evaluate(
      () => document.activeElement?.getAttribute('data-cta-location') === 'header',
    );
    if (hit) return;
  }
  throw new Error('ヘッダーのCTAにTabで到達できません');
}

test.describe('テーマ基盤', () => {
  test('ヘッダーのCTAは spc_line_url() を参照し、設定を変えると href も変わる', async ({ page }) => {
    await page.goto('/price/');
    const cta = page.locator('header [data-cta-location="header"]');
    await expect(cta).toHaveCount(1);
    await expect(cta).toHaveAttribute('href', currentLineUrl());

    const url = 'https://lin.ee/E2Etheme6';
    const restore = setSpcSetting('line_url', url);
    try {
      await page.reload();
      await expect(cta).toHaveAttribute('href', url);
    } finally {
      restore();
    }
  });

  test('CTAの文言は完全一致で「写真を送って無料相談」で、末尾に矢印がない', async ({ page }) => {
    await page.goto('/price/');
    const ctas = page.locator('[data-spc-cta]');
    expect(await ctas.count()).toBeGreaterThanOrEqual(2); // ヘッダー＋ページ下部
    for (const text of await ctas.allTextContents()) {
      expect(text.trim()).toBe(CTA_TEXT);
    }
    await expect(page.locator('header [data-cta-location="header"]')).toHaveClass(/spc-cta--compact/);
    await expect(page.locator('[data-cta-location="final"]')).toHaveClass(/spc-cta--default/);
  });

  test('どのページにも「→」が出ていない', async ({ page }) => {
    for (const path of PAGES) {
      await page.goto(path);
      expect(await page.locator('body').innerText(), path).not.toContain('→');
    }
  });

  test('Google Fonts の stylesheet は1つだけで、2書体を含み、preconnect がある', async ({ page }) => {
    await page.goto('/price/');
    const fonts = page.locator('link[rel="stylesheet"][href*="fonts.googleapis.com"]');
    await expect(fonts).toHaveCount(1);
    const href = (await fonts.getAttribute('href')) ?? '';
    expect(href).toContain('Shippori+Mincho');
    expect(href).toContain('Zen+Kaku+Gothic+New');
    expect(href).toContain('display=swap');
    await expect(page.locator('link[rel="preconnect"][href="https://fonts.googleapis.com"]')).toHaveCount(1);
    await expect(page.locator('link[rel="preconnect"][href="https://fonts.gstatic.com"][crossorigin]')).toHaveCount(1);
  });

  test('Tabキーで移動したとき、CTAに実線のフォーカス枠が出る', async ({ page }) => {
    await page.goto('/price/');
    await tabToHeaderCta(page);
    const outline = await page.evaluate(() => {
      const s = getComputedStyle(document.activeElement as Element);
      return { style: s.outlineStyle, width: s.outlineWidth, offset: s.outlineOffset };
    });
    expect(outline).toEqual({ style: 'solid', width: '2px', offset: '3px' });
  });

  test('375px幅で横スクロールが出ず、ナビは隠れてロゴと小さいCTAだけになる', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    for (const path of PAGES) {
      await page.goto(path);
      const width = await page.evaluate(() => document.documentElement.scrollWidth);
      expect(width, path).toBeLessThanOrEqual(375);
    }
    await page.goto('/price/');
    await expect(page.locator('.spc-header__nav')).toBeHidden();
    await expect(page.locator('.spc-header__brand')).toBeVisible();
    await expect(page.locator('header [data-cta-location="header"]')).toBeVisible();
    // スマホの補足文だけが見える。
    await expect(page.locator('[data-cta-location="final"] ~ .spc-cta__note--sp')).toBeVisible();
    await expect(page.locator('[data-cta-location="final"] ~ .spc-cta__note--pc')).toBeHidden();
  });

  test('768・1024・1280px幅でナビが見え、横スクロールが出ない（1280pxは1行）', async ({ page }) => {
    for (const width of [768, 1024, 1280]) {
      await page.setViewportSize({ width, height: 900 });
      await page.goto('/price/');
      expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
      const nav = page.locator('.spc-header__nav');
      await expect(nav).toBeVisible();
      await expect(nav.getByRole('link', { name: '特殊シミ抜き' })).toHaveAttribute('href', /\/stain-removal\/$/);
      await expect(nav.getByRole('link', { name: 'ご利用の流れ' })).toHaveAttribute('href', /\/guide\/#flow$/);
    }
    // PCの補足文に切り替わる。
    await expect(page.locator('[data-cta-location="final"] ~ .spc-cta__note--pc')).toBeVisible();
    await expect(page.locator('[data-cta-location="final"] ~ .spc-cta__note--sp')).toBeHidden();
    // 1280pxではナビ・ロゴ・CTAが同じ行に並ぶ。
    const brand = await page.locator('.spc-header__brand').boundingBox();
    const navBox = await page.locator('.spc-header__nav').boundingBox();
    expect(brand && navBox && Math.abs(brand.y + brand.height / 2 - (navBox.y + navBox.height / 2))).toBeLessThan(8);
  });

  test('フッターに運営会社と法定3ページへのリンクがある', async ({ page }) => {
    await page.goto('/price/');
    const footer = page.locator('footer.spc-footer');
    await expect(footer).toContainText('運営：有限会社杉本クリーニング商会（創業1954年）');
    for (const slug of ['/legal/terms/', '/legal/tokushoho/', '/legal/privacy/', '/about/', '/guide/']) {
      await expect(footer.locator(`a[href$="${slug}"]`)).toHaveCount(1);
    }
    await expect(footer).toContainText(`© ${new Date().getFullYear()}`);
  });

  test('固定ページ：h1は1つ、仮データには仮バッジ、下部にCTA', async ({ page }) => {
    await page.goto('/price/');
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('h1')).toHaveText('料金・送料');
    await expect(page.locator('.spc-page .spc-ph-badge')).toHaveText('仮');
    await expect(page.locator('h1')).toHaveCSS('font-family', /Shippori Mincho/);
    await expect(page.locator('main [data-cta-location="final"]')).toHaveCount(1);
  });

  test('404：日本語のメッセージ、トップへのリンク、CTAがある', async ({ page }) => {
    const res = await page.goto('/no-such-page-e2e/');
    expect(res?.status()).toBe(404);
    await expect(page.locator('h1')).toHaveText('ページが見つかりませんでした。');
    await expect(page.locator('main a[href$="localhost:8888/"]')).toHaveCount(1);
    await expect(page.locator('[data-cta-location="final"]')).toHaveCount(1);
  });
});
