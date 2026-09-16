import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

import StatusPill from '@/Components/Ui/StatusPill.vue';

describe('StatusPill', () => {
    it.each([
        ['pending', 'Pending', 'bg-mustard'],
        ['confirmed', 'Confirmed', 'bg-teal'],
        ['completed', 'Completed', 'bg-teal-light'],
        ['cancelled', 'Cancelled', 'bg-white'],
    ])('renders %s as "%s"', (status, label, cls) => {
        const w = mount(StatusPill, { props: { status } });
        expect(w.text()).toBe(label);
        expect(w.classes()).toContain(cls);
    });

    it('shows an unknown status verbatim in the neutral colours', () => {
        const w = mount(StatusPill, { props: { status: 'archived' } });
        expect(w.text()).toBe('archived');
        expect(w.classes()).toContain('bg-white');
    });
});
