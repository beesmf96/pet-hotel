import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'

const pageProps = { auth: { user: null }, unread_notifications_count: 0 }

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: pageProps }),
    router: { post: vi.fn() },
}))
vi.mock('@/Components/NotificationBell.vue', () => ({
    default: { props: ['unreadCount'], template: '<div data-testid="bell" :data-count="unreadCount" />' },
}))

import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

beforeEach(() => {
    vi.clearAllMocks()
    pageProps.auth = { user: null }
    pageProps.unread_notifications_count = 0
})

const links = (w) => w.findAll('a').map((a) => a.attributes('href'))

describe('AppLayout — guest', () => {
    it('shows Sign in and Register, and no account links', () => {
        const w = mount(AppLayout)
        expect(links(w)).toEqual(expect.arrayContaining(['/', '/hotels', '/login', '/register']))
        expect(links(w)).not.toContain('/pets')
        expect(w.find('[data-testid="bell"]').exists()).toBe(false)
    })
})

describe('AppLayout — signed in', () => {
    beforeEach(() => {
        pageProps.auth = { user: { name: 'Jane' } }
    })

    it('shows the account links and the bell, and hides Sign in', () => {
        pageProps.unread_notifications_count = 4
        const w = mount(AppLayout)
        expect(links(w)).toEqual(expect.arrayContaining(['/pets', '/bookings', '/profile']))
        expect(links(w)).not.toContain('/login')
        expect(w.find('[data-testid="bell"]').attributes('data-count')).toBe('4')
    })

    it('treats a missing unread count as zero', () => {
        delete pageProps.unread_notifications_count
        const w = mount(AppLayout)
        expect(w.find('[data-testid="bell"]').attributes('data-count')).toBe('0')
    })

    it('Sign out posts to /logout', async () => {
        const w = mount(AppLayout)
        await w.findAll('button').find((b) => b.text() === 'Sign out').trigger('click')
        expect(router.post).toHaveBeenCalledWith('/logout')
    })
})

describe('AppLayout — slots', () => {
    it('renders the header bar only when a header slot is given', () => {
        expect(mount(AppLayout).find('header').exists()).toBe(false)
        const w = mount(AppLayout, { slots: { header: '<h1>Title</h1>' } })
        expect(w.find('header').text()).toBe('Title')
    })

    it('renders the default and nav slots', () => {
        const w = mount(AppLayout, {
            slots: { default: '<p data-testid="main" />', nav: '<a href="/extra">Extra</a>' },
        })
        expect(w.find('main [data-testid="main"]').exists()).toBe(true)
        expect(links(w)).toContain('/extra')
    })
})
