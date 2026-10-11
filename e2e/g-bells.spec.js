import { test, expect } from '@playwright/test';
import { resetDatabase, tinker } from './support/server.js';
import { loginCustomer } from './support/auth.js';
import { USERS } from './support/env.js';
import { dayOfNextMonth } from './support/dates.js';
import { requestStay } from './support/customer.js';

// Checklist G: one account can be a customer and an owner; each bell keeps its own.
test.beforeEach(async ({ page, browser }) => {
    resetDatabase();

    // Alice's request puts "New booking request" in the owner's panel bell.
    await loginCustomer(page, USERS.alice);
    await requestStay(page, { pet: 'Buddy (Dog)', checkIn: dayOfNextMonth(10), checkOut: dayOfNextMonth(11) });

    // And the owner gets one customer-site notification of their own.
    tinker(
        '\\App\\Models\\User::where("email", "owner@e2e.test")->first()->notify(new \\App\\Notifications\\BookingRequested(\\App\\Models\\Booking::first()));',
    );
});

test('G1 the customer bell leaves out owner-panel notifications', async ({ browser }) => {
    const owner = await (await browser.newContext()).newPage();
    await loginCustomer(owner, USERS.owner);
    await owner.getByRole('button', { name: 'Notifications' }).click();

    await expect(owner.getByText('Your booking request for E2E Paws Hotel has been received.')).toBeVisible();
    await expect(owner.getByText('New booking request')).toHaveCount(0);
});

test('G2 "mark all read" on the customer site leaves the panel bell unread', async ({ browser }) => {
    const owner = await (await browser.newContext()).newPage();
    await loginCustomer(owner, USERS.owner);
    await owner.getByRole('button', { name: 'Notifications' }).click();
    await owner.getByRole('button', { name: 'Mark all read' }).click();
    await expect(owner.getByRole('button', { name: 'Mark all read' })).toHaveCount(0);

    // Same account, same session: the owner panel opens without a second login.
    await owner.goto('/owner/bookings');
    await owner.getByRole('button', { name: 'Notifications' }).click();
    await expect(owner.getByText('New booking request').first()).toBeVisible();
    await expect(owner.getByRole('button', { name: /Mark all as read/i })).toBeVisible();
});
