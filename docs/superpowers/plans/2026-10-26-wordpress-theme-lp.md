# WordPress初期構築＋トップLP 実装計画

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** ローカルのWordPress上に、自作テーマ `spc-theme` と機能用mu-plugin `spc-core` を構築し、仮データ入りのトップLPを DESIGN.md どおりに表示する。

**Architecture:** 見た目はクラシックテーマ（PHPテンプレート＋素のCSS/JS）、データと業務ルールはmu-plugin（CPT・メタ・ブランド設定・仮データのガード・WP-CLI）に分ける。テーマを差し替えても、事例・料金などのデータと公開停止のルールは残る。テーマは `spc-core` の関数だけを通してデータを読む。

**Tech Stack:** WordPress 最新安定版 / PHP 8.3 / `@wordpress/env`（Docker）/ PHPUnit（WordPressテストスイート）/ Playwright / Node 24

**Spec:** [DESIGN.md](../../../DESIGN.md)、[docs/sitemap.md](../../sitemap.md)、[CLAUDE.md](../../../CLAUDE.md)

## Global Constraints

- CTAの文言は全箇所で「写真を送って無料相談」。言い換えない。
- LINE URLは `spc_line_url()` からのみ取得。ダミーは `https://lin.ee/PLACEHOLDER`。
- 関数・フック・オプションの接頭辞は `spc_`、テキストドメインは `spc`。
- サードパーティのプラグインはSEO・セキュリティ・バックアップの3つだけ（本計画ではどれもインストールしない）。
- サイトにフォームを置かない。写真・個人情報を受け取らない。
- 色は `assets/css/tokens.css` の変数（`--color-paper` `--color-mist` `--color-line` `--color-stone` `--color-sumi` `--color-placeholder`）だけを使う。HEXを直接書かない。
- 書体は Shippori Mincho（500/700）と Zen Kaku Gothic New（400/500/700）の2つだけ。
- 本文16px以上、タップ領域44×44px以上、フォーカスは `outline: 2px solid` ＋ `outline-offset: 3px`、`prefers-reduced-motion` に対応する。
- 英字大文字のアイブロウ、末尾の「→」、各セクションのフェードイン、カードの影は使わない。
- 出力時は必ずエスケープ（`esc_html` / `esc_attr` / `esc_url` / `wp_kses_post`）。保存処理ではnonce・権限チェック・サニタイズを行う。
- 仮データは `_spc_is_placeholder = '1'`。本番（`spc_is_production()`）では公開も表示もしない。
- 計測イベント名は `landing_view` / `line_click`。`cta_location` の値は header / fv / steps / cases / final / sticky / qr_modal。
- 仮の値を書いたコードには `// TODO(仮): 公開前に差し替え` を付ける。

## Review Focus

1. **LINE URLが空欄・不正な値・http・LINE以外のドメインで保存された場合**：保存時に弾き、ダミーに戻す。CTAの `href` が空にならず、`launch-check` で検出される → Task 2 のテスト
2. **管理画面のクイック編集・REST APIなど、メタボックスを通らない経路で仮データを公開しようとした場合**：本番では下書きに戻る → Task 4 のテスト
3. **事例・料金の項目が一部未入力の場合**（After画像なし、金額0）：レイアウトが崩れたり「0円〜」になったりせず、画像は仮枠、金額は「お見積り」と表示する → Task 9 のテスト
4. **長いブランド名・素材名が入った場合（幅375px）**：診断票や料金表が折り返され、横スクロールが出ない → Task 9 のテスト
5. **GTMが読み込まれない場合（未設定・広告ブロッカー・JSエラー）**：CTAは通常のリンクとしてLINEへ遷移し、`dataLayer` への送信で例外が出ない → Task 7 のテスト

---

## ファイル構成

