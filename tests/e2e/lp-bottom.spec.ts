import { test, expect, type Page } from '@playwright/test';
import { setSpcSetting, wpCli } from './helpers/wp';
import { NG_WORDS } from './helpers/page-checks';

// 環境が遅いことがあるため、データを書き換えるテストは長めに待つ。
test.describe.configure({ timeout: 180_000 });

const ROW_LABELS = ['品目', '素材', 'シミ', '他店処理', '処置', '仕上がり', '所要日数'];
const FLOW_STEPS = ['写真で相談', '発送', '到着・検品', '正式なお見積り', 'ご承認・お支払い', '施工', '返送'];

type JsonLdNode = Record<string, unknown> & { '@type'?: string | string[]; '@id'?: string };

// 公開中の施工事例のID。
function publishedCaseIds(): string[] {
  const out = wpCli(['post', 'list', '--post_type=spc_case', '--post_status=publish', '--format=ids']);
  return out.split(/\s+/).filter(Boolean);
}

// ゴミ箱に入れた投稿を、元の公開状態に戻す（WP 5.6 以降は既定で下書きに戻るため、フィルタで元の状態を使う）。
function untrash(ids: string[]): void {
  wpCli([
    'eval',
    `add_filter('wp_untrash_post_status','wp_untrash_post_set_previous_status',10,3);array_map('wp_untrash_post',array(${ids.join(',')}));`,
  ]);
}

async function jsonLd(page: Page): Promise<JsonLdNode[]> {
  const scripts = page.locator('script[type="application/ld+json"]');
  await expect(scripts).toHaveCount(1);
  const data = JSON.parse((await scripts.textContent()) ?? '{}');
  expect(data['@context']).toBe('https://schema.org');
  return data['@graph'] as JsonLdNode[];
}

function byType(graph: JsonLdNode[], type: string): JsonLdNode | undefined {
  return graph.find((n) => n['@type'] === type || (Array.isArray(n['@type']) && n['@type'].includes(type)));
}

