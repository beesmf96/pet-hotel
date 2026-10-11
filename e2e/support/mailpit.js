import { expect } from '@playwright/test';
import { MAILPIT_URL } from './env.js';

// Only e2e addresses are searched or deleted, so dev mail in the same Mailpit
// is left alone.
export async function clearE2eMail(request) {
    await request.delete(`${MAILPIT_URL}/api/v1/search`, { params: { query: 'to:@e2e.test' } });
}

async function search(request, to, subject) {
    const query = `to:"${to}" subject:"${subject}"`;
    const response = await request.get(`${MAILPIT_URL}/api/v1/search`, { params: { query } });
    return (await response.json()).messages ?? [];
}

/** The newest matching message, as { subject, text }. Waits for it to arrive. */
export async function findMail(request, { to, subject }) {
    let messages = [];
    await expect
        .poll(async () => (messages = await search(request, to, subject)).length, {
            message: `mail to ${to} with "${subject}"`,
            timeout: 10_000,
        })
        .toBeGreaterThan(0);

    const message = await (await request.get(`${MAILPIT_URL}/api/v1/message/${messages[0].ID}`)).json();
    return { subject: message.Subject, text: message.Text };
}

export async function countMail(request, { to, subject }) {
    return (await search(request, to, subject)).length;
}
