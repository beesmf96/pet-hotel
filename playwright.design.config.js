import { defineConfig, devices } from '@playwright/test';
import { BASE_URL } from './e2e/support/env.js';
import { serverCommand } from './e2e/support/server.js';

// Screenshots of every customer page for /design-review
// (.claude/skills/design-review/). Local only, like the e2e suite, and on the
// same throwaway server and e2e database. Run with `bun run design:capture`;
// the pictures land in storage/design-review/{phone,desktop}/.
export default defineConfig({
    testDir: './e2e/design',
    fullyParallel: false,
    workers: 1,
    timeout: 120_000,
    reporter: [['list']],
    outputDir: 'storage/design-review/results',
    globalSetup: './e2e/support/global-setup.js',
    use: {
        baseURL: BASE_URL,
        actionTimeout: 10_000,
        navigationTimeout: 15_000,
    },
    projects: [
        { name: 'phone', use: { ...devices['Desktop Chrome'], viewport: { width: 375, height: 812 } } },
        { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } } },
    ],
    webServer: {
        command: serverCommand(),
        url: `${BASE_URL}/up`,
        reuseExistingServer: false,
        timeout: 120_000,
        stdout: 'ignore',
        stderr: 'pipe',
    },
});
