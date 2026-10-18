import { describe, it, expect } from 'vitest';
import { nightsBetween, nightsLabel, stayQuery } from '@/stay';

describe('stayQuery', () => {
    it('is empty without a stay', () => {
        expect(stayQuery(null)).toBe('');
    });

    it('carries both dates', () => {
        expect(stayQuery({ check_in: '2026-10-25', check_out: '2026-10-28', nights: 3 })).toBe(
            '?check_in=2026-10-25&check_out=2026-10-28',
        );
    });
});

describe('nightsBetween', () => {
    it('counts the nights of a stay', () => {
        expect(nightsBetween('2026-10-25', '2026-10-28')).toBe(3);
    });

    it('is zero for the same day and negative when the dates are swapped', () => {
        expect(nightsBetween('2026-10-25', '2026-10-25')).toBe(0);
        expect(nightsBetween('2026-10-28', '2026-10-25')).toBe(-3);
    });
});

describe('nightsLabel', () => {
    it('says "night" for one', () => {
        expect(nightsLabel(1)).toBe('1 night');
    });

    it('says "nights" for more', () => {
        expect(nightsLabel(3)).toBe('3 nights');
    });
});