```
/（リポジトリ）
├─ CLAUDE.md / DESIGN.md / docs/
├─ .gitignore  .wp-env.json  package.json  playwright.config.ts
├─ tests/e2e/                         Playwrightのテスト（global-setup.ts で seed）
└─ wp-content/
   ├─ mu-plugins/
   │  ├─ spc-core.php                 ローダー（mu-pluginsはサブフォルダを自動で読まないため）
   │  └─ spc-core/
   │     ├─ composer.json  phpunit.xml.dist  tests/（bootstrap.php, test-*.php）
   │     ├─ includes/settings.php      ブランド設定ページ・spc_get_setting()・spc_line_url()
   │     ├─ includes/post-types.php    CPT 4種の登録
   │     ├─ includes/meta-fields.php   メタ定義・メタボックス・保存処理・管理一覧の「仮」列
   │     ├─ includes/placeholder.php   仮データ判定・本番での公開停止・クエリ除外・レポート・サイトヘルス
   │     ├─ includes/cli.php           wp spc launch-check / wp spc seed
   │     ├─ includes/seed-data.php     仮データ（事例3・料金・FAQ・職人・固定ページ）
   │     ├─ includes/security.php      XML-RPC停止・ユーザー列挙防止など
   │     └─ assets/admin-media.js      メタボックスの画像選択
   └─ themes/spc-theme/
      ├─ style.css  functions.php  theme.json  index.php  page.php  404.php
      ├─ header.php  footer.php  front-page.php
      ├─ inc/setup.php                テーマ機能・メニュー・画像サイズ
      ├─ inc/enqueue.php              フォント・CSS・JSの読み込み
      ├─ inc/gtm.php                  GTMスニペット
      ├─ inc/schema.php               JSON-LD（Organization / LocalBusiness / FAQPage）
      ├─ inc/template-tags.php        spc_cta() など表示用の関数
      ├─ template-parts/              cta-button / sticky-cta / qr-modal / diagnosis-card / placeholder-image / placeholder-badge
      ├─ template-parts/lp/           hero / concerns / steps / menu / cases / craftsmen / price / shipping-flow / limits / faq / final-cta
      └─ assets/
         ├─ css/tokens.css base.css components.css lp.css
         └─ js/tracking.js sticky-cta.js qr-modal.js before-after.js
```

---

### Task 1: 開発環境とテストの土台

**Files:**
- Create: `.gitignore`, `.wp-env.json`, `package.json`, `playwright.config.ts`, `tests/e2e/global-setup.ts`, `tests/e2e/smoke.spec.ts`
- Create: `wp-content/mu-plugins/spc-core.php`, `wp-content/mu-plugins/spc-core/{composer.json,phpunit.xml.dist,tests/bootstrap.php,tests/test-boot.php}`
- Create: `wp-content/themes/spc-theme/{style.css,functions.php,index.php}`

**Interfaces:**
- Produces: `const SPC_CORE_DIR`（`spc-core/` の絶対パス）。npmスクリプト `env:start` / `env:stop` / `test:php` / `test:e2e` / `wp`（`wp-env run cli wp`）

- [ ] **Step 1:** `git init`。`.gitignore` に `node_modules/` `vendor/` `test-results/` `playwright-report/` `*.docx` を追加する（設計書は社外秘の可能性があるため除外）。
- [ ] **Step 2:** `.wp-env.json` を作る：`phpVersion: "8.3"`、`themes: ["./wp-content/themes/spc-theme"]`、`mappings: {"wp-content/mu-plugins": "./wp-content/mu-plugins"}`、`config: {"WP_ENVIRONMENT_TYPE": "local", "DISALLOW_FILE_EDIT": true}`、`lifecycleScripts.afterStart` で `wp theme activate spc-theme && wp rewrite structure '/%postname%/' --hard` を実行する。
- [ ] **Step 3:** スモークテストを書く。PHP側 `test-boot.php`：`function_exists('spc_core_loaded')` が true。E2E側 `smoke.spec.ts`：`/` が200を返し、`<html lang="ja">` を持ち、`link[href*="spc-theme/style.css"]` がある。
- [ ] **Step 4:** `npm run env:start && npm run test:php && npm run test:e2e` → 両方失敗することを確認する。
- [ ] **Step 5:** ローダー（`spc-core.php` が `spc-core/includes/*.php` を読み込み、`spc_core_loaded()` を定義）と、テーマの最小ファイルを作る。`composer.json` の dev 依存は `phpunit/phpunit:^9.6` と `yoast/phpunit-polyfills:^2`。`bootstrap.php` は `getenv('WP_TESTS_DIR')` を読む。
- [ ] **Step 6:** `npm run test:php && npm run test:e2e` → PASS。
- [ ] **Step 7:** コミット `chore: WordPress開発環境とテスト基盤を追加`

