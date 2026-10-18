import { describe, it, expect } from 'vitest';

import { useFormatDate } from '@/composables/useFormatDate.js';

const { formatDate, formatDateTime } = useFormatDate();

describe('useFormatDate', () => {
    it('formats a plain date string day first, like Dates::SHORT', () => {
        expect(formatDate('2026-10-01')).toBe('01 Oct 2026');
    });

    it('adds the weekday on request, like Dates::DAY', () => {
        expect(formatDate('2026-10-01', { weekday: true })).toBe('Thu, 01 Oct 2026');
    });

    it('accepts a full ISO timestamp from a date cast without shifting the day', () => {
        expect(formatDate('2026-10-01T00:00:00.000000Z', { weekday: true })).toBe('Thu, 01 Oct 2026');
    });
});

describe('formatDateTime', () => {
    it('writes an afternoon moment with a 12-hour clock', () => {
        expect(formatDateTime(new Date(2026, 9, 18, 14, 5).toISOString())).toBe('18 Oct, 2:05 PM');
    });

    it('writes midnight as 12 AM', () => {
        expect(formatDateTime(new Date(2026, 9, 3, 0, 30).toISOString())).toBe('03 Oct, 12:30 AM');
    });
});
