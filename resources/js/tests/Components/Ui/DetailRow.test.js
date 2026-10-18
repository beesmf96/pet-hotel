import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import DetailRow from '@/Components/Ui/DetailRow.vue';

describe('DetailRow', () => {
    it('pairs the label with the slotted value', () => {
        const w = mount(DetailRow, { props: { label: 'Pet' }, slots: { default: 'Milo (Dog)' } });
        expect(w.find('dt').text()).toBe('Pet');
        expect(w.find('dd').text()).toBe('Milo (Dog)');
    });

    it('keeps the label on one line and the value on the right', () => {
        const w = mount(DetailRow, { props: { label: 'Check-in' } });
        expect(w.find('dt').classes()).toContain('whitespace-nowrap');
        expect(w.find('dd').classes()).toContain('text-right');
    });
});