### Task 2: ブランド設定とLINE URLの1か所管理

**Files:**
- Create: `spc-core/includes/settings.php`, `spc-core/tests/test-settings.php`

**Interfaces:**
- Produces:
  - `const SPC_LINE_URL_PLACEHOLDER = 'https://lin.ee/PLACEHOLDER';`
  - `spc_settings_defaults(): array`：`brand_name='（仮）ブランド名'`, `line_url=SPC_LINE_URL_PLACEHOLDER`, `line_qr_id=0`, `gtm_id=''`, `hero_sp_writing='vertical'`, `reply_time_note='営業時間内24時間以内（仮）'`
  - `spc_settings_sanitize( array $input ): array`
  - `spc_get_setting( string $key ): string|int`
  - `spc_line_url(): string`
  - オプション名 `spc_settings`。管理画面「設定 > ブランド設定」（権限 `manage_options`）

- [ ] **Step 1: 失敗するテストを書く**

```php
public function test_line_url_defaults_to_placeholder() {
    delete_option( 'spc_settings' );
    $this->assertSame( 'https://lin.ee/PLACEHOLDER', spc_line_url() );
}
/** @dataProvider invalid_line_urls */
public function test_invalid_line_url_falls_back( $input ) {
    $out = spc_settings_sanitize( [ 'line_url' => $input ] );
    $this->assertSame( SPC_LINE_URL_PLACEHOLDER, $out['line_url'] );
}
public function invalid_line_urls() {
    return [ [''], ['http://lin.ee/abc'], ['https://evil.example/abc'], ['javascript:alert(1)'] ];
}
public function test_valid_line_urls_kept() {
    foreach ( [ 'https://lin.ee/AbC123', 'https://line.me/R/ti/p/@abc' ] as $url ) {
        $this->assertSame( $url, spc_settings_sanitize( [ 'line_url' => $url ] )['line_url'] );
    }
}
public function test_gtm_id_format() {
    $this->assertSame( 'GTM-AB12CD', spc_settings_sanitize( [ 'gtm_id' => ' gtm-ab12cd ' ] )['gtm_id'] );
    $this->assertSame( '', spc_settings_sanitize( [ 'gtm_id' => '<script>' ] )['gtm_id'] );
}
public function test_hero_mode_whitelist() {
    $this->assertSame( 'vertical', spc_settings_sanitize( [ 'hero_sp_writing' => 'diagonal' ] )['hero_sp_writing'] );
}
```

- [ ] **Step 2:** `npm run test:php -- --filter Settings` → FAIL（関数が未定義）
- [ ] **Step 3:** `settings.php` を実装する。Settings API を使う。許可するLINEのホストは `lin.ee` と `line.me`（https のみ）。GTM IDは大文字化して `/^GTM-[A-Z0-9]{4,10}$/` に一致するものだけ残す。`line_qr_id` はメディアライブラリから選ぶ（`admin-media.js` を共用）。
- [ ] **Step 4:** `npm run test:php -- --filter Settings` → PASS
- [ ] **Step 5:** コミット `feat(core): ブランド設定とLINE URLの一元管理`

### Task 3: カスタム投稿タイプとカスタムフィールド

**Files:**
- Create: `spc-core/includes/post-types.php`, `spc-core/includes/meta-fields.php`, `spc-core/assets/admin-media.js`, `spc-core/tests/test-post-types.php`, `spc-core/tests/test-meta-fields.php`

