import { test, expect } from '@playwright/test';
import { resetDatabase, setDateCapacity } from './support/server.js';
import { loginCustomer, loginPanel } from './support/auth.js';
import { HOTEL, USERS } from './support/env.js';
import { dayOfNextMonth, iso } from './support/dates.js';
import { dayCell, showMonth } from './support/calendar.js';

// Checklist E: a capacity change reaches every date; reset brings dates back.
const normal = dayOfNextMonth(10);
const changed = dayOfNextMonth(11);

test.beforeEach(async ({ page }) => {
    resetDatabase();
    setDateCapacity(iso(changed), 1);
    await loginPanel(page, 'owner', USERS.owner);
});

async function spotsOn(browser, date) {
    const guest = await (await browser.newContext()).newPage();
    await loginCustomer(guest, USERS.alice);
    await guest.goto(`/hotels/${HOTEL.slug}`);
    await showMonth(guest, date);
    return dayCell(guest, date);
}

test('E1 a new hotel capacity reaches every normal date', async ({ page, browser }) => {
    await page.goto('/owner/hotel-settings');
    await page.getByLabel('Pets per night').fill('5');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('Hotel settings saved')).toBeVisible();

    await expect(await spotsOn(browser, normal)).toHaveAttribute('title', '5 spots left');
    await expect(await spotsOn(browser, changed)).toHaveAttribute('title', '1 spot left');
});

test('E2 reset dates puts a changed date back to normal', async ({ page, browser }) => {
    await page.goto('/owner/availabilities');
    await expect(page.locator('table tbody tr')).toHaveCount(1);

    await page.getByRole('button', { name: 'Reset dates' }).click();
    const modal = page.locator('.fi-modal-window:visible');
    await modal.getByLabel('From').fill(iso(normal));
    await modal.getByLabel('Until (last night)').fill(iso(changed));
    await modal.getByRole('button', { name: 'Submit' }).click();
    await expect(page.getByText('Reset 1 night')).toBeVisible();
    await expect(page.locator('table tbody tr').filter({ hasText: iso(changed).slice(-2) })).toHaveCount(0);

    await expect(await spotsOn(browser, changed)).toHaveAttribute('title', '2 spots left');
});
