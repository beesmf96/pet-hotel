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

/** Open a booking's pop-up from its notification in the owner panel's bell. */
export async function openFromBell(page, guest) {
    await page.goto('/owner/bookings');
    await page.getByRole('button', { name: 'Notifications' }).click();
    const item = page.locator('.fi-no-notification').filter({ hasText: guest }).first();
    await item.getByRole('link', { name: 'View booking' }).click();
    const popup = page.locator('.fi-modal-window:visible');
    await expect(popup).toContainText(`Booking from ${guest}`);
    return popup;
}

export async function expectNotice(page, title) {
    await expect(page.getByText(title).first()).toBeVisible();
}

const isLivewireUpdate = (response) => /\/livewire[^/]*\/update/.test(response.url());

/** Do `action` and wait for the Livewire request it sends. */
async function andLivewire(page, action, isResponse = isLivewireUpdate) {
    await Promise.all([page.waitForResponse(isResponse), action()]);
}

/**
 * Owner panel → Availability → Change dates. "From" and "Closed" are live
 * fields: a click while their request is out can be lost in the re-render,
 * so each one waits for it. Submit waits for the save too: Filament hides the
 * modal at once, and a page.goto() straight after can cancel it. Pass
 * `invalid: true` when the form is meant to fail (the browser may block it,
 * so no answer comes).
 */
export async function changeDates(page, { from, until, closed = false, capacity = null, invalid = false }) {
    await page.goto('/owner/availabilities');
    await page.getByRole('button', { name: 'Change dates' }).click();
    const modal = page.getByRole('dialog');
    // Wait for the form first, so the request awaited below is the field's own.
    await modal.getByLabel('From').waitFor();
    await andLivewire(page, () => modal.getByLabel('From').fill(iso(from)));
    await modal.getByLabel('Until (last night)').fill(iso(until));
    if (closed) await andLivewire(page, () => modal.getByRole('switch', { name: 'Closed' }).click());
    if (capacity !== null) await modal.getByLabel('Pets per night').fill(String(capacity));
    const submit = modal.getByRole('button', { name: 'Submit' });
    if (invalid) return submit.click();
    await andLivewire(
        page,
        () => submit.click(),
        (response) => response.request().postData()?.includes('"callMountedAction"'),
    );
}
