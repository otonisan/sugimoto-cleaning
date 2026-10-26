import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  globalSetup: './tests/e2e/global-setup.ts',
  use: { baseURL: 'http://localhost:8888' },
  projects: [{ name: 'chromium', use: { browserName: 'chromium' } }],
});
