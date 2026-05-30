import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}))
vi.mock('@/Components/Hotels/HotelCard.vue', () => ({
    default: { template: '<div data-testid="hotel-card" />' },
}))
vi.mock('@/Components/Hotels/SearchBar.vue', () => ({
    default: { template: '<div />' },
}))
vi.mock('@/Components/Hotels/FilterSidebar.vue', () => ({
    default: { template: '<div />' },
}))
vi.mock('@inertiajs/vue3', () => ({
    router: { get: vi.fn(), visit: vi.fn() },
}))

import SearchPage from '@/Pages/Hotels/SearchPage.vue'

const makeHotels = (count, lastPage = 1) => ({
    data: Array.from({ length: count }, (_, i) => ({
        id: i + 1,
        name: `Hotel ${i + 1}`,
        slug: `hotel-${i + 1}`,
        city: 'Singapore',
        cover_photo: null,
        facilities: [],
        price_from: null,
        reviews_avg_rating: null,
    })),
    total: count,
    last_page: lastPage,
    links: [],
})

const baseFilters = { sort: 'latest' }

describe('SearchPage — hotel grid vs empty state', () => {
    it('renders hotel cards when hotels.data has entries', () => {
        const w = mount(SearchPage, { props: { hotels: makeHotels(3), filters: baseFilters } })
        expect(w.findAll('[data-testid="hotel-card"]').length).toBe(3)
    })

    it('shows empty state when hotels.data is empty', () => {
        const w = mount(SearchPage, { props: { hotels: makeHotels(0), filters: baseFilters } })
        expect(w.text()).toContain('No hotels found')
    })
})

describe('SearchPage — pagination', () => {
    it('renders pagination buttons when last_page > 1', () => {
        const hotels = makeHotels(20, 3)
        hotels.links = [
            { label: '&laquo; Previous', url: null, active: false },
            { label: '1', url: '/hotels?page=1', active: true },
            { label: '2', url: '/hotels?page=2', active: false },
            { label: 'Next &raquo;', url: '/hotels?page=2', active: false },
        ]
        const w = mount(SearchPage, { props: { hotels, filters: baseFilters } })
        expect(w.find('button[disabled]').exists()).toBe(true)
    })

    it('does not render pagination when last_page is 1', () => {
        const hotels = makeHotels(3, 1)
        const w = mount(SearchPage, { props: { hotels, filters: baseFilters } })
        // Pagination buttons are inside the v-if block — check no disabled nav buttons
        const buttons = w.findAll('button')
        const paginationButtons = buttons.filter(b => ['«', '»', '1', '2'].includes(b.text().trim()))
        expect(paginationButtons.length).toBe(0)
    })
})
