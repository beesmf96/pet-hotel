import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'

vi.mock('@/Layouts/AuthLayout.vue', () => ({
    default: { template: '<div><slot name="subtitle" /><slot /></div>' },
}))

// The page calls useForm twice with empty objects: first for resend, then for logout.
const resendForm = { processing: false, post: vi.fn() }
const logoutForm = { processing: false, post: vi.fn() }
let calls = 0

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn(() => (calls++ % 2 === 0 ? resendForm : logoutForm)),
}))

import VerifyEmail from '@/Pages/Auth/VerifyEmail.vue'

beforeEach(() => {
    vi.clearAllMocks()
    calls = 0
    resendForm.processing = false
    logoutForm.processing = false
})

const button = (w, label) => w.findAll('button').find((b) => b.text() === label)

describe('VerifyEmail page', () => {
    it('shows the "sent" banner only for the verification-link-sent status', () => {
        expect(mount(VerifyEmail).text()).not.toContain('A new verification link')
        expect(mount(VerifyEmail, { props: { status: 'other' } }).text()).not.toContain('A new verification link')
        expect(mount(VerifyEmail, { props: { status: 'verification-link-sent' } }).text()).toContain(
            'A new verification link has been sent',
        )
    })

    it('resend posts to the verification endpoint', async () => {
        const w = mount(VerifyEmail)
        await button(w, 'Resend verification email').trigger('click')
        expect(resendForm.post).toHaveBeenCalledWith('/email/verification-notification')
        expect(logoutForm.post).not.toHaveBeenCalled()
    })

    it('sign out posts to /logout', async () => {
        const w = mount(VerifyEmail)
        await button(w, 'Sign out').trigger('click')
        expect(logoutForm.post).toHaveBeenCalledWith('/logout')
        expect(resendForm.post).not.toHaveBeenCalled()
    })

    it('shows Sending... and disables both buttons while their form is processing', () => {
        resendForm.processing = true
        logoutForm.processing = true
        const w = mount(VerifyEmail)
        const sending = button(w, 'Sending...')
        expect(sending.attributes('disabled')).toBeDefined()
        expect(button(w, 'Sign out').attributes('disabled')).toBeDefined()
    })
})