**Interfaces:**
- Consumes: なし
- Produces:
  - 投稿タイプ：`spc_case`（public、`rewrite.slug='cases'`、`has_archive=true`）、`spc_price` / `spc_faq` / `spc_staff`（`public=false`、`show_ui=true`）。4つともブロックエディタは使わない（`use_block_editor_for_post_type` で false）。
  - `spc_field_definitions( string $post_type ): array<string, array{label:string, type:'text'|'textarea'|'int'|'bool'|'image'|'select', options?:array}>`
  - メタキー：
    - 事例：`spc_case_no` `spc_item` `spc_material` `spc_stain` `spc_elapsed` `spc_prior_treatment` `spc_treatment` `spc_result` `spc_days`(int) `spc_before_id`(image) `spc_after_id`(image) `spc_permission_id`
    - 料金：`spc_price_group`(select: stain / yellowing / down / coat / shipping) `spc_amount`(int、0=未設定) `spc_from`(bool) `spc_note`
    - FAQ：`spc_faq_group`(select: shipping / price / payment / photo / other) `spc_show_on_top`(bool)
    - 職人：`spc_role` `spc_bio`(textarea)
    - 共通：`_spc_is_placeholder`(bool) `_spc_seed_key`(text、管理画面には出さない)
  - 並び順：料金・FAQ・職人は `menu_order`（`page-attributes` に対応させる）

- [ ] **Step 1: 失敗するテストを書く**：`test_case_post_type_is_public_with_cases_slug`（`get_post_type_object('spc_case')->rewrite['slug'] === 'cases'`）、`test_internal_types_not_public`（price / faq / staff の `publicly_queryable === false`）、`test_int_meta_sanitized`（`update_post_meta($id,'spc_amount','-500abc')` の後で `get_post_meta` が `0`）、`test_select_meta_rejects_unknown`（`spc_price_group='hack'` → `''`）、`test_save_requires_nonce`（nonceなしの `$_POST` で `spc_save_meta_box($id)` を呼んでもメタが変わらない）、`test_save_requires_capability`（購読者権限で変わらない）。
- [ ] **Step 2:** `npm run test:php -- --filter "PostTypes|MetaFields"` → FAIL
- [ ] **Step 3:** 実装する。メタは `register_post_meta()` で `sanitize_callback` と `auth_callback`（`edit_post`）を付け、`show_in_rest` は false にする。メタボックスは `spc_field_definitions()` から自動生成し、保存は `spc_save_meta_box( int $post_id ): void`（`save_post_{type}` にフック）で行う。管理一覧に「仮」列を追加する。
- [ ] **Step 4:** `npm run test:php -- --filter "PostTypes|MetaFields"` → PASS
- [ ] **Step 5:** コミット `feat(core): 施工事例・料金・FAQ・職人のCPTとフィールド`

### Task 4: 仮データのガード（本番で公開・表示しない）

**Files:**
- Create: `spc-core/includes/placeholder.php`, `spc-core/tests/test-placeholder.php`

**Interfaces:**
- Consumes: `spc_get_setting()`, `SPC_LINE_URL_PLACEHOLDER`（Task 2）、メタキー `_spc_is_placeholder`（Task 3）
- Produces:
  - `spc_is_production(): bool`：`wp_get_environment_type() === 'production'` をフィルタ `spc_is_production` に通した値
  - `spc_is_placeholder( int|WP_Post $post ): bool`
  - `spc_query_args( string $post_type, array $args = [] ): array`：本番では `_spc_is_placeholder` を除外する `meta_query` を合成し、並び順の既定値を入れる（spc_case は公開日の新しい順、それ以外は `menu_order ASC`）
  - `spc_placeholder_report(): array<int, array{type:'post'|'line_url'|'brand_name'|'gtm_id', label:string, edit_url:string}>`

- [ ] **Step 1: 失敗するテストを書く**

```php
public function test_production_reverts_placeholder_publish_to_draft() {
    add_filter( 'spc_is_production', '__return_true' );
    $id = self::factory()->post->create( [ 'post_type' => 'spc_case', 'post_status' => 'draft' ] );
    update_post_meta( $id, '_spc_is_placeholder', '1' );
    wp_update_post( [ 'ID' => $id, 'post_status' => 'publish' ] );   // クイック編集・RESTと同じ経路
    $this->assertSame( 'draft', get_post_status( $id ) );
}
public function test_non_production_allows_placeholder_publish() { /* 同じ操作で 'publish' のまま */ }
public function test_marking_published_post_as_placeholder_in_production_unpublishes() {
    /* 公開済みの投稿に後から _spc_is_placeholder=1 を付けると draft になる */
}
public function test_query_args_exclude_placeholder_only_in_production() {
    /* 仮1件＋本物1件。本番 → 1件、本番以外 → 2件 */
}
public function test_report_lists_all_issue_types() {
    delete_option( 'spc_settings' );
    $types = wp_list_pluck( spc_placeholder_report(), 'type' );
    foreach ( [ 'line_url', 'brand_name', 'gtm_id' ] as $t ) { $this->assertContains( $t, $types ); }
}
public function test_report_empty_when_everything_set() { /* 正しい設定・仮投稿なし → [] */ }
```

