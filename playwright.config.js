import { defineConfig, devices } from '@playwright/test';
import { BASE_URL } from './e2e/support/env.js';
import { serverCommand } from './e2e/support/server.js';

// Local only — not run in CI (kept lean on purpose). Run with `bun run e2e`;
// setup in docs-src/local-setup.md. The specs share one e2e database and reset
// it themselves, so they run one at a time.
export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    workers: 1,
    timeout: 60_000,
    expect: { timeout: 10_000 },
    reporter: [['list'], ['html', { open: 'never', outputFolder: 'storage/e2e-report' }]],
    outputDir: 'storage/e2e-results',
    globalSetup: './e2e/support/global-setup.js',
    use: {
        baseURL: BASE_URL,
        actionTimeout: 10_000,
        navigationTimeout: 15_000,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        command: serverCommand(),
        url: `${BASE_URL}/up`,
        reuseExistingServer: false,
        timeout: 120_000,
        stdout: 'ignore',
        stderr: 'pipe',
    },
});
