import { execFileSync } from 'node:child_process';
import { CONTAINER, DATABASE, E2E_ENV, E2E_PORT } from './env.js';

// The argument list for the e2e server. Playwright's webServer needs a string;
// every value is a plain token with no spaces, so joining is safe.
export function serverCommand() {
    const env = Object.entries(E2E_ENV).flatMap(([key, value]) => ['-e', `${key}=${value}`]);

    return [
        'docker',
        'compose',
        'run',
        '--rm',
        '--no-deps',
        '--name',
        CONTAINER,
        // Laravel's router script serves from the current directory.
        '-w',
        '/var/www/public',
        '-p',
        `${E2E_PORT}:${E2E_PORT}`,
        ...env,
        'app',
        'php',
        '-S',
        `0.0.0.0:${E2E_PORT}`,
        '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php',
    ].join(' ');
}

function artisan(...args) {
    return execFileSync('docker', ['exec', '-w', '/var/www', CONTAINER, 'php', 'artisan', ...args], {
        encoding: 'utf8',
    });
}

/**
 * Fresh e2e database. Refuses unless the running e2e container resolves to the
 * e2e SQLite file — the check that would have stopped the dev-database wipe.
 * The design-review capture passes DesignReviewSeeder for fuller pages.
 */
export function resetDatabase(seeder = 'E2eSeeder') {
    const resolved = artisan(
        'tinker',
        '--execute=echo config("app.env"), "|", config("database.default"), "|", config("database.connections.sqlite.database");',
    ).trim();

    if (resolved !== `e2e|sqlite|${DATABASE}`) {
        throw new Error(`Refusing to reset: the e2e app resolved to "${resolved}".`);
    }

    artisan('migrate:fresh', `--seeder=${seeder}`, '--force');
}

/** Run PHP in the e2e app, for test setup. Same container, same e2e database. */
export function tinker(code) {
    return artisan('tinker', `--execute=${code}`).trim();
}

// Setup shortcuts on the seeded hotel (slug e2e-paws).
const hotel = '\\App\\Models\\PetHotel::where("slug", "e2e-paws")->first()';

export function closeDate(date) {
    tinker(`${hotel}->availabilities()->create(["date" => "${date}", "is_blocked" => true]);`);
}

export function setDateCapacity(date, capacity) {
    tinker(`${hotel}->availabilities()->create(["date" => "${date}", "capacity" => ${capacity}]);`);
}