- [ ] **Step 2:** `npm run test:php -- --filter Placeholder` → FAIL
- [ ] **Step 3:** 実装する。公開の取り消しは `wp_after_insert_post`（メタ保存後に発火する）と、`_spc_is_placeholder` の `added_post_meta` / `updated_post_meta` にフックする。`spc_is_production()` かつ仮データかつ `publish` なら、自分のフックを外してから `wp_update_post(['post_status'=>'draft'])` を実行し、ユーザーごとのtransientに「仮データのため本番では公開できません」という通知を積む。サイトヘルスの `direct` テスト（レポートが空でなければ `critical`）と、管理バーの「仮データ N件」も追加する。
- [ ] **Step 4:** `npm run test:php -- --filter Placeholder` → PASS
- [ ] **Step 5:** コミット `feat(core): 本番での仮データの公開停止と公開前レポート`

### Task 5: WP-CLI（公開前チェックと仮データ投入）

**Files:**
- Create: `spc-core/includes/cli.php`, `spc-core/includes/seed-data.php`, `spc-core/tests/test-seed.php`

**Interfaces:**
- Consumes: `spc_placeholder_report()`, `spc_is_production()`（Task 4）、CPT・メタキー（Task 3）
- Produces:
  - `spc_seed_definitions(): array`（仮データ本体）、`spc_seed_run(): array{created:int, skipped:int}`（`_spc_seed_key` で重複を防ぐ。何度実行しても同じ結果になる）
  - `wp spc seed`：本番では `WP_CLI::error()` で拒否する
  - `wp spc launch-check`：レポートを表で出し、1件以上あれば終了コード1、0件なら `Success`

**仮データの内容（すべて `_spc_is_placeholder=1`、文言に「（仮）」を含める）**
- 事例 ×3：受付番号 `仮-001`〜`仮-003`、タイトルの先頭に「（仮）」を付ける。画像は登録しない（仮枠で表示する）。1件は「仕上がり：薄く残る（目立たない程度）（仮）」にする。
- 料金（`// TODO(仮): 公開前に差し替え`）：特殊シミ抜き 5,000円〜／黄ばみ・汗汚れ 3,000円〜／高級ダウン 15,000円〜／コート・ジャケット 12,000円〜／往復送料 2,000円〜／診断料 0（＝「お見積り」表示、注記「無料の予定（仮）」）／未承認時の返送料 0（注記「実費の予定（仮）」）
- FAQ ×5（すべて「トップに表示」）：送料・納期・支払い・写真の扱い・お引き受けできない場合。回答の末尾に「（仮）」を付ける。
- 職人 ×2：「職人名（仮）」「役職（仮）」。顔写真は登録しない。
- 固定ページ：stain-removal / down-coat / price / about / guide / legal（子ページ terms / tokushoho / privacy）。本文は「準備中（仮）」。

- [ ] **Step 1: 失敗するテストを書く**：`test_seed_is_idempotent`（2回実行して投稿数が変わらず、2回目の `created === 0`）、`test_all_seeded_posts_are_placeholders`、`test_seed_creates_three_cases_and_five_top_faqs`。
- [ ] **Step 2:** `npm run test:php -- --filter Seed` → FAIL
- [ ] **Step 3:** 実装する。
- [ ] **Step 4:** `npm run test:php -- --filter Seed` → PASS。さらに `npm run wp -- spc seed` を実行してから `npm run wp -- spc launch-check` を実行し、終了コード1と仮データの一覧が出ることを確認する。
- [ ] **Step 5:** コミット `feat(core): wp spc seed / launch-check コマンド`

### Task 6: テーマ基盤（トークン・ヘッダー/フッター・CTA部品）

