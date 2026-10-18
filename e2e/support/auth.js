import { expect } from '@playwright/test';
import { PASSWORD } from './env.js';

export async function loginCustomer(page, email) {
    await page.goto('/login');
    await page.getByLabel('Email', { exact: true }).fill(email);
    await page.getByLabel('Password', { exact: true }).fill(PASSWORD);
    await page.getByRole('button', { name: 'Sign in' }).click();
    await expect(page).toHaveURL(/\/bookings/);
}

export async function loginPanel(page, panel, email) {
    await page.goto(`/${panel}/login`);
    await page.getByLabel('Email address').fill(email);
    await page.locator('input[type="password"]').fill(PASSWORD);
    await page.getByRole('button', { name: 'Sign in' }).click();
    await expect(page).not.toHaveURL(/\/login/);
}
