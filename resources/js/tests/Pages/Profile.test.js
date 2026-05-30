import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}))

const formState = {
    name: 'Jane',
    phone: '',
    preferred_location: '',
    processing: false,
    recentlySuccessful: false,
    errors: {},
    patch: vi.fn(),
}

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn(() => formState),
}))

import ProfilePage from '@/Pages/Profile.vue'

const user = { name: 'Jane', email: 'jane@example.com', phone: '', preferred_location: '' }

describe('Profile page — Saved! message', () => {
    it('shows Saved! when form.recentlySuccessful is true', async () => {
        formState.recentlySuccessful = true
        const w = mount(ProfilePage, { props: { user } })
        expect(w.text()).toContain('Saved!')
    })

    it('hides Saved! when form.recentlySuccessful is false', () => {
        formState.recentlySuccessful = false
        const w = mount(ProfilePage, { props: { user } })
        expect(w.text()).not.toContain('Saved!')
    })
})

describe('Profile page — email field', () => {
    it('renders email input as disabled with user.email value', () => {
        formState.recentlySuccessful = false
        const w = mount(ProfilePage, { props: { user } })
        const emailInput = w.find('input[type="email"]')
        expect(emailInput.attributes('disabled')).toBeDefined()
        expect(emailInput.element.value).toBe('jane@example.com')
    })
})
