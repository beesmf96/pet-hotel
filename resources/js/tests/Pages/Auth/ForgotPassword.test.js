import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'

vi.mock('@/Layouts/AuthLayout.vue', () => ({
    default: { template: '<div><slot name="subtitle" /><slot /></div>' },
}))

const form = { email: '', processing: false, errors: {}, post: vi.fn() }

vi.mock('@inertiajs/vue3', () => ({ useForm: vi.fn(() => form) }))

import ForgotPassword from '@/Pages/Auth/ForgotPassword.vue'

beforeEach(() => {
    vi.clearAllMocks()
    Object.assign(form, { email: '', processing: false, errors: {} })
})

describe('ForgotPassword page', () => {
    it('shows the status banner only when a status is given', () => {
        expect(mount(ForgotPassword).find('.bg-green-50').exists()).toBe(false)
        const w = mount(ForgotPassword, { props: { status: 'Link sent.' } })
        expect(w.text()).toContain('Link sent.')
    })

    it('posts to /forgot-password', async () => {
        const w = mount(ForgotPassword)
        await w.find('input[type="email"]').setValue('jane@example.com')
        await w.find('form').trigger('submit')
        expect(form.email).toBe('jane@example.com')
        expect(form.post).toHaveBeenCalledWith('/forgot-password')
    })

    it('shows the email error', () => {
        form.errors = { email: 'No such account.' }
        expect(mount(ForgotPassword).text()).toContain('No such account.')
    })

    it('changes the button label while processing and links back to login', () => {
        form.processing = true
        const w = mount(ForgotPassword)
        expect(w.find('button[type="submit"]').text()).toBe('Sending...')
        expect(w.find('a[href="/login"]').exists()).toBe(true)
        form.processing = false
        expect(mount(ForgotPassword).find('button[type="submit"]').text()).toBe('Send reset link')
    })
})
