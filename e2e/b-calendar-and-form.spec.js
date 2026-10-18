import { test, expect } from '@playwright/test';
import { closeDate, resetDatabase, setDateCapacity } from './support/server.js';
import { loginCustomer } from './support/auth.js';
import { HOTEL, USERS } from './support/env.js';
import { dayOfNextMonth, iso } from './support/dates.js';
import { clickDay, dayCell, showMonth } from './support/calendar.js';

// Checklist B: the customer calendar and the booking form.
const before = dayOfNextMonth(10);
const closed = dayOfNextMonth(11);
const limited = dayOfNextMonth(12);

test.beforeEach(async ({ page }) => {
    resetDatabase();
    closeDate(iso(closed));
    setDateCapacity(iso(limited), 1);
    await loginCustomer(page, USERS.alice);
});

test('B1 calendar marks closed days and labels spots left', async ({ page }) => {
    await page.goto(`/hotels/${HOTEL.slug}`);
    await showMonth(page, closed);

    await expect(dayCell(page, closed)).toHaveAttribute('title', 'Closed');
    await expect(dayCell(page, closed)).toHaveClass(/bg-gray-100/);
    await expect(dayCell(page, before)).toHaveAttribute('title', '2 spots left');
    await expect(dayCell(page, before)).toHaveClass(/bg-green-50/);
    await expect(dayCell(page, limited)).toHaveAttribute('title', '1 spot left');
});

test('B2 the form shows the hotel times', async ({ page }) => {
    await page.goto(`/hotels/${HOTEL.slug}/book`);
    await expect(page.getByText('Check-in from 2:00 PM · check-out by 12:00 PM')).toBeVisible();
});

test('B3 a stay cannot cross a closed night', async ({ page }) => {
    await page.goto(`/hotels/${HOTEL.slug}/book`);
    await clickDay(page, before);
    await clickDay(page, limited);

    // The click on the far side of the closed night starts a new check-in.
    await expect(dayCell(page, limited)).toHaveClass(/bg-ink/);
    await expect(dayCell(page, before)).not.toHaveClass(/bg-ink/);
});

test('B4 check-out may fall on a closed day', async ({ page }) => {
    await page.goto(`/hotels/${HOTEL.slug}/book`);
    await clickDay(page, before);
    await clickDay(page, closed);

    await expect(dayCell(page, before)).toHaveClass(/bg-ink/);
    await expect(dayCell(page, closed)).toHaveClass(/bg-ink/);
});

test('B5 the price summary uses ringgit with separators', async ({ page }) => {
    await page.goto(`/hotels/${HOTEL.slug}/book`);
    await page.getByLabel('Select pet *').selectOption({ label: 'Buddy (Dog)' });
    await clickDay(page, dayOfNextMonth(13));
    await clickDay(page, dayOfNextMonth(15));

    await expect(page.getByText('RM 650.00 × 2 nights')).toBeVisible();
    await expect(page.getByText('RM 1,300.00').first()).toBeVisible();
});

test('B6 searched dates carry through to the booking form', async ({ page }) => {
    const checkIn = dayOfNextMonth(13);
    const checkOut = dayOfNextMonth(15);
    await page.goto(`/hotels?check_in=${iso(checkIn)}&check_out=${iso(checkOut)}`);
    await page.getByText(HOTEL.name).click();
    await expect(page.getByText(/\(2\s+nights\)/)).toBeVisible();

    await page.getByRole('link', { name: 'Request to book' }).click();
    await page.getByLabel('Select pet *').selectOption({ label: 'Buddy (Dog)' });
    await expect(dayCell(page, checkIn)).toHaveClass(/bg-ink/);
    await expect(dayCell(page, checkOut)).toHaveClass(/bg-ink/);
    await expect(page.getByText('RM 650.00 × 2 nights')).toBeVisible();
});
