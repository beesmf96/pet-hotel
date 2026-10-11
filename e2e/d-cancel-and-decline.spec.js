import { test, expect } from '@playwright/test';
import { resetDatabase } from './support/server.js';
import { loginCustomer, loginPanel } from './support/auth.js';
import { HOTEL, USERS } from './support/env.js';
import { dayOfNextMonth } from './support/dates.js';
import { clearE2eMail, countMail, findMail } from './support/mailpit.js';
import { requestStay } from './support/customer.js';
import { bookingAction, confirmedBookingAction, overviewRow } from './support/owner.js';

// Checklist D: the three cancellation emails, and who else hears about it.
const checkIn = dayOfNextMonth(24);
const checkOut = dayOfNextMonth(25);
const stay = (pet) => ({ pet, checkIn, checkOut });

test.beforeEach(async ({ request }) => {
    resetDatabase();
    await clearE2eMail(request);
});

test('D1 an owner declining sends "declined" and does not notify the owners', async ({ page, browser, request }) => {
    await loginCustomer(page, USERS.ben);
    await requestStay(page, stay('Rex (Dog)'));

    const owner = await (await browser.newContext()).newPage();
    await loginPanel(owner, 'owner', USERS.owner);
    await bookingAction(owner, 'owner', 'Ben Guest', 'Decline');

    const mail = await findMail(request, { to: USERS.ben, subject: `Booking Request Declined — ${HOTEL.name}` });
    expect(mail.text).toContain('could not take your booking request');
    expect(mail.text).toContain('No booking was made');
    expect(mail.text).toContain('Find Another Stay');
    expect(mail.text).not.toContain('did not cancel');
    expect(await countMail(request, { to: USERS.owner, subject: 'Booking request cancelled' })).toBe(0);
});

test('D2 a guest cancelling sends "cancelled" and tells the owners', async ({ page, browser, request }) => {
    await loginCustomer(page, USERS.alice);
    await requestStay(page, stay('Buddy (Dog)'));
    const id = page.url().match(/bookings\/(\d+)/)[1];

    await page.goto(`/bookings/${id}`);
    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Cancel Booking' }).click();
    await expect(page.getByText('Booking cancelled.')).toBeVisible();

    const mail = await findMail(request, { to: USERS.alice, subject: `Booking Request Cancelled — ${HOTEL.name}` });
    expect(mail.text).toContain('You cancelled your booking request');
    expect(mail.text).toContain('If you did not cancel it yourself');

    const ownerMail = await findMail(request, {
        to: USERS.owner,
        subject: `Booking request cancelled — ${HOTEL.name}`,
    });
    expect(ownerMail.text).toContain('Guest: Alice Guest');

    const owner = await (await browser.newContext()).newPage();
    await loginPanel(owner, 'owner', USERS.owner);
    await owner.getByRole('button', { name: 'Notifications' }).click();
    await expect(owner.getByText('Booking request cancelled').first()).toBeVisible();
});

test('D3–D4 the admin cancelling a confirmed stay says so and frees the spot', async ({ page, browser, request }) => {
    await loginCustomer(page, USERS.alice);
    await requestStay(page, stay('Buddy (Dog)'));

    const owner = await (await browser.newContext()).newPage();
    await loginPanel(owner, 'owner', USERS.owner);
    await bookingAction(owner, 'owner', 'Alice Guest', 'Confirm');
    await expect(await overviewRow(owner, checkIn)).toContainText('Limited');

    const admin = await (await browser.newContext()).newPage();
    await loginPanel(admin, 'admin', USERS.admin);
    await confirmedBookingAction(admin, 'admin', 'Alice Guest', 'Cancel');

    const mail = await findMail(request, {
        to: USERS.alice,
        subject: `Booking Cancelled by the Hotel — ${HOTEL.name}`,
    });
    expect(mail.text).toContain('has cancelled your confirmed booking');
    expect(mail.text).toContain('View Booking');

    const row = await overviewRow(owner, checkIn);
    await expect(row).toContainText('Open');
});
