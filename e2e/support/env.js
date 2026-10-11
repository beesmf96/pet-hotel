// Settings for the throwaway e2e app. They are passed to `docker compose run`
// as separate -e arguments (never one shell string: see
// docs-src/knowledge/zsh-does-not-split-unquoted-variables.md) and reach PHP
// because the server is plain `php -S`, not `artisan serve`, which would drop
// them (docs-src/knowledge/artisan-serve-drops-environment-variables.md).
export const E2E_PORT = 8001;
export const BASE_URL = `http://localhost:${E2E_PORT}`;
export const MAILPIT_URL = 'http://mailpit.local';
export const CONTAINER = 'pet_hotel_e2e';
export const DATABASE = '/var/www/database/e2e.sqlite';

export const E2E_ENV = {
    APP_ENV: 'e2e',
    APP_URL: BASE_URL,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: DATABASE,
    QUEUE_CONNECTION: 'sync',
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'file',
    MAIL_MAILER: 'smtp',
    MAIL_HOST: 'mailpit',
    MAIL_PORT: '1025',
    PHP_CLI_SERVER_WORKERS: '4',
};

export const PASSWORD = 'password';
export const USERS = {
    owner: 'owner@e2e.test',
    admin: 'admin@e2e.test',
    alice: 'alice@e2e.test',
    ben: 'ben@e2e.test',
};
export const HOTEL = { name: 'E2E Paws Hotel', slug: 'e2e-paws' };
