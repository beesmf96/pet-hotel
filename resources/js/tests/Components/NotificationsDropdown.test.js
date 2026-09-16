import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({ router: { visit: vi.fn() } }))

import { router } from '@inertiajs/vue3'
import NotificationsDropdown from '@/Components/NotificationsDropdown.vue'

const items = [
    { id: 1, type: 'booking_confirmed', message: 'Booking confirmed', url: '/bookings/1', read_at: null, created_at: '2026-09-10T08:00:00Z' },
    { id: 2, type: 'booking_cancelled', message: 'Booking cancelled', url: null, read_at: '2026-09-09T08:00:00Z', created_at: '2026-09-09T08:00:00Z' },
    { id: 3, type: 'something_else', message: 'Misc', url: null, read_at: null, created_at: '2026-09-08T08:00:00Z' },
]

const jsonResponse = (data, ok = true) => Promise.resolve({ ok, json: () => Promise.resolve(data) })

let meta

beforeEach(() => {
    vi.clearAllMocks()
    meta = document.createElement('meta')
    meta.name = 'csrf-token'
    meta.content = 'tok123'
    document.head.appendChild(meta)
    global.fetch = vi.fn(() => jsonResponse(structuredClone(items)))
})

afterEach(() => meta.remove())

const mountLoaded = async () => {
    const w = mount(NotificationsDropdown, { props: { unreadCount: 2 } })
    await flushPromises()
    return w
}

describe('NotificationsDropdown — loading', () => {
    it('shows Loading… then fetches the list as JSON', async () => {
        const w = mount(NotificationsDropdown)
        expect(w.text()).toContain('Loading…')
        await flushPromises()
        expect(fetch).toHaveBeenCalledWith('/notifications', expect.objectContaining({
            headers: expect.objectContaining({ Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }),
        }))
        expect(w.text()).not.toContain('Loading…')
        expect(w.findAll('li')).toHaveLength(3)
    })

    it('shows the empty state when there are no notifications', async () => {
        fetch.mockImplementation(() => jsonResponse([]))
        const w = await mountLoaded()
        expect(w.text()).toContain('No notifications yet.')
        expect(w.text()).not.toContain('Mark all read')
    })

    it('stays empty when the request fails', async () => {
        fetch.mockImplementation(() => jsonResponse(null, false))
        const w = await mountLoaded()
        expect(w.text()).toContain('No notifications yet.')
    })
})

describe('NotificationsDropdown — rendering', () => {
    it('uses the type icon, falling back to a bell', async () => {
        const w = await mountLoaded()
        const icons = w.findAll('li > span:first-child').map((s) => s.attributes('data-glyph'))
        expect(icons).toEqual(['check', 'cross', 'bell'])
    })

    it('highlights unread items and shows their dot', async () => {
        const w = await mountLoaded()
        const rows = w.findAll('li')
        expect(rows[0].classes()).toContain('bg-mustard/30')
        expect(rows[0].find('span.unread-dot').exists()).toBe(true)
        expect(rows[1].classes()).not.toContain('bg-mustard/30')
        expect(rows[1].find('span.unread-dot').exists()).toBe(false)
    })
})

describe('NotificationsDropdown — mark read', () => {
    it('PATCHes the item with the CSRF token, marks it read, closes, and visits its url', async () => {
        const w = await mountLoaded()
        await w.findAll('li')[0].trigger('click')
        await flushPromises()
        expect(fetch).toHaveBeenCalledWith('/notifications/1/read', expect.objectContaining({
            method: 'PATCH',
            headers: expect.objectContaining({ 'X-CSRF-TOKEN': 'tok123' }),
        }))
        expect(w.findAll('li')[0].classes()).not.toContain('bg-mustard/30')
        expect(w.emitted('closed')).toHaveLength(1)
        expect(router.visit).toHaveBeenCalledWith('/bookings/1')
    })

    it('does not navigate or close for an item without a url', async () => {
        const w = await mountLoaded()
        await w.findAll('li')[2].trigger('click')
        await flushPromises()
        expect(fetch).toHaveBeenCalledWith('/notifications/3/read', expect.anything())
        expect(w.emitted('closed')).toBeUndefined()
        expect(router.visit).not.toHaveBeenCalled()
    })

    it('Mark all read POSTs once and clears every highlight', async () => {
        const w = await mountLoaded()
        await w.findAll('button').find((b) => b.text() === 'Mark all read').trigger('click')
        await flushPromises()
        expect(fetch).toHaveBeenCalledWith('/notifications/read-all', expect.objectContaining({ method: 'POST' }))
        expect(w.findAll('li.bg-indigo-50')).toHaveLength(0)
        expect(w.text()).not.toContain('Mark all read')
    })
})
