import { test, expect } from '@playwright/test';
import { resetDatabase, setDateCapacity } from './support/server.js';
import { loginCustomer, loginPanel } from './support/auth.js';
import { HOTEL, USERS } from './support/env.js';
import { dayOfNextMonth, iso, longDate } from './support/dates.js';
import { clearE2eMail, findMail } from './support/mailpit.js';
import { fillRequest, requestStay } from './support/customer.js';
import { bookingAction, expectNotice, openFromBell, overviewRow } from './support/owner.js';
import { dayCell, showMonth } from './support/calendar.js';

// Checklist C: requests, the owner's notices, confirm, and the last spot.
const night = dayOfNextMonth(20);
const checkOut = dayOfNextMonth(21);

test.beforeEach(async ({ request }) => {
    resetDatabase();
    setDateCapacity(iso(night), 1);
    await clearE2eMail(request);
});

test('C1–C8 two guests ask for the last spot', async ({ browser, request }) => {
    const alice = await (await browser.newContext()).newPage();
    const ben = await (await browser.newContext()).newPage();
    const owner = await (await browser.newContext()).newPage();
    await loginCustomer(alice, USERS.alice);
    await loginCustomer(ben, USERS.ben);
    await loginPanel(owner, 'owner', USERS.owner);

    await test.step('C1 Alice requests the night and sees the times', async () => {
        await requestStay(alice, { pet: 'Buddy (Dog)', checkIn: night, checkOut, notes: 'Needs a walk at 7am' });
        await expect(alice.getByText(', from 2:00 PM')).toBeVisible();
        await expect(alice.getByText(', by 12:00 PM')).toBeVisible();
        await expect(alice.getByText('RM 650.00')).toBeVisible();
    });

    await test.step('C2 both emails go out, in ringgit', async () => {
        const guest = await findMail(request, { to: USERS.alice, subject: `Booking Request Received — ${HOTEL.name}` });
        expect(guest.text).toContain('Total: RM 650.00');
        expect(guest.text).toContain(`${longDate(night)}, from 2:00 PM`);
        expect(guest.text).not.toContain('$');

        const ownerMail = await findMail(request, { to: USERS.owner, subject: `New booking request — ${HOTEL.name}` });
        expect(ownerMail.text).toContain('Guest: Alice Guest');
        expect(ownerMail.text).toContain('Pet: Buddy (Dog)');
        expect(ownerMail.text).toContain('(1 night)');
        expect(ownerMail.text).toContain('Notes: Needs a walk at 7am');
    });

    await test.step('C3 the owner sees the bell item and the pending count', async () => {
        await owner.goto('/owner/bookings');
        await expect(owner.getByRole('link', { name: /Bookings\s*1/ })).toBeVisible();
        await owner.getByRole('button', { name: 'Notifications' }).click();
        await expect(owner.getByText('New booking request').first()).toBeVisible();
    });

    await test.step('C4 Ben may still request the same night', async () => {
        await requestStay(ben, { pet: 'Rex (Dog)', checkIn: night, checkOut });
        await owner.goto('/owner/bookings');
        await expect(owner.getByRole('link', { name: /Bookings\s*2/ })).toBeVisible();
    });

    await test.step('C4b Ben cannot ask twice for Rex on the same night', async () => {
        await fillRequest(ben, { pet: 'Rex (Dog)', checkIn: night, checkOut });
        await ben.getByRole('button', { name: 'Request Booking' }).click();
        await expect(ben.getByText(`Rex already has a booking request at ${HOTEL.name}`)).toBeVisible();
        await expect(ben).toHaveURL(/\/book$/);
    });

    // Ben opens the form while the night still has a spot; he submits it after
    // the owner confirms Alice (C8), so only the server can catch it.
    const benLate = await ben.context().newPage();
    await fillRequest(benLate, { pet: 'Rex (Dog)', checkIn: night, checkOut });

    await test.step('C5 confirming Alice from the bell pop-up works and emails her', async () => {
        const popup = await openFromBell(owner, 'Alice Guest');
        await expect(popup).toContainText('Beagle');
        await expect(popup).toContainText('Needs a walk at 7am');
        await expect(popup).toContainText('from 2:00 PM');
        await popup.getByRole('button', { name: 'Confirm', exact: true }).click();
        await owner
            .locator('.fi-modal-window:visible')
            .filter({ hasText: 'This will confirm the booking' })
            .getByRole('button', { name: 'Confirm', exact: true })
            .click();
        const mail = await findMail(request, { to: USERS.alice, subject: `Booking Confirmed — ${HOTEL.name}` });
        expect(mail.text).toContain('Total: RM 650.00');
    });

    await test.step('C6 confirming Ben is refused', async () => {
        await bookingAction(owner, 'owner', 'Ben Guest', 'Confirm');
        await expectNotice(owner, 'Cannot confirm this booking');
        await owner.goto('/owner/bookings');
        await expect(owner.locator('table tbody tr').filter({ hasText: 'Ben Guest' })).toContainText(/pending/i);
    });

    await test.step('C7 the night shows as full everywhere, search included', async () => {
        await ben.goto(`/hotels/${HOTEL.slug}`);
        await showMonth(ben, night);
        await expect(dayCell(ben, night)).toHaveAttribute('title', 'Full');

        const row = await overviewRow(owner, night);
        await expect(row).toContainText('Full');

        await ben.goto(`/hotels?check_in=${iso(night)}&check_out=${iso(checkOut)}`);
        await expect(ben.getByText('No hotels with space')).toBeVisible();
        await ben.goto(`/hotels?check_in=${iso(checkOut)}&check_out=${iso(dayOfNextMonth(22))}`);
        await expect(ben.getByText(HOTEL.name)).toBeVisible();
    });

    await test.step('C8 a request sent after the night filled is refused', async () => {
        await benLate.getByRole('button', { name: 'Request Booking' }).click();
        await expect(
            benLate.getByText('The hotel is full or closed on at least one night of this stay.'),
        ).toBeVisible();
        await expect(benLate).toHaveURL(/\/book$/);
    });
});
