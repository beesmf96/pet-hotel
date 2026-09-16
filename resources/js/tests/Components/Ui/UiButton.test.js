import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href" data-testid="inertia-link"><slot /></a>' },
}));

import UiButton from '@/Components/Ui/UiButton.vue';

describe('UiButton — element', () => {
    it('renders a button by default', () => {
        const w = mount(UiButton, { slots: { default: 'Go' } });
        expect(w.element.tagName).toBe('BUTTON');
        expect(w.text()).toBe('Go');
    });

    it('renders a plain anchor with as="a"', () => {
        const w = mount(UiButton, { props: { as: 'a' }, attrs: { href: '/hotels' } });
        expect(w.element.tagName).toBe('A');
        expect(w.attributes('href')).toBe('/hotels');
    });

    it('renders an Inertia Link with as="link"', () => {
        const w = mount(UiButton, { props: { as: 'link' }, attrs: { href: '/bookings' } });
        expect(w.find('[data-testid="inertia-link"]').exists()).toBe(true);
    });
});

describe('UiButton — variants and layout', () => {
    it('uses the teal variant by default', () => {
        expect(mount(UiButton).classes()).toContain('bg-teal');
    });

    it('applies the requested variant', () => {
        expect(mount(UiButton, { props: { variant: 'mustard' } }).classes()).toContain('bg-mustard');
    });

    it('falls back to teal for an unknown variant', () => {
        expect(mount(UiButton, { props: { variant: 'nope' } }).classes()).toContain('bg-teal');
    });

    it('stretches full width with block', () => {
        expect(mount(UiButton, { props: { block: true } }).classes()).toContain('w-full');
        expect(mount(UiButton).classes()).not.toContain('w-full');
    });

    it('passes disabled through to the element', () => {
        const w = mount(UiButton, { attrs: { disabled: true } });
        expect(w.attributes('disabled')).toBeDefined();
    });
});
