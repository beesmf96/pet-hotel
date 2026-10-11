import { expect } from '@playwright/test';
import { iso, longDate, monthLabel } from './dates.js';

/** The Daily overview row for one night, after picking that night's month. */
export async function overviewRow(page, date) {
    await page.goto('/owner/daily-overview');
    await page.getByRole('button', { name: 'Filter', exact: true }).click();
    await page.getByRole('combobox', { name: 'Month' }).selectOption({ label: monthLabel(date) });
    await page.getByRole('button', { name: 'Apply filters' }).click();
    return page.getByRole('row', { name: new RegExp(`^${longDate(date)} `) });
}

/** Click a row action on the owner (or admin) bookings table and confirm the modal. */
export async function bookingAction(page, panel, guest, action) {
    await page.goto(`/${panel}/bookings`);
    const row = page.locator('table tbody tr').filter({ hasText: guest }).filter({ hasText: 'Pending' }).first();
    await row.getByRole('button', { name: action }).click();
    // Filament's confirmation modal submits with "Confirm" whatever the action.
    await page.locator('.fi-modal-window:visible').getByRole('button', { name: 'Confirm', exact: true }).click();
}

/** The confirmed booking row of a guest, for actions on confirmed stays. */
export async function confirmedBookingAction(page, panel, guest, action) {
    await page.goto(`/${panel}/bookings`);
    const row = page.locator('table tbody tr').filter({ hasText: guest }).filter({ hasText: 'Confirmed' }).first();
    await row.getByRole('button', { name: action }).click();
    // Filament's confirmation modal submits with "Confirm" whatever the action.
    await page.locator('.fi-modal-window:visible').getByRole('button', { name: 'Confirm', exact: true }).click();
}

export async function expectNotice(page, title) {
    await expect(page.getByText(title).first()).toBeVisible();
}

/** Owner panel → Availability → Change dates. */
export async function changeDates(page, { from, until, closed = false, capacity = null }) {
    await page.goto('/owner/availabilities');
    await page.getByRole('button', { name: 'Change dates' }).click();
    const modal = page.getByRole('dialog');
    await modal.getByLabel('From').fill(iso(from));
    await modal.getByLabel('Until (last night)').fill(iso(until));
    if (closed) await modal.getByRole('switch', { name: 'Closed' }).click();
    if (capacity !== null) await modal.getByLabel('Pets per night').fill(String(capacity));
    await modal.getByRole('button', { name: 'Submit' }).click();
}