**Files:**
- Create: `spc-theme/{theme.json,header.php,footer.php,page.php,404.php}`, `spc-theme/inc/{setup.php,enqueue.php,template-tags.php}`, `spc-theme/template-parts/{cta-button.php,placeholder-badge.php,placeholder-image.php}`, `spc-theme/assets/css/{tokens.css,base.css,components.css}`, `tests/e2e/theme-base.spec.ts`
- Modify: `spc-theme/functions.php`（`inc/*.php` を読み込む）

**Interfaces:**
- Consumes: `spc_line_url()`, `spc_get_setting()`（Task 2）、`spc_is_placeholder()`（Task 4）
- Produces:
  - `spc_cta( string $location, string $variant = 'default' ): void`：variant は `default` / `inverse` / `compact`。出力は `<a class="spc-cta spc-cta--{variant}" href="{spc_line_url()}" data-spc-cta data-cta-location="{location}">写真を送って無料相談</a>` と補足文 `<span class="spc-cta__note">`
  - `spc_placeholder_badge( ?int $post_id = null, bool $force = false ): void`
  - `spc_image_or_placeholder( int $attachment_id, string $size, string $label ): void`：IDが0なら `<div class="spc-ph-image" role="img" aria-label="仮｜{label}">仮｜{label}</div>`
  - 画像サイズ：`spc-hero`（1600×1000）、`spc-portrait`（800×1000）、`spc-menu`（900×600）
  - メニュー位置：`primary`、`footer`（未設定のときは固定リンクで代用）

- [ ] **Step 1: 失敗するテストを書く**（`theme-base.spec.ts`）：
  - ヘッダーに `[data-cta-location="header"]` があり、その href が `npm run wp -- option` で設定したLINE URLと一致する
  - CTAの文言が完全一致で「写真を送って無料相談」
  - `fonts.googleapis.com` の `<link>` が1つだけあり、Shippori Mincho と Zen Kaku Gothic New を含む
  - Tabキーで移動したときCTAの `outline-style` が `solid`
  - 375px幅で `document.documentElement.scrollWidth <= 375`
  - フッターに「有限会社杉本クリーニング商会」と法定3ページへのリンクがある
- [ ] **Step 2:** `npm run test:e2e -- theme-base` → FAIL
- [ ] **Step 3:** 実装する。tokens / base / components の値は DESIGN.md の §2・§3・§4・§6 から転記する。`theme.json` はパレットをトークンの5色に限定し、`custom` と `gradients` を無効にする。CSSとJSのバージョンは `filemtime()` にする。
- [ ] **Step 4:** `npm run test:e2e -- theme-base` → PASS
- [ ] **Step 5:** コミット `feat(theme): デザイントークン・共通レイアウト・CTA部品`

### Task 7: 計測・スマホ固定CTA・QRモーダル

**Files:**
- Create: `spc-theme/inc/gtm.php`, `spc-theme/template-parts/{sticky-cta.php,qr-modal.php}`, `spc-theme/assets/js/{tracking.js,sticky-cta.js,qr-modal.js}`, `tests/e2e/tracking.spec.ts`, `tests/e2e/sticky-cta.spec.ts`

**Interfaces:**
- Consumes: `spc_cta()`（Task 6）、`spc_get_setting('gtm_id' | 'line_qr_id')`（Task 2）
- Produces:
  - `window.spcTrack( event: string, params?: object ): void`：`window.dataLayer` がなければ作成する。例外は握りつぶす
  - LPに `<body data-page-type="lp">` を付ける。tracking.js が読み込み時に `landing_view` を送る
  - `[data-spc-cta]` のクリック → `line_click {cta_location}`。最初に着地したURLの `utm_*` を sessionStorage に保存し、CTAの href に付ける
  - PC（`(min-width: 768px) and (hover: hover)`）では、クリック時に既定の遷移を止めて `#spc-qr-modal`（`<dialog>`）を開く
  - 固定CTAは `.spc-sticky-cta`。`[data-sticky-hide]` を付けた要素（FV・最終CTA）が見えている間は `hidden` にする

