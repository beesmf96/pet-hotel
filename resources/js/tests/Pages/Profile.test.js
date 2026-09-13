import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'

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

const passwordFormState = {
    current_password: '',
    password: '',
    password_confirmation: '',
    processing: false,
    recentlySuccessful: false,
    errors: {},
    put: vi.fn(),
    reset: vi.fn(),
}

// The page calls useForm twice; hand back the right state for each by looking at
// the fields it was initialised with.
vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((fields) => ('password' in fields ? passwordFormState : formState)),
}))

import ProfilePage from '@/Pages/Profile.vue'

const user = { name: 'Jane', email: 'jane@example.com', phone: '', preferred_location: '' }

beforeEach(() => {
    formState.recentlySuccessful = false
    formState.errors = {}
    passwordFormState.recentlySuccessful = false
    passwordFormState.errors = {}
})

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

describe('Profile page — password card', () => {
    it('asks for the current password when the user has one', () => {
        const w = mount(ProfilePage, { props: { user, hasPassword: true } })
        expect(w.text()).toContain('Change Password')
        expect(w.findAll('input[type="password"]')).toHaveLength(3)
        expect(w.find('input[autocomplete="current-password"]').exists()).toBe(true)
    })

    it('omits the current password field for an OAuth-only account', () => {
        const w = mount(ProfilePage, { props: { user, hasPassword: false } })
        expect(w.text()).toContain('Set a Password')
        expect(w.findAll('input[type="password"]')).toHaveLength(2)
        expect(w.find('input[autocomplete="current-password"]').exists()).toBe(false)
    })

    it('explains why an OAuth-only account is being offered a password', () => {
        const w = mount(ProfilePage, { props: { user, hasPassword: false } })
        expect(w.text()).toContain('You signed in with Google')
    })

    it('submits the password form to /profile/password', async () => {
        const w = mount(ProfilePage, { props: { user, hasPassword: true } })
        await w.findAll('form')[1].trigger('submit')
        expect(passwordFormState.put).toHaveBeenCalledWith(
            '/profile/password',
            expect.any(Object),
        )
    })

    it('renders a validation error against the current password field', () => {
        passwordFormState.errors = { current_password: 'The current password is incorrect.' }
        const w = mount(ProfilePage, { props: { user, hasPassword: true } })
        expect(w.text()).toContain('The current password is incorrect.')
    })
})
