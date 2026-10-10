import { describe, it, expect } from 'vitest';
import { CURRENCY_SYMBOL, formatMoney, toSen } from '@/money';

describe('money', () => {
    it('prints ringgit with two decimals and thousands', () => {
        expect(CURRENCY_SYMBOL).toBe('RM');
        expect(formatMoney('1234.5')).toBe('RM 1,234.50');
        expect(formatMoney(null)).toBe('RM 0.00');
    });

    it('can drop the decimals for "from" prices', () => {
        expect(formatMoney(45.2, { decimals: 0 })).toBe('RM 45');
    });

    it('converts to whole sen without float drift', () => {
        expect(toSen('0.10')).toBe(10);
        expect((toSen('0.10') * 3) / 100).toBe(0.3);
        expect(toSen('45.50')).toBe(4550);
    });
});
