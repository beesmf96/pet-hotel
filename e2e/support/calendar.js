import { expect } from '@playwright/test';
import { monthLabel } from './dates.js';

/** Move the availability calendar to the month of `date`. */
export async function showMonth(page, date) {
    const header = page.locator('span.font-display.font-bold.text-base').first();
    for (let i = 0; i < 13; i++) {
        if ((await header.textContent())?.trim() === monthLabel(date)) return;
        await page.getByRole('button', { name: '›' }).click();
    }
    await expect(header).toHaveText(monthLabel(date));
}

/** The calendar cell for `date` (the month must be showing). */
export function dayCell(page, date) {
    return page.locator('.grid.grid-cols-7 > div').filter({ hasText: new RegExp(`^${date.getDate()}$`) });
}

export async function clickDay(page, date) {
    await showMonth(page, date);
    await dayCell(page, date).click();
}
