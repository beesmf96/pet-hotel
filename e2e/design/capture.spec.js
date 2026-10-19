import { test } from '@playwright/test';
import { resetDatabase, tinker } from '../support/server.js';
import { loginCustomer } from '../support/auth.js';

// One full-page screenshot per customer page and state, for /design-review.
// Data: DesignReviewSeeder (the personas plus reviews, more stays, notifications
// and an empty account). Not a test: nothing here asserts.
const HOTEL = 'rahmans-paws-house';

function bookingId(email, status) {
    return tinker(
        `echo \\App\\Models\\Booking::where("status", "${status}")->whereHas("user", fn ($q) => $q->where("email", "${email}"))->value("id");`,
    );
}

async function shot(page, testInfo, name) {
    // The map tiles come from the internet; do not wait forever for them.
    await page.waitForLoadState('networkidle', { timeout: 5_000 }).catch(() => {});
    await page.screenshot({
        path: `storage/design-review/${testInfo.project.name}/${name}.png`,
        fullPage: true,
        animations: 'disabled',
    });
}

async function visit(page, testInfo, name, path) {
    await page.goto(path);
    await shot(page, testInfo, name);
}

test.beforeAll(() => resetDatabase('DesignReviewSeeder'));

test('guest pages', async ({ page }, testInfo) => {
    await visit(page, testInfo, '01-landing', '/');
    await visit(page, testInfo, '02-search', '/hotels');
    await visit(page, testInfo, '03-search-empty', '/hotels?city=Nowhere');
    await visit(page, testInfo, '04-hotel', `/hotels/${HOTEL}`);
    await visit(page, testInfo, '05-reviews', `/hotels/${HOTEL}/reviews`);
    await visit(page, testInfo, '06-login', '/login');
    await visit(page, testInfo, '07-register', '/register');
    await visit(page, testInfo, '08-forgot-password', '/forgot-password');
});

test('customer with history (Daniel)', async ({ page }, testInfo) => {
    const email = 'daniel@example.com';
    await loginCustomer(page, email);

    await visit(page, testInfo, '10-my-bookings', '/bookings');
    await visit(page, testInfo, '11-booking-pending', `/bookings/${bookingId(email, 'pending')}`);
    await visit(page, testInfo, '12-booking-completed', `/bookings/${bookingId(email, 'completed')}`);
    await visit(page, testInfo, '13-booking-form', `/hotels/${HOTEL}/book`);
    await visit(page, testInfo, '14-pets', '/pets');
    await visit(page, testInfo, '15-profile', '/profile');

    await page.goto('/pets');
    await page.getByRole('button', { name: /Add pet/ }).click();
    await shot(page, testInfo, '17-pet-modal');

    await page.goto('/bookings');
    await page.getByRole('button', { name: /Leave a review/ }).click();
    await shot(page, testInfo, '18-review-modal');

    await page.goto('/bookings');
    await page.getByRole('button', { name: 'Notifications' }).click();
    await shot(page, testInfo, '19-notifications');
});

test('customer with a confirmed and a cancelled stay (Aisyah)', async ({ page }, testInfo) => {
    const email = 'aisyah@example.com';
    await loginCustomer(page, email);

    const confirmed = bookingId(email, 'confirmed');
    await visit(page, testInfo, '20-booking-confirmed', `/bookings/${confirmed}`);
    await visit(page, testInfo, '21-booking-cancelled', `/bookings/${bookingId(email, 'cancelled')}`);
    await visit(page, testInfo, '22-booking-confirmation', `/bookings/${confirmed}/confirmation`);
});

test('empty account (Nina)', async ({ page }, testInfo) => {
    await loginCustomer(page, 'nina@example.com');

    await visit(page, testInfo, '30-my-bookings-empty', '/bookings');
    await visit(page, testInfo, '31-pets-empty', '/pets');
    await visit(page, testInfo, '32-booking-form-no-pets', `/hotels/${HOTEL}/book`);
});
