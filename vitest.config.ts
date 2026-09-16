import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import path from 'path';

// Coverage floor for `bun run test --run --coverage` (CI runs it that way). Raise it
// as coverage improves; never lower it to make a red build pass.
const MIN_COVERAGE = 90;

export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'jsdom',
        globals: true,
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html'],
            include: ['resources/js/**/*.vue', 'resources/js/composables/**/*.js'],
            exclude: ['resources/js/app.js', 'resources/js/bootstrap.js'],
            thresholds: {
                lines: MIN_COVERAGE,
                statements: MIN_COVERAGE,
            },
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, 'resources/js'),
        },
    },
});
