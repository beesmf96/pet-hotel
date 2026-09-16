import { describe, it, expect } from 'vitest';

import { useFormatDate } from '@/composables/useFormatDate.js';

const { formatDate } = useFormatDate();

describe('useFormatDate', () => {
    it('formats a plain date string', () => {
        expect(formatDate('2026-10-01')).toBe('Oct 1, 2026');
    });

    it('adds the weekday on request', () => {
        expect(formatDate('2026-10-01', { weekday: true })).toBe('Thu, Oct 1, 2026');
    });

    it('accepts a full ISO timestamp from a date cast without shifting the day', () => {
        expect(formatDate('2026-10-01T00:00:00.000000Z', { weekday: true })).toBe('Thu, Oct 1, 2026');
    });
});
