import { test, expect, type Page } from '@playwright/test';
import { setSpcSetting } from './helpers/wp';

// 設定を書き換えるテストがあり、環境が遅いこともあるため長めに待つ。
test.describe.configure({ timeout: 120_000 });

// lin.ee へは実際に出ない。
async function stubLine(page: Page): Promise<void> {
  await page.route('https://lin.ee/**', (route) => route.fulfill({ status: 200, contentType: 'text/html', body: 'ok' }));
}

async function dataLayer(page: Page): Promise<Array<Record<string, unknown>>> {
  return page.evaluate(() => JSON.parse(JSON.stringify((window as any).dataLayer ?? [])));
}

// 遷移を止めて dataLayer を確認するための、確認用リスナー（計測側のリスナーより後ろで動く）。
async function suppressNavigation(page: Page): Promise<void> {
  await page.evaluate(() => {
    window.addEventListener('click', (e) => e.preventDefault());
  });
}

test.describe('計測：GTM', () => {
  test('GTM ID 未設定なら googletagmanager のスクリプトも noscript もない', async ({ page }) => {
    await page.goto('/');
    const html = await page.content();
    expect(html).not.toContain('googletagmanager.com');
  });

  test('GTM ID を設定するとスクリプトと noscript iframe が出る', async ({ page }) => {
    const restore = setSpcSetting('gtm_id', 'GTM-TEST123');
    try {
      await page.goto('/');
      const head = await page.locator('head').innerHTML();
      expect(head).toContain('googletagmanager.com/gtm.js');
      expect(head).toContain('GTM-TEST123');
      const noscript = await page.locator('body > noscript').first().innerHTML();
      expect(noscript).toContain('googletagmanager.com/ns.html?id=GTM-TEST123');
    } finally {
      restore();
    }
  });

  test('GTM の読み込みをブロックしても CTA は LINE に遷移し、スクリプトエラーが出ない', async ({ page }) => {
    const restore = setSpcSetting('gtm_id', 'GTM-TEST123');
    try {
      await page.setViewportSize({ width: 375, height: 812 });
      await page.route('**/googletagmanager.com/**', (route) => route.abort());
      await stubLine(page);
      const errors: string[] = [];
      page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
      page.on('console', (m) => {
        // ブロックした GTM 自体の読み込み失敗メッセージは対象外。
        if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) {
          errors.push(`console: ${m.text()}`);
        }
      });
      await page.goto('/');
      await page.locator('.hero [data-cta-location="fv"]').click();
      await page.waitForURL(/lin\.ee/);
      expect(errors).toEqual([]);
    } finally {
      restore();
    }
  });
});

test.describe('計測：イベント', () => {
  test('LP を開くと landing_view（page_type: lp）が dataLayer に入る。LP 以外では入らない', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('body')).toHaveAttribute('data-page-type', 'lp');
    const events = await dataLayer(page);
    expect(events.filter((e) => e.event === 'landing_view')).toEqual([{ event: 'landing_view', page_type: 'lp' }]);

    await page.goto('/price/');
    expect(await page.locator('body').getAttribute('data-page-type')).toBeNull();
    expect((await dataLayer(page)).some((e) => e.event === 'landing_view')).toBe(false);
  });

  test('375px：FV の CTA クリックで line_click {cta_location: fv}、遷移先は lin.ee', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await stubLine(page);
    await page.goto('/');
    const href = await page.locator('.hero [data-cta-location="fv"]').getAttribute('href');
    expect(new URL(href!).hostname).toBe('lin.ee');

    await suppressNavigation(page);
    await page.locator('.hero [data-cta-location="fv"]').click();
    const clicks = (await dataLayer(page)).filter((e) => e.event === 'line_click');
    expect(clicks).toEqual([{ event: 'line_click', cta_location: 'fv' }]);

    // 実際の遷移も確認する（確認用リスナーを外すためリロード）。
    await page.reload();
    await page.locator('.hero [data-cta-location="fv"]').click();
    await page.waitForURL(/lin\.ee/);
  });

  test('spcTrack は dataLayer がなくても作成して積み、例外を出さない', async ({ page }) => {
    await page.goto('/');
    const ok = await page.evaluate(() => {
      delete (window as any).dataLayer;
      (window as any).spcTrack('x_test', { a: 1 });
      return (window as any).dataLayer.some((e: any) => e.event === 'x_test' && e.a === 1);
    });
    expect(ok).toBe(true);
  });
});

