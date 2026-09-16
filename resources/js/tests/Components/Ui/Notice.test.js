import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

import Notice from '@/Components/Ui/Notice.vue';

describe('Notice', () => {
    it('renders its content in the info tone by default', () => {
        const w = mount(Notice, { slots: { default: 'Saved.' } });
        expect(w.text()).toBe('Saved.');
        expect(w.classes()).toContain('bg-teal-light');
    });

    it('uses the warning tone when asked', () => {
        expect(mount(Notice, { props: { tone: 'warning' } }).classes()).toContain('bg-mustard');
    });

    it('falls back to info for an unknown tone', () => {
        expect(mount(Notice, { props: { tone: 'nope' } }).classes()).toContain('bg-teal-light');
    });
});
