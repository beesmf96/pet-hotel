import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

// Stub child components that are not under test
vi.mock('@/Components/Hotels/SearchBar.vue', () => ({
    default: { template: '<div data-testid="search-bar" />' },
}))
vi.mock('@/Components/Hotels/HotelCard.vue', () => ({
    default: { template: '<div data-testid="hotel-card" />' },
}))

// Stub Inertia — usePage and useForm are called at module level in Landing.vue
const mockLogoutPost = vi.fn()
vi.mock('@inertiajs/vue3', () => ({
    router: { get: vi.fn() },
    usePage: vi.fn(),
    useForm: vi.fn(() => ({
        post: mockLogoutPost,
        processing: false,
    })),
}))

import { usePage } from '@inertiajs/vue3'
import Landing from '@/Pages/Landing.vue'

// Helper to mount Landing with a given auth.user value
function mountLanding(user = null) {
    usePage.mockReturnValue({ props: { auth: { user } } })
    return mount(Landing, {
        props: { featuredHotels: [] },
    })
}

describe('Landing — guest state (unauthenticated)', () => {
    it('shows Sign in link in nav', () => {
        const w = mountLanding(null)
        const links = w.findAll('a[href="/login"]')
        expect(links.length).toBeGreaterThan(0)
    })

    it('shows Register link in nav', () => {
        const w = mountLanding(null)
        const links = w.findAll('a[href="/register"]')
        expect(links.length).toBeGreaterThan(0)
    })

    it('does not show Sign out button in nav', () => {
        const w = mountLanding(null)
        // The Sign out / Sign Out buttons only render when user is truthy
        const buttons = w.findAll('button')
        const signOutButtons = buttons.filter(b =>
            b.text().toLowerCase().includes('sign out')
        )
        expect(signOutButtons).toHaveLength(0)
    })

    it('does not show Dashboard link', () => {
        const w = mountLanding(null)
        const links = w.findAll('a[href="/dashboard"]')
        expect(links).toHaveLength(0)
    })

    it('does not show user name', () => {
        const w = mountLanding(null)
        expect(w.text()).not.toContain('Jane')
    })
})

describe('Landing — authenticated state', () => {
    const authUser = { id: 1, name: 'Jane', email: 'jane@example.com' }

    it('shows user name in nav', () => {
        const w = mountLanding(authUser)
        expect(w.text()).toContain('Jane')
    })

    it('shows Dashboard link in nav', () => {
        const w = mountLanding(authUser)
        const links = w.findAll('a[href="/dashboard"]')
        expect(links.length).toBeGreaterThan(0)
    })

    it('shows Sign out button in nav', () => {
        const w = mountLanding(authUser)
        const buttons = w.findAll('button')
        const signOutButtons = buttons.filter(b =>
            b.text().toLowerCase().includes('sign out')
        )
        expect(signOutButtons.length).toBeGreaterThan(0)
    })

    it('does not show Sign in link', () => {
        const w = mountLanding(authUser)
        const links = w.findAll('a[href="/login"]')
        expect(links).toHaveLength(0)
    })

    it('does not show Register link', () => {
        const w = mountLanding(authUser)
        const links = w.findAll('a[href="/register"]')
        expect(links).toHaveLength(0)
    })

    it('Sign out button calls logoutForm.post with /logout', async () => {
        mockLogoutPost.mockClear()
        const w = mountLanding(authUser)
        const buttons = w.findAll('button')
        const signOutBtn = buttons.find(b =>
            b.text().toLowerCase().includes('sign out')
        )
        await signOutBtn.trigger('click')
        expect(mockLogoutPost).toHaveBeenCalledWith('/logout')
    })
})
