import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot name="header" /><slot /></div>' },
}))
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { auth: { user: { name: 'Jane', email: 'jane@example.com' } } } }),
}))

import Dashboard from '@/Pages/Dashboard.vue'

describe('Dashboard', () => {
    it('greets the signed-in user by name and email', () => {
        const w = mount(Dashboard)
        expect(w.text()).toContain('Dashboard')
        expect(w.text()).toContain('Welcome back, Jane!')
        expect(w.text()).toContain("You're logged in as jane@example.com.")
    })
})
