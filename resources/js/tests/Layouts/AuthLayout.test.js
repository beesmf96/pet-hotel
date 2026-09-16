import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

import AuthLayout from '@/Layouts/AuthLayout.vue';

describe('AuthLayout', () => {
    it('renders the brand link back to the landing page', () => {
        const w = mount(AuthLayout);
        const brand = w.find('a[href="/"]');
        expect(brand.text()).toBe('PetHotel');
    });

    it('renders the default and subtitle slots', () => {
        const w = mount(AuthLayout, {
            slots: { default: '<form data-testid="body" />', subtitle: 'Sign in here' },
        });
        expect(w.find('[data-testid="body"]').exists()).toBe(true);
        expect(w.text()).toContain('Sign in here');
    });
});