test.describe('計測：UTM', () => {
  test('?utm_source=google で着地すると CTA の href に付く', async ({ page }) => {
    await page.goto('/?utm_source=google&utm_medium=cpc');
    const hrefs = await page.locator('[data-spc-cta]').evaluateAll((els) => els.map((e) => (e as HTMLAnchorElement).href));
    expect(hrefs.length).toBeGreaterThan(0);
    for (const href of hrefs) {
      const u = new URL(href);
      expect(u.hostname).toBe('lin.ee');
      expect(u.searchParams.get('utm_source')).toBe('google');
      expect(u.searchParams.get('utm_medium')).toBe('cpc');
    }
  });

  test('最初に着地した UTM が優先され、別ページでも引き継がれる。重複しない', async ({ page }) => {
    await page.goto('/?utm_source=google');
    await page.goto('/price/?utm_source=other');
    const hrefs = await page.locator('[data-spc-cta]').evaluateAll((els) => els.map((e) => (e as HTMLAnchorElement).href));
    expect(hrefs.length).toBeGreaterThan(0);
    for (const href of hrefs) {
      expect(new URL(href).searchParams.getAll('utm_source')).toEqual(['google']);
    }
    await page.goto('/price/');
    const href = await page.locator('[data-spc-cta]').first().evaluate((e) => (e as HTMLAnchorElement).href);
    expect(new URL(href).searchParams.get('utm_source')).toBe('google');
  });
});

test.describe('QRモーダル（PC）', () => {
  test('1280px：CTA クリックでダイアログが開き line_click が送られ、Esc で閉じて元の CTA にフォーカスが戻る', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto('/');
    const cta = page.locator('.hero [data-cta-location="fv"]');
    await cta.click();
    const dialog = page.locator('#spc-qr-modal');
    await expect(dialog).toBeVisible();
    await expect(page).toHaveURL(/localhost:8888\/$/);
    await expect(dialog.getByText('スマートフォンのカメラで読み取ってください')).toBeVisible();
    await expect(dialog.locator('.spc-ph-image, img').first()).toBeVisible();
    const clicks = (await dataLayer(page)).filter((e) => e.event === 'line_click');
    expect(clicks).toEqual([{ event: 'line_click', cta_location: 'fv' }]);

    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(cta).toBeFocused();
  });

  test('背景クリック・閉じるボタンで閉じる。モーダル内の CTA は再度開かず qr_modal として記録される', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await stubLine(page);
    await page.goto('/');
    const dialog = page.locator('#spc-qr-modal');
    const open = page.locator('.hero [data-cta-location="fv"]');

    await open.click();
    await expect(dialog).toBeVisible();
    await page.mouse.click(5, 5);
    await expect(dialog).toBeHidden();

    await open.click();
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: '閉じる' }).click();
    await expect(dialog).toBeHidden();

    await open.click();
    await expect(dialog).toBeVisible();
    await suppressNavigation(page);
    await dialog.locator('[data-cta-location="qr_modal"]').click();
    await expect(dialog).toBeVisible();
    const locations = (await dataLayer(page)).filter((e) => e.event === 'line_click').map((e) => e.cta_location);
    expect(locations.at(-1)).toBe('qr_modal');
    expect(locations.filter((l) => l === 'qr_modal')).toHaveLength(1);
  });

  test('375px ではモーダルを開かず LINE へ遷移する。補足文は SP 用が見える', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await stubLine(page);
    await page.goto('/');
    await expect(page.locator('.hero .spc-cta__note--sp')).toBeVisible();
    await expect(page.locator('.hero .spc-cta__note--pc')).toBeHidden();
    await page.locator('.hero [data-cta-location="fv"]').click();
    await page.waitForURL(/lin\.ee/);
  });

  test('1280px では PC 用の補足文が見える', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto('/');
    await expect(page.locator('.hero .spc-cta__note--pc')).toBeVisible();
    await expect(page.locator('.hero .spc-cta__note--sp')).toBeHidden();
  });
});
