import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  globalSetup: './tests/e2e/global-setup.ts',
  // テストは1つのWordPress（設定・投稿）を共有し、一部は設定を書き換えるため、並列にしない。
  workers: 1,
  // WP-CLI（wp-env の cli コンテナ経由）は1回あたり数秒かかり、設定を書き換えるテストは30秒を超えることがあるため、上限を延ばす。
  timeout: 90_000,
  use: { baseURL: 'http://localhost:8888' },
  projects: [{ name: 'chromium', use: { browserName: 'chromium' } }],
});
