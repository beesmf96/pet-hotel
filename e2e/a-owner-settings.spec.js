import { test, expect } from '@playwright/test';
import { resetDatabase } from './support/server.js';
import { loginPanel } from './support/auth.js';
import { USERS } from './support/env.js';
import { addDays, daysFromToday, iso } from './support/dates.js';
import { changeDates, overviewRow } from './support/owner.js';

// Checklist A: owner hotel settings, date changes, and the daily overview.
const d1 = daysFromToday(20);
const d2 = addDays(d1, 1);

test.beforeEach(async ({ page }) => {
    resetDatabase();
    await loginPanel(page, 'owner', USERS.owner);
});

test('A1 hotel settings save capacity and times', async ({ page }) => {
    await page.goto('/owner/hotel-settings');
    await page.getByLabel('Pets per night').fill('3');
    await page.getByLabel('Check-in from').fill('15:00');
    await page.getByLabel('Check-out by').fill('11:00');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('Hotel settings saved')).toBeVisible();

    await page.reload();
    await expect(page.getByLabel('Pets per night')).toHaveValue('3');
    await expect(page.getByLabel('Check-in from')).toHaveValue('15:00');
});

test('A2–A3 close one date and lower another', async ({ page }) => {
    await changeDates(page, { from: d1, until: d1, closed: true });
    await expect(page.getByText('Updated 1 night')).toBeVisible();
    await changeDates(page, { from: d2, until: d2, capacity: 1 });

    const rows = page.locator('table tbody tr');
    await expect(rows).toHaveCount(2);
    await expect(rows.nth(1)).toContainText('1');
});

test('A4 capacity is required unless the dates are closed', async ({ page }) => {
    await changeDates(page, { from: d1, until: d1 });

    // The browser's own required check stops the submit, so the form stays open.
    const capacity = page.getByRole('dialog').getByLabel('Pets per night');
    await expect(capacity).toBeVisible();
    expect(await capacity.evaluate((input) => input.validity.valueMissing)).toBe(true);
    await expect(page.getByText('Updated 1 night')).toHaveCount(0);
});

test('A5 the last night cannot be before the first', async ({ page }) => {
    await changeDates(page, { from: d2, until: d1, closed: true });
    await expect(page.getByRole('dialog')).toContainText('must be a date after or equal to');
});

test('A6 daily overview shows the changes', async ({ page }) => {
    await changeDates(page, { from: d1, until: d1, closed: true });
    await changeDates(page, { from: d2, until: d2, capacity: 1 });

    await expect(await overviewRow(page, d1)).toContainText('Closed');

    const changed = await overviewRow(page, d2);
    await expect(changed).toContainText('Changed for this date');
    await expect(changed).toContainText('Open');
});
