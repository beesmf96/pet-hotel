import { describe, it, expect } from 'vitest';
import { stayQuery } from '@/stay';

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
