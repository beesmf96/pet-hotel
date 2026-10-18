import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import PageSubtitle from '@/Components/Ui/PageSubtitle.vue';

describe('PageSubtitle', () => {
    it('renders its slot as quiet body text', () => {
        const w = mount(PageSubtitle, { slots: { default: '12 Jalan Anggerik, Shah Alam' } });
        expect(w.text()).toBe('12 Jalan Anggerik, Shah Alam');
        expect(w.classes()).toEqual(expect.arrayContaining(['font-sans', 'text-sm', 'text-moss']));
    });
});
