import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

import EmptyState from '@/Components/Ui/EmptyState.vue';

describe('EmptyState', () => {
    it('renders the title and message', () => {
        const w = mount(EmptyState, { props: { title: 'Nothing here', message: 'Try again later.' } });
        expect(w.find('h3').text()).toBe('Nothing here');
        expect(w.text()).toContain('Try again later.');
        expect(w.find('svg').exists()).toBe(true);
    });

    it('omits the title when none is given', () => {
        const w = mount(EmptyState, { props: { message: 'Empty.' } });
        expect(w.find('h3').exists()).toBe(false);
    });

    it('renders an action slot when provided', () => {
        const w = mount(EmptyState, { slots: { default: '<a href="/x">Do it</a>' } });
        expect(w.find('a[href="/x"]').exists()).toBe(true);
    });

    it('renders no action wrapper without a slot', () => {
        const w = mount(EmptyState, { props: { message: 'Empty.' } });
        expect(w.findAll('div.mt-1')).toHaveLength(0);
    });
});