- [ ] **Step 1: 失敗するテストを書く**：
  - GTM ID未設定 → `googletagmanager.com` のスクリプトなし。設定済み（`GTM-TEST123`）→ スクリプトと `<noscript>` iframe がある
  - LPを開く → `dataLayer` に `{event:'landing_view'}`
  - 375px幅で FV のCTAをクリック → `{event:'line_click', cta_location:'fv'}`、遷移先が `lin.ee`
  - `?utm_source=google` で着地 → CTA の href に `utm_source=google` が付く
  - **GTMの読み込みをブロック（`page.route` で abort）してもCTAがLINEに遷移し、コンソールエラーが出ない**（Review Focus 5）
  - 375px幅：ページ先頭では固定CTAが非表示、FVを過ぎると表示、最終CTAが見えると非表示。1024px幅では常に非表示
  - 1280px幅：CTAクリックでダイアログが開いて `line_click {cta_location:'fv'}` が送られ、Escで閉じると元のCTAにフォーカスが戻る
- [ ] **Step 2:** `npm run test:e2e -- tracking sticky-cta` → FAIL
- [ ] **Step 3:** 実装する。モーダルは `<dialog>` の `showModal()` を使う（フォーカス管理とEscはブラウザ標準に任せる）。QR画像が未設定のときは仮枠と仮バッジを出す。固定CTAは `env(safe-area-inset-bottom)` を考慮し、`body` に下余白を取る。
- [ ] **Step 4:** `npm run test:e2e -- tracking sticky-cta` → PASS
- [ ] **Step 5:** コミット `feat(theme): 計測イベント・スマホ固定CTA・QRモーダル`

### Task 8: LP前半（FV・悩みと考え方・3ステップ・対応メニュー）

**Files:**
- Create: `spc-theme/front-page.php`, `spc-theme/template-parts/lp/{hero.php,concerns.php,steps.php,menu.php}`, `spc-theme/assets/css/lp.css`, `tests/e2e/lp-top.spec.ts`

**Interfaces:**
- Consumes: `spc_cta()`, `spc_image_or_placeholder()`（Task 6）、`spc_get_setting('hero_sp_writing')`（Task 2）
- Produces: `spc_hero_classes(): string`（`'hero'` または `'hero hero--sp-horizontal'`）。front-page.php は lp/ の部品を sitemap.md §3 の順に `get_template_part()` で呼ぶ

**固定コピー（sitemap.md §3・DESIGN.md §4 より）**
- h1：「諦めかけた、その一着に。」
- サブ：「創業70年以上の技術を受け継ぐ職人が、衣類の状態を丁寧に確認します。」
- 対応：「特殊シミ抜き／高級ダウン・コート」
- 考え方：「必ず落とすとは言いません。職人が状態を見て、可能性とリスクを正直にお伝えします。」

- [ ] **Step 1: 失敗するテストを書く**：
  - h1がページに1つだけで、文言が完全一致
  - 1280px幅：h1の `writing-mode` が `vertical-rl`
  - 375px幅・設定が縦書き：`vertical-rl`／設定を `horizontal` に変更：`horizontal-tb`（PCは `vertical-rl` のまま）
  - 375×812で h1・サブコピー・「特殊シミ抜き」・FVのCTAがスクロールせずに見えている（`boundingBox().y + height <= 812`）
  - 3ステップは `<ol>` で項目が3つ
  - `emulateMedia({reducedMotion:'reduce'})` のとき、FV画像の `filter` が `none`
  - 対応メニューの4つのリンク先がそれぞれ `/stain-removal/` `/stain-removal/#yellowing` `/down-coat/` `/down-coat/#coat`
- [ ] **Step 2:** `npm run test:e2e -- lp-top` → FAIL
- [ ] **Step 3:** 実装する（DESIGN.md §4 のワイヤーと §7 のぼけが晴れる動き：`blur(6px)`→`0`、900ms、ease-out）。FV画像は当面、仮枠「仮｜繊維マクロ写真 16:10」で表示する。
- [ ] **Step 4:** `npm run test:e2e -- lp-top` → PASS
- [ ] **Step 5:** コミット `feat(theme): LP前半（FV・考え方・3ステップ・メニュー）`

### Task 9: LP後半（事例・職人・料金・流れ・注意・FAQ・最終CTA・構造化データ）

