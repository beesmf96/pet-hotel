import { describe, it, expect } from 'vitest';
import { FACILITIES, facilityLabel } from '@/facilities';

describe('facilityLabel', () => {
    it('gives the sentence-case label for a known facility', () => {
        expect(facilityLabel('outdoor_walks')).toBe('Outdoor walks');
        expect(facilityLabel('24h_care')).toBe('24h care');
    });

    it('falls back to the raw value for an unknown one', () => {
        expect(facilityLabel('helipad')).toBe('helipad');
    });

    it('uses sentence case for every label', () => {
        for (const { label } of FACILITIES) {
            expect(label.slice(1)).toBe(label.slice(1).toLowerCase());
        }
    });
});
