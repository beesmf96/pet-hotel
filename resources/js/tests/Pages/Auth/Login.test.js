import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'

vi.mock('@/Layouts/AuthLayout.vue', () => ({
    default: { template: '<div><slot name="subtitle" /><slot /></div>' },
}))

const form = { email: '', password: '', remember: false, processing: false, errors: {}, post: vi.fn(), reset: vi.fn() }

vi.mock('@inertiajs/vue3', () => ({ useForm: vi.fn(() => form) }))

import Login from '@/Pages/Auth/Login.vue'

beforeEach(() => {
    vi.clearAllMocks()
    Object.assign(form, { email: '', password: '', remember: false, processing: false, errors: {} })
})

describe('Login page', () => {
    it('shows the status banner only when a status is given', () => {
        expect(mount(Login).text()).not.toContain('bg-green-50')
        const w = mount(Login, { props: { status: 'Password reset.' } })
        expect(w.text()).toContain('Password reset.')
    })

    it('posts to /login and clears the password when the request finishes', async () => {
        const w = mount(Login)
        await w.find('form').trigger('submit')
        expect(form.post).toHaveBeenCalledWith('/login', expect.any(Object))
        form.post.mock.calls[0][1].onFinish()
        expect(form.reset).toHaveBeenCalledWith('password')
    })

    it('binds email, password, and remember to the form', async () => {
        const w = mount(Login)
        await w.find('input[type="email"]').setValue('jane@example.com')
        await w.find('input[type="password"]').setValue('secret')
        await w.find('#remember').setValue(true)
        expect(form.email).toBe('jane@example.com')
        expect(form.password).toBe('secret')
        expect(form.remember).toBe(true)
    })

    it('shows field errors', () => {
        form.errors = { email: 'Email is wrong.', password: 'Password is wrong.' }
        const w = mount(Login)
        expect(w.text()).toContain('Email is wrong.')
        expect(w.text()).toContain('Password is wrong.')
    })

    it('disables the button and changes its label while processing', () => {
        form.processing = true
        const w = mount(Login)
        const btn = w.find('button[type="submit"]')
        expect(btn.attributes('disabled')).toBeDefined()
        expect(btn.text()).toBe('Signing in...')
        form.processing = false
        expect(mount(Login).find('button[type="submit"]').text()).toBe('Sign in')
    })

    // A plain anchor on purpose: an Inertia Link will not follow the 302 to Google.
    it('links to Google OAuth with a plain anchor, plus register and forgot-password', () => {
        const w = mount(Login)
        const hrefs = w.findAll('a').map((a) => a.attributes('href'))
        expect(hrefs).toEqual(expect.arrayContaining(['/auth/google', '/register', '/forgot-password']))
    })
})
