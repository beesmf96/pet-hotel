import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

import TextInput from '@/Components/Ui/TextInput.vue';
import FormField from '@/Components/Ui/FormField.vue';

describe('TextInput', () => {
    it('renders an input by default and syncs v-model', async () => {
        const w = mount(TextInput, {
            props: { modelValue: 'a', 'onUpdate:modelValue': (v) => w.setProps({ modelValue: v }) },
            attrs: { type: 'text' },
        });
        expect(w.element.tagName).toBe('INPUT');
        expect(w.attributes('type')).toBe('text');
        await w.find('input').setValue('bedok');
        expect(w.props('modelValue')).toBe('bedok');
    });

    it('renders a textarea with as="textarea"', async () => {
        const w = mount(TextInput, {
            props: { as: 'textarea', modelValue: '', 'onUpdate:modelValue': (v) => w.setProps({ modelValue: v }) },
        });
        expect(w.element.tagName).toBe('TEXTAREA');
        await w.find('textarea').setValue('hi');
        expect(w.props('modelValue')).toBe('hi');
    });

    it('renders a select with its options with as="select"', async () => {
        const w = mount(TextInput, {
            props: { as: 'select', modelValue: 'a', 'onUpdate:modelValue': (v) => w.setProps({ modelValue: v }) },
            slots: { default: '<option value="a">A</option><option value="b">B</option>' },
        });
        expect(w.element.tagName).toBe('SELECT');
        await w.find('select').setValue('b');
        expect(w.props('modelValue')).toBe('b');
    });

    it('shows a preset value and passes disabled through', () => {
        const w = mount(TextInput, {
            props: { modelValue: 'jane@example.com' },
            attrs: { disabled: true, type: 'email' },
        });
        expect(w.find('input').element.value).toBe('jane@example.com');
        expect(w.attributes('disabled')).toBeDefined();
    });

    it('is full width by default and shrinks with full=false', () => {
        expect(mount(TextInput).classes()).toContain('w-full');
        expect(mount(TextInput, { props: { full: false } }).classes()).not.toContain('w-full');
    });

    it('adds no id or aria attributes outside a FormField', () => {
        const w = mount(TextInput);
        expect(w.attributes('id')).toBeUndefined();
        expect(w.attributes('aria-describedby')).toBeUndefined();
        expect(w.attributes('aria-invalid')).toBeUndefined();
    });

    it('keeps an id passed in directly, even inside a FormField', () => {
        const w = mount({
            components: { FormField, TextInput },
            template: '<FormField label="Email"><TextInput id="own-id" /></FormField>',
        });
        expect(w.find('input').attributes('id')).toBe('own-id');
    });
});
