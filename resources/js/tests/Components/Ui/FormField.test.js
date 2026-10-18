import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

import FormField from '@/Components/Ui/FormField.vue';
import TextInput from '@/Components/Ui/TextInput.vue';

// A FormField with a TextInput inside, as the pages use it.
const field = (props = {}) =>
    mount({
        components: { FormField, TextInput },
        data: () => ({ props }),
        template: '<FormField v-bind="props"><TextInput /></FormField>',
    });

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

    it('ties the label to the TextInput inside it', () => {
        const w = field({ label: 'Email' });
        const id = w.find('input').attributes('id');
        expect(id).toBeTruthy();
        expect(w.find('label').attributes('for')).toBe(id);
    });

    it('gives each field its own id', () => {
        const w = mount({
            components: { FormField, TextInput },
            template:
                '<div><FormField label="A"><TextInput /></FormField><FormField label="B"><TextInput /></FormField></div>',
        });
        const [a, b] = w.findAll('input').map((i) => i.attributes('id'));
        expect(a).not.toBe(b);
    });

    it('describes the control by its error, and marks it invalid, only when there is one', () => {
        const withError = field({ label: 'Email', error: 'Required.' });
        const input = withError.find('input');
        expect(input.attributes('aria-describedby')).toBe(withError.find('p').attributes('id'));
        expect(input.attributes('aria-invalid')).toBe('true');

        const clean = field({ label: 'Email' }).find('input');
        expect(clean.attributes('aria-describedby')).toBeUndefined();
        expect(clean.attributes('aria-invalid')).toBeUndefined();
    });

    it('hands the id and the label id to a control that is not a TextInput', () => {
        const w = mount(FormField, {
            props: { label: 'Rating' },
            slots: { default: '<div data-testid="slot" :data-id="params.id" :data-label-id="params.labelId" />' },
        });
        const slot = w.find('[data-testid="slot"]');
        expect(slot.attributes('data-id')).toBeTruthy();
        expect(slot.attributes('data-label-id')).toBeTruthy();
        expect(slot.attributes('data-id')).toBe(w.find('label').attributes('for'));
        expect(slot.attributes('data-label-id')).toBe(w.find('label').attributes('id'));
    });
});
