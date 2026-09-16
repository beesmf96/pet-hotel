import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'

vi.mock('@/Layouts/AuthLayout.vue', () => ({
    default: { template: '<div><slot name="subtitle" /><slot /></div>' },
}))

const form = {
    name: '', email: '', password: '', password_confirmation: '',
    processing: false, errors: {}, post: vi.fn(), reset: vi.fn(),
}

vi.mock('@inertiajs/vue3', () => ({ useForm: vi.fn(() => form) }))

import Register from '@/Pages/Auth/Register.vue'

beforeEach(() => {
    vi.clearAllMocks()
    Object.assign(form, { processing: false, errors: {} })
})

describe('Register page', () => {
    it('posts to /register and clears both password fields when finished', async () => {
        const w = mount(Register)
        await w.find('form').trigger('submit')
        expect(form.post).toHaveBeenCalledWith('/register', expect.any(Object))
        form.post.mock.calls[0][1].onFinish()
        expect(form.reset).toHaveBeenCalledWith('password', 'password_confirmation')
    })

    it('renders one input per form field', () => {
        const w = mount(Register)
        expect(w.find('input[type="text"]').exists()).toBe(true)
        expect(w.find('input[type="email"]').exists()).toBe(true)
        expect(w.findAll('input[type="password"]')).toHaveLength(2)
    })

    it('shows field errors', () => {
        form.errors = {
            name: 'Name is required.', email: 'Email taken.',
            password: 'Too short.',
        }
        const w = mount(Register)
        for (const msg of Object.values(form.errors)) expect(w.text()).toContain(msg)
    })

    it('changes the button label while processing', () => {
        form.processing = true
        expect(mount(Register).find('button[type="submit"]').text()).toBe('Creating account...')
        form.processing = false
        expect(mount(Register).find('button[type="submit"]').text()).toBe('Create account')
    })
})
