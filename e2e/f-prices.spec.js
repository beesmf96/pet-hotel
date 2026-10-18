import { test, expect } from '@playwright/test';
import { resetDatabase, tinker } from './support/server.js';
import { loginCustomer, loginPanel } from './support/auth.js';
import { HOTEL, USERS } from './support/env.js';

// Checklist F: every price in ringgit, and no booking for an unpriced pet type.
test.beforeEach(() => {
    resetDatabase();
});

test('F1 the admin pricing tab shows ringgit', async ({ page }) => {
    await loginPanel(page, 'admin', USERS.admin);
    const id = tinker('echo \\App\\Models\\PetHotel::where("slug", "e2e-paws")->value("id");');
    await page.goto(`/admin/hotels/${id}/edit`);
    await page.getByRole('tab', { name: 'Pricing' }).click();
    // The relation manager loads when it scrolls into view.
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await expect(page.getByText('RM 650.00')).toBeVisible();
    await expect(page.getByText('RM 45.50')).toBeVisible();
});

test('F2 a pet type with no price cannot be booked', async ({ page }) => {
    await loginCustomer(page, USERS.alice);
    await page.goto(`/hotels/${HOTEL.slug}/book`);
    await page.getByLabel('Pet', { exact: true }).selectOption({ label: 'Tweety (Bird)' });

    await expect(page.getByText('no pricing listed for')).toBeVisible();
    await expect(page.getByText('Tweety cannot be booked here')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Request booking' })).toBeDisabled();
});

test('F3 search and hotel pages print ringgit', async ({ page }) => {
    await page.goto('/hotels');
    await expect(page.getByText('RM 46').first()).toBeVisible();

    await page.goto(`/hotels/${HOTEL.slug}`);
    await expect(page.getByText('RM 650.00')).toBeVisible();
    await expect(page.getByText('RM 45.50')).toBeVisible();
});

test('F4 both bookings tables print ringgit with separators', async ({ page, browser }) => {
    tinker(
        '\\App\\Models\\Booking::factory()->for(\\App\\Models\\PetHotel::where("slug", "e2e-paws")->first(), "hotel")->create(["total_price" => 1234.5]);',
    );

    await loginPanel(page, 'owner', USERS.owner);
    await page.goto('/owner/bookings');
    await expect(page.getByText('RM 1,234.50')).toBeVisible();

    const admin = await (await browser.newContext()).newPage();
    await loginPanel(admin, 'admin', USERS.admin);
    await admin.goto('/admin/bookings');
    await expect(admin.getByText('RM 1,234.50')).toBeVisible();
    await expect(admin.getByText('MYR')).toHaveCount(0);
});