**Files:**
- Create: `spc-theme/template-parts/lp/{cases.php,craftsmen.php,price.php,shipping-flow.php,limits.php,faq.php,final-cta.php}`, `spc-theme/template-parts/diagnosis-card.php`, `spc-theme/inc/schema.php`, `spc-theme/assets/js/before-after.js`, `tests/e2e/lp-bottom.spec.ts`
- Modify: `spc-theme/assets/css/lp.css`, `spc-theme/inc/template-tags.php`

**Interfaces:**
- Consumes: `spc_query_args()`, `spc_is_placeholder()`（Task 4）、メタキー（Task 3）、`spc_placeholder_badge()`, `spc_image_or_placeholder()`, `spc_cta()`（Task 6）
- Produces: `spc_format_price( int $amount, bool $from ): string`（`15000,true` → `'15,000円〜'`、`0` → `'お見積り'`）

- [ ] **Step 1: 失敗するテストを書く**：
  - seed後、施工事例セクションに `.spc-diagnosis` が3件あり、それぞれに仮バッジが付く。項目名（品目・素材・シミ・他店処理・処置・仕上がり・所要日数）がある
  - Before/Afterの切り替えボタンが `aria-pressed` を切り替える（自動では動かない）
  - **After画像なし → 仮枠を表示。金額0 → 「お見積り」**（Review Focus 3）
  - **素材に60文字の値を入れても、375px幅で `scrollWidth <= 375`**（Review Focus 4）
  - 事例を全部ゴミ箱に入れる → 施工事例セクションごと出力されない
  - 料金表の「高級ダウン」の行が「15,000円〜」で仮バッジ付き
  - 職人セクションの背景色が `--color-sumi`、名前が「職人名（仮）」
  - FAQは `<details>` で最大5件。JSON-LDをパースすると `FAQPage` の質問がページ上の `<summary>` と一致し、`Organization.name` が「有限会社杉本クリーニング商会」
  - 最終CTAに `data-sticky-hide` があり、返信目安の文言は設定値（`reply_time_note`）
- [ ] **Step 2:** `npm run test:e2e -- lp-bottom` → FAIL
- [ ] **Step 3:** 実装する。各セクションは `spc_query_args()` 経由で取得し、0件なら何も出力しない。お引き受けできない場合・宅配の流れの文言は sitemap.md §3 に従う。
- [ ] **Step 4:** `npm run test:e2e -- lp-bottom` → PASS
- [ ] **Step 5:** コミット `feat(theme): LP後半と構造化データ`

### Task 10: セキュリティ設定・運用手順書・全体確認

**Files:**
- Create: `spc-core/includes/security.php`, `spc-core/tests/test-security.php`, `docs/operations.md`

**Interfaces:**
- Consumes: すべて

- [ ] **Step 1: 失敗するテストを書く**：`xmlrpc_enabled` が false、未ログインの REST `/wp/v2/users` が401、`?author=1` が投稿者アーカイブへリダイレクトせずトップへ戻る、`<meta name="generator">` を出力しない。
- [ ] **Step 2:** `npm run test:php -- --filter Security` → FAIL
- [ ] **Step 3:** `security.php` を実装する。`docs/operations.md` に次をまとめる：本番の `wp-config.php` の設定（`WP_ENVIRONMENT_TYPE=production`、`DISALLOW_FILE_EDIT`）、導入するプラグイン3つ、管理者の二要素認証、バックアップの保管期間、公開前の手順（`wp spc launch-check` が通ってからnoindexとBasic認証を外す）、依頼元向けの更新方法（事例・料金・FAQ・職人の編集）。
- [ ] **Step 4:** `npm run test:php && npm run test:e2e` → すべてPASS。375px / 768px / 1280px のスクリーンショットを撮り、DESIGN.md §1 の「やらないこと」に当てはまるものがないか目視で確認する。
- [ ] **Step 5:** コミット `feat(core): セキュリティ設定と運用手順書`

---

## 本計画の範囲外（次の計画で扱う）

- 下層ページの本文とテンプレート（特殊シミ抜き・ダウン/コート・料金・事例一覧/詳細・職人/会社・流れ/FAQ・規約系）
- 本番サーバーの構築、デプロイ、SEO・セキュリティ・バックアップの各プラグインの導入と設定
- GTMコンテナ内の設定（GA4タグ・トリガー）、Search Console の登録
