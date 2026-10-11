import { closeSync, openSync } from 'node:fs';

// The SQLite file must exist before the first migrate. The server is already
// up (Playwright starts webServer first); assets were built by `bun run e2e`.
export default function globalSetup() {
    closeSync(openSync('database/e2e.sqlite', 'a'));
}
