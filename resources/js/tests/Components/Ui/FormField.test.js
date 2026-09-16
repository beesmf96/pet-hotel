import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

import FormField from '@/Components/Ui/FormField.vue';

describe('FormField', () => {
    it('renders the label and the control slot', () => {
        const w = mount(FormField, { props: { label: 'Email' }, slots: { default: '<input type="email" />' } });
        expect(w.find('label').text()).toBe('Email');
        expect(w.find('input[type="email"]').exists()).toBe(true);
    });

    it('shows the error only when one is set', () => {
        const w = mount(FormField, { props: { label: 'Email', error: 'Required.' } });
        expect(w.text()).toContain('Required.');
        expect(
            mount(FormField, { props: { label: 'Email' } })
                .find('p')
                .exists(),
        ).toBe(false);
    });

    it('renders the aside and label-suffix slots', () => {
        const w = mount(FormField, {
            props: { label: 'Password' },
            slots: { aside: '<a href="/forgot">Forgot?</a>', 'label-suffix': '<span>(optional)</span>' },
        });
        expect(w.find('a[href="/forgot"]').exists()).toBe(true);
        expect(w.find('label').text()).toContain('(optional)');
    });

    it('renders no label row when there is neither a label nor an aside', () => {
        const w = mount(FormField, { slots: { default: '<input />' } });
        expect(w.find('label').exists()).toBe(false);
    });
});
