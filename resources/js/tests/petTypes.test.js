import { describe, it, expect } from 'vitest';
import { PET_TYPES, petTypeLabel } from '@/petTypes';

describe('petTypes', () => {
    it('lists the same keys as App\\Enums\\PetType', () => {
        expect(PET_TYPES.map((t) => t.value)).toEqual(['dog', 'cat', 'rabbit', 'bird', 'other']);
    });

    it('labels a known key', () => {
        expect(petTypeLabel('rabbit')).toBe('Rabbit');
    });

    it('falls back to the raw value for an unknown key', () => {
        expect(petTypeLabel('hamster')).toBe('hamster');
    });
});