test.describe('トップLP後半（事例・職人・料金・流れ・注意・FAQ・最終CTA・構造化データ）', () => {
  test('施工事例：診断票が3件、それぞれ仮バッジと7つの項目名、枠は1px・角丸2px・影なし', async ({ page }) => {
    await page.goto('/');
    const section = page.locator('section.spc-cases');
    await expect(section).toHaveCount(1);
    await expect(section).toHaveClass(/spc-section--mist/);
    await expect(section.locator('h2')).toHaveText('施工事例');
    const cards = section.locator('.spc-diagnosis');
    await expect(cards).toHaveCount(3);
    for (let i = 0; i < 3; i++) {
      const card = cards.nth(i);
      await expect(card.locator('.spc-ph-badge').first()).toHaveText('仮');
      await expect(card.locator('dt')).toHaveText(ROW_LABELS);
      await expect(card).toHaveCSS('border-top-width', '1px');
      await expect(card).toHaveCSS('border-top-left-radius', '2px');
      await expect(card).toHaveCSS('box-shadow', 'none');
    }
    await expect(cards.first()).toContainText(/No\.\s*仮-00\d/);
    await expect(cards.first().locator('dd').last()).toHaveText(/^\d+日$/);
    // 画像がないので、施工前・施工後とも仮枠。
    await expect(cards.first().locator('.spc-ph-image[aria-label="仮｜施工前の写真 4:5"]')).toHaveCount(1);
    await expect(cards.first().locator('.spc-ph-image[aria-label="仮｜施工後の写真 4:5"]')).toHaveCount(1);
    // CTAと一覧へのリンク。
    await expect(section.locator('[data-cta-location="cases"]')).toHaveCount(1);
    await expect(section.getByRole('link', { name: '施工事例をもっと見る' })).toHaveAttribute('href', /\/cases\/$/);
  });

  test('Before/After：自動では切り替わらず、クリックで aria-pressed と表示が入れ替わる（150〜250ms）', async ({ page }) => {
    await page.goto('/');
    const card = page.locator('.spc-cases .spc-diagnosis').first();
    const before = card.getByRole('button', { name: '施工前' });
    const after = card.getByRole('button', { name: '施工後' });
    await expect(before).toHaveAttribute('aria-pressed', 'true');
    await expect(after).toHaveAttribute('aria-pressed', 'false');
    await expect(before).toHaveAttribute('type', 'button');

    // 自動スライドしない：しばらく待っても変わらず、動いているアニメーションもない。
    await page.waitForTimeout(1500);
    await expect(before).toHaveAttribute('aria-pressed', 'true');
    const running = await page.evaluate(() => document.getAnimations().filter((a) => a.playState === 'running').length);
    expect(running).toBe(0);

    const beforePane = card.locator('[data-spc-ba-pane="before"]');
    const afterPane = card.locator('[data-spc-ba-pane="after"]');
    await expect(beforePane).toBeVisible();
    await expect(afterPane).toBeHidden();
    const duration = await afterPane.evaluate((el) => parseFloat(getComputedStyle(el).transitionDuration) * 1000);
    expect(duration).toBeGreaterThanOrEqual(150);
    expect(duration).toBeLessThanOrEqual(250);

    await after.click();
    await expect(after).toHaveAttribute('aria-pressed', 'true');
    await expect(before).toHaveAttribute('aria-pressed', 'false');
    await expect(afterPane).toBeVisible();
    await expect(beforePane).toBeHidden();

    await before.click();
    await expect(before).toHaveAttribute('aria-pressed', 'true');
    await expect(beforePane).toBeVisible();

    // 動きを減らす設定では即時。
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.reload();
    const reduced = await page
      .locator('.spc-cases .spc-diagnosis [data-spc-ba-pane="after"]')
      .first()
      .evaluate((el) => parseFloat(getComputedStyle(el).transitionDuration) * 1000);
    expect(reduced).toBeLessThan(1);
  });

  test('JSなしでは施工前・施工後の写真が両方見え、切り替えボタンは出ない', async ({ browser }) => {
    const context = await browser.newContext({ javaScriptEnabled: false });
    const page = await context.newPage();
    try {
      await page.goto('/');
      const card = page.locator('.spc-cases .spc-diagnosis').first();
      await expect(card.locator('[data-spc-ba-pane="before"]')).toBeVisible();
      await expect(card.locator('[data-spc-ba-pane="after"]')).toBeVisible();
      await expect(card.getByRole('button', { name: '施工後' })).toBeHidden();
    } finally {
      await context.close();
    }
  });

  test('施工後の画像なし → 仮枠、空の項目は行ごと省く、長い素材名でも375pxで横スクロールしない、掲載許諾IDは出さない', async ({ page }) => {
    const longMaterial = 'WoolCashmereSilkBlendFabricWithLiningAndDetachableFurCollar0'.slice(0, 60);
    expect(longMaterial).toHaveLength(60);
    const permission = 'E2E-PERMISSION-7731';
    let caseId = '';
    let mediaId = '';
    try {
      mediaId = wpCli(['media', 'import', '/var/www/html/wp-includes/images/media/default.png', '--porcelain']);
      caseId = wpCli(['post', 'create', '--post_type=spc_case', '--post_status=publish', '--post_title=E2E-case', '--porcelain']);
      for (const [key, value] of [
        ['spc_case_no', 'E2E-1'],
        ['spc_item', 'Coat'],
        ['spc_material', longMaterial],
        ['spc_stain', 'Wine'],
        ['spc_result', 'Faint'],
        ['spc_days', '7'],
        ['spc_before_id', mediaId],
        ['spc_permission_id', permission],
      ]) {
        wpCli(['post', 'meta', 'update', caseId, key, value]);
      }

      await page.setViewportSize({ width: 375, height: 812 });
      await page.goto('/');
      const card = page.locator('.spc-cases .spc-diagnosis').filter({ hasText: 'E2E-1' });
      await expect(card).toHaveCount(1);
      // 施工前は画像、施工後は仮枠。
      await expect(card.locator('[data-spc-ba-pane="before"] img')).toHaveCount(1);
      await expect(card.locator('[data-spc-ba-pane="after"] .spc-ph-image')).toHaveAttribute('aria-label', '仮｜施工後の写真 4:5');
      // 他店処理・処置は空なので行ごと出さない。「—」も出さない。
      await expect(card.locator('dt')).toHaveText(['品目', '素材', 'シミ', '仕上がり', '所要日数']);
      await expect(card.locator('dd')).not.toContainText(['—']);
      await expect(card.locator('dd').filter({ hasText: longMaterial })).toHaveCount(1);
      // 仮データではないので仮バッジは出ない。
      await expect(card.locator('.spc-diagnosis__head .spc-ph-badge')).toHaveCount(0);
      // 横スクロールしない。
      expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(375);
      // 掲載許諾IDは内部用。HTMLのどこにも出さない。
      expect(await page.content()).not.toContain(permission);
    } finally {
      if (caseId) wpCli(['post', 'delete', caseId, '--force']);
      if (mediaId) wpCli(['post', 'delete', mediaId, '--force']);
    }
  });

  test('施工事例を全部ゴミ箱に入れると、施工事例セクションごと出力されない', async ({ page }) => {
    const ids = publishedCaseIds();
    expect(ids.length).toBeGreaterThan(0);
    try {
      // WP-CLI の post delete は投稿・固定ページ以外をゴミ箱に入れられないため、wp_trash_post を使う。
      wpCli(['eval', `array_map('wp_trash_post',array(${ids.join(',')}));`]);
      await page.goto('/');
      await expect(page.locator('.spc-cases')).toHaveCount(0);
      await expect(page.locator('.spc-diagnosis')).toHaveCount(0);
      await expect(page.getByRole('heading', { name: '施工事例', exact: true })).toHaveCount(0);
      // ほかのセクションは出ている。
      await expect(page.locator('section.spc-craftsmen')).toHaveCount(1);
    } finally {
      untrash(ids);
    }
    await page.goto('/');
    await expect(page.locator('.spc-cases .spc-diagnosis')).toHaveCount(3);
  });

  test('職人と施工母体：LPで唯一の墨地。名前「職人名（仮）」、顔写真の仮枠、創業1954年', async ({ page }) => {
    await page.goto('/');
    const section = page.locator('section.spc-craftsmen');
    await expect(section).toHaveCount(1);
    const sumi = await page.evaluate(() => {
      const probe = document.createElement('div');
      probe.style.backgroundColor = 'var(--color-sumi)';
      document.body.appendChild(probe);
      const value = getComputedStyle(probe).backgroundColor;
      probe.remove();
      return value;
    });
    await expect(section).toHaveCSS('background-color', sumi);
    // 墨地のセクションはこの1つだけ。
    const darkSections = await page
      .locator('main > section')
      .evaluateAll((els, color) => els.filter((el) => getComputedStyle(el).backgroundColor === color).length, sumi);
    expect(darkSections).toBe(1);

    await expect(section.getByRole('heading', { level: 3, name: '職人名（仮）', exact: true })).toBeVisible();
    await expect(section.locator('.spc-ph-image[aria-label="仮｜職人の顔写真 4:5"]').first()).toBeVisible();
    await expect(section.locator('.spc-ph-badge').first()).toBeVisible();
    await expect(section).toContainText('有限会社杉本クリーニング商会');
    await expect(section).toContainText('1954年');
    await expect(section).toContainText('ドライクリーニングとウェットクリーニングを素材に合わせて使い分けています');
    await expect(section.locator('.spc-ph-image[aria-label="仮｜作業風景の実写 3:2"]')).toHaveCount(1);
    // 墨地の上の文字は白。
    await expect(section.locator('h2')).toHaveCSS('color', 'rgb(255, 255, 255)');
  });

  test('料金の目安：高級ダウンは「15,000円〜」＋仮バッジ、金額0は「お見積り」、金額は右揃え・桁揃え、縦罫線なし', async ({ page }) => {
    await page.goto('/');
    const section = page.locator('section.spc-price');
    await expect(section).toHaveCount(1);
    await expect(section.locator('h2')).toHaveText('料金の目安');
    // 写真で概算 → 現物で正式見積 → 承認後に施工
    await expect(section.locator('ol.spc-price__steps > li')).toHaveCount(3);
    // 説明文は本文なので16px以上（--text-small は注記・キャプション専用）。
    const stepSizes = await section
      .locator('.spc-price__step-text')
      .evaluateAll((els) => els.map((el) => parseFloat(getComputedStyle(el).fontSize)));
    expect(stepSizes).toHaveLength(3);
    for (const size of stepSizes) {
      expect(size).toBeGreaterThanOrEqual(16);
    }

    const down = section.locator('tr').filter({ hasText: '高級ダウン' });
    await expect(down).toHaveCount(1);
    const amount = down.locator('.spc-price__amount');
    await expect(amount).toContainText('15,000円〜');
    await expect(amount.locator('.spc-ph-badge')).toHaveText('仮');
    await expect(amount).toHaveCSS('text-align', 'right');
    await expect(amount).toHaveCSS('font-variant-numeric', 'tabular-nums');
    const cell = down.locator('td, th').first();
    await expect(cell).toHaveCSS('border-left-width', '0px');
    await expect(cell).toHaveCSS('border-right-width', '0px');

    // 送料・手数料の表：診断料は表示用テキスト「無料（仮）」が金額より優先される。
    const diagnosis = section.locator('tr').filter({ hasText: '診断料' });
    await expect(diagnosis.locator('.spc-price__amount')).toContainText('無料（仮）');
    await expect(diagnosis.locator('.spc-price__amount')).not.toContainText('お見積り');
    await expect(section.locator('table')).toHaveCount(2);

    await expect(section).toContainText('表示は目安です。素材や状態によって変わります。正式な金額は現物を拝見してからお伝えします。');
    await expect(section.getByRole('link', { name: '料金・送料の詳細' })).toHaveAttribute('href', /\/price\/$/);
  });

  test('全国宅配の流れは <ol> で7つ、お引き受けできない場合は判断基準4つ', async ({ page }) => {
    await page.goto('/');
    const flow = page.locator('section.spc-flow');
    await expect(flow).toHaveClass(/spc-section--mist/);
    await expect(flow.locator('ol > li')).toHaveCount(7);
    await expect(flow.locator('ol > li h3')).toHaveText(FLOW_STEPS);

    const limits = page.locator('section.spc-limits');
    await expect(limits.locator('h2')).toHaveCount(1);
    await expect(limits.locator('ul > li')).toHaveCount(4);
    await expect(limits.locator('ul > li').first()).toHaveCSS('border-top-width', '1px');
    await expect(limits).toContainText('お引き受けできない場合も理由をご説明し、お預かり後であれば返送いたします');
  });

  test('FAQは <details> で最大5件、JSON-LDの FAQPage の質問がページ上の質問と一致し、Organization.name が運営会社', async ({ page }) => {
    await page.goto('/');
    const section = page.locator('section.spc-faq');
    await expect(section.locator('h2')).toHaveText('よくある質問');
    const items = section.locator('details');
    const count = await items.count();
    expect(count).toBeGreaterThan(0);
    expect(count).toBeLessThanOrEqual(5);
    await expect(section.locator('details > summary')).toHaveCount(count);
    const questions = (await section.locator('summary .spc-faq__q').allTextContents()).map((t) => t.trim());

    // 開閉：クリックで開く。
    await items.first().locator('summary').click();
    await expect(items.first()).toHaveAttribute('open', '');
    await expect(items.first().locator('.spc-faq__a')).toBeVisible();
    await expect(section.getByRole('link', { name: 'よくある質問をすべて見る' })).toHaveAttribute('href', /\/guide\/#faq$/);

    const graph = await jsonLd(page);
    const org = byType(graph, 'Organization');
    expect(org?.name).toBe('有限会社杉本クリーニング商会');
    expect(String(org?.foundingDate)).toBe('1954');
    const business = byType(graph, 'DryCleaningOrLaundry');
    expect(business).toBeDefined();
    expect((business?.parentOrganization as JsonLdNode)['@id']).toBe(org?.['@id']);
    const faq = byType(graph, 'FAQPage');
    const entities = faq?.mainEntity as Array<{ name: string; acceptedAnswer: { text: string } }>;
    expect(entities.map((q) => q.name)).toEqual(questions);
    for (const q of entities) {
      expect(q.acceptedAnswer.text).not.toMatch(/<[^>]+>/);
    }

    // 未確定の情報は書かない。
    const raw = (await page.locator('script[type="application/ld+json"]').textContent()) ?? '';
    for (const key of ['address', 'telephone', 'aggregateRating', 'review', 'priceRange']) {
      expect(raw, key).not.toContain(`"${key}"`);
    }

    // ブランド（DryCleaningOrLaundry）はトップだけ（下層ページは Organization と BreadcrumbList）。
    await page.goto('/price/');
    expect(byType(await jsonLd(page), 'DryCleaningOrLaundry')).toBeUndefined();
  });

  test('最終CTA：data-sticky-hide、CTA、返信の目安は設定値、QRは1024px以上だけ', async ({ page }) => {
    const note = 'E2E-reply-note-42';
    const restore = setSpcSetting('reply_time_note', note);
    try {
      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto('/');
      const section = page.locator('section.spc-final-cta');
      await expect(section).toHaveCount(1);
      await expect(section).toHaveAttribute('data-sticky-hide', '');
      await expect(section).toHaveClass(/spc-section--mist/);
      await expect(section.locator('h2')).toHaveCount(1);
      await expect(section.locator('[data-cta-location="final"]')).toHaveCount(1);
      await expect(section).toContainText(`一次返信の目安：${note}`);
      await expect(section.locator('h2')).toHaveCSS('text-align', 'center');
      const qr = section.locator('.spc-final-cta__qr');
      await expect(qr).toBeVisible();
      await expect(qr.locator('.spc-ph-image')).toHaveAttribute('aria-label', '仮｜LINEのQRコード');
      await expect(qr.locator('.spc-ph-badge')).toBeVisible();
      await expect(qr).toContainText('スマートフォンのカメラで読み取ってください');

      await page.setViewportSize({ width: 375, height: 812 });
      await expect(qr).toBeHidden();
    } finally {
      restore();
    }
  });

  test('料金の表示用テキスト：入力があれば金額より優先、なければ金額（「〜」付き）を表示する', async ({ page }) => {
    const ids: string[] = [];
    try {
      for (const [title, amount, from, text] of [
        ['E2E表示テキスト', '0', '', '無料'],
        ['E2E金額表示', '15000', '1', ''],
      ]) {
        const id = wpCli(['post', 'create', '--post_type=spc_price', '--post_status=publish', `--post_title=${title}`, '--porcelain']);
        ids.push(id);
        for (const [key, value] of [
          ['spc_price_group', 'shipping'],
          ['spc_amount', amount],
          ['spc_from', from],
          ['spc_display_text', text],
        ]) {
          if (value !== '') wpCli(['post', 'meta', 'update', id, key, value]);
        }
      }
      await page.goto('/');
      const section = page.locator('section.spc-price');
      const free = section.locator('tr').filter({ hasText: 'E2E表示テキスト' }).locator('.spc-price__amount');
      await expect(free).toContainText('無料');
      await expect(free).not.toContainText('お見積り');
      const money = section.locator('tr').filter({ hasText: 'E2E金額表示' }).locator('.spc-price__amount');
      await expect(money).toContainText('15,000円〜');
    } finally {
      for (const id of ids) wpCli(['post', 'delete', id, '--force']);
    }
  });

  test('セクションの順番（5〜11）、list-style:none のリストに role="list"、影なし、375pxで横スクロールなし（長い品目名・注記の料金行を含む）、NG表現なし', async ({ page }) => {
    // 区切りのない60文字の品目名と長い注記（Safari は word-break: auto-phrase 非対応のため overflow-wrap で折り返す）。
    const longName = 'PremiumCashmereDoubleFacedCoatWithFurCollarAndSilkLining0000'.slice(0, 60);
    expect(longName).toHaveLength(60);
    const longNote = 'SurchargeAppliesForHandFinishingOfDelicateTrimsAndLinings'.repeat(2);
    let priceId = '';
    try {
      priceId = wpCli(['post', 'create', '--post_type=spc_price', '--post_status=publish', `--post_title=${longName}`, '--porcelain']);
      for (const [key, value] of [
        ['spc_price_group', 'stain'],
        ['spc_amount', '12000'],
        ['spc_note', longNote],
      ]) {
        wpCli(['post', 'meta', 'update', priceId, key, value]);
      }

      await page.setViewportSize({ width: 375, height: 812 });
      await page.goto('/');
      const row = page.locator('.spc-price tr').filter({ hasText: longName });
      await expect(row).toHaveCount(1);
      await expect(row.locator('.spc-price__name')).toHaveCSS('overflow-wrap', 'anywhere');
      await expect(row.locator('.spc-price__note')).toHaveCSS('overflow-wrap', 'anywhere');
      await expect(row.locator('.spc-price__note')).toContainText(longNote);
      // 行のどのセルも、中身がはみ出していない。
      const overflowing = await row.evaluate((tr) =>
        [...tr.querySelectorAll('th, td')].filter((cell) => cell.scrollWidth > cell.clientWidth + 1).length,
      );
      expect(overflowing).toBe(0);

      const names = await page.locator('main > section').evaluateAll((els) =>
        els.map((el) => [...el.classList].find((c) => /^(hero|spc-(concerns|steps|menu|cases|craftsmen|price|flow|limits|faq|final-cta))$/.test(c))),
      );
      expect(names).toEqual([
        'hero',
        'spc-concerns',
        'spc-steps',
        'spc-menu',
        'spc-cases',
        'spc-craftsmen',
        'spc-price',
        'spc-flow',
        'spc-limits',
        'spc-faq',
        'spc-final-cta',
      ]);
      const bgs = await page.locator('main > section').evaluateAll((els) => els.map((el) => el.className));
      expect(bgs[6]).not.toMatch(/spc-section--(mist|sumi)/); // 料金は白
      expect(bgs[8]).not.toMatch(/spc-section--(mist|sumi)/); // お引き受けできない場合は白
      expect(bgs[9]).not.toMatch(/spc-section--(mist|sumi)/); // FAQは白

      const unlabeled = await page.evaluate(() =>
        [...document.querySelectorAll('main ul, main ol')]
          .filter((el) => getComputedStyle(el).listStyleType === 'none' && el.getAttribute('role') !== 'list')
          .map((el) => el.className),
      );
      expect(unlabeled).toEqual([]);

      const shadows = await page.evaluate(() =>
        [...document.querySelectorAll('main *')].filter((el) => getComputedStyle(el).boxShadow !== 'none').length,
      );
      expect(shadows).toBe(0);

      expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(375);

      const text = await page.locator('main').innerText();
      for (const ng of NG_WORDS) {
        expect(text, ng).not.toContain(ng);
      }
    } finally {
      if (priceId) wpCli(['post', 'delete', priceId, '--force']);
    }
  });
});
