import { expect } from '@playwright/test';
import { HOTEL } from './env.js';
import { clickDay } from './calendar.js';

/** Fill the booking form; does not submit. */
export async function fillRequest(page, { pet, checkIn, checkOut, notes }) {
    await page.goto(`/hotels/${HOTEL.slug}/book`);
    await page.getByLabel('Select pet *').selectOption({ label: pet });
    await clickDay(page, checkIn);
    await clickDay(page, checkOut);
    if (notes) await page.getByLabel('Special requests (optional)').fill(notes);
}

/** Send a booking request and land on its confirmation page. */
export async function requestStay(page, stay) {
    await fillRequest(page, stay);
    await page.getByRole('button', { name: 'Request Booking' }).click();
    await expect(page).toHaveURL(/\/bookings\/\d+\/confirmation/);
}
