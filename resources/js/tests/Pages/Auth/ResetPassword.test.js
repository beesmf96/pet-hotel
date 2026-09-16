import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'

vi.mock('@/Layouts/AuthLayout.vue', () => ({
    default: { template: '<div><slot name="subtitle" /><slot /></div>' },
}))

const form = { token: '', email: '', password: '', password_confirmation: '', processing: false, errors: {}, post: vi.fn(), reset: vi.fn() }
const useForm = vi.fn((fields) => Object.assign(form, fields))

vi.mock('@inertiajs/vue3', () => ({ useForm: (fields) => useForm(fields) }))

import ResetPassword from '@/Pages/Auth/ResetPassword.vue'

beforeEach(() => {
    vi.clearAllMocks()
    Object.assign(form, { processing: false, errors: {} })
})

describe('ResetPassword page', () => {
    it('seeds the form with the token and email from the props', () => {
        mount(ResetPassword, { props: { token: 'abc123', email: 'jane@example.com' } })
        expect(useForm).toHaveBeenCalledWith(
            expect.objectContaining({ token: 'abc123', email: 'jane@example.com' }),
        )
    })

    it('posts to /reset-password and clears both password fields when finished', async () => {
        const w = mount(ResetPassword, { props: { token: 'abc123' } })
        await w.find('form').trigger('submit')
        expect(form.post).toHaveBeenCalledWith('/reset-password', expect.any(Object))
        form.post.mock.calls[0][1].onFinish()
        expect(form.reset).toHaveBeenCalledWith('password', 'password_confirmation')
    })

    it('shows field errors', () => {
        form.errors = { email: 'Unknown email.', password: 'Too short.' }
        const w = mount(ResetPassword, { props: { token: 'abc123' } })
        for (const msg of Object.values(form.errors)) expect(w.text()).toContain(msg)
    })

    it('changes the button label while processing', () => {
        form.processing = true
        expect(mount(ResetPassword, { props: { token: 't' } }).find('button[type="submit"]').text()).toBe('Resetting...')
        form.processing = false
        expect(mount(ResetPassword, { props: { token: 't' } }).find('button[type="submit"]').text()).toBe('Reset password')
    })
})
