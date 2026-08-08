import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@/Components/Hotels/HotelMap.vue', () => ({
    default: { template: '<div data-testid="hotel-map" />' },
}))
vi.mock('@/Components/Hotels/AvailabilityCalendar.vue', () => ({
    default: { template: '<div />' },
}))
vi.mock('@/Components/Hotels/ReviewList.vue', () => ({
    default: { template: '<div />' },
}))
vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}))
vi.mock('@inertiajs/vue3', () => ({
    Link: { template: '<a><slot /></a>' },
    usePage: () => ({ props: { auth: { user: null } } }),
}))

import HotelProfilePage from '@/Pages/Hotels/HotelProfilePage.vue'

const baseHotel = {
    id: 1,
    name: 'Pawsome Stay',
    slug: 'pawsome-stay',
    description: 'A great place for pets.',
    address: '10 Orchard Rd',
    city: 'Singapore',
    lat: 1.3521,
    lng: 103.8198,
    cover_photo_url: null,
    is_active: true,
    photos: [],
    facilities: [],
    policy: null,
    pricing: [],
}

const baseProps = { hotel: baseHotel, reviews: [], reviewsCount: 0, averageRating: null }

describe('HotelProfilePage — Location card null-guard', () => {
    it('renders HotelMap when lat and lng are both present', () => {
        const w = mount(HotelProfilePage, { props: baseProps })
        expect(w.find('[data-testid="hotel-map"]').exists()).toBe(true)
    })

    it('hides HotelMap and shows address-only when lat is null', () => {
        const w = mount(HotelProfilePage, {
            props: { ...baseProps, hotel: { ...baseHotel, lat: null } },
        })
        expect(w.find('[data-testid="hotel-map"]').exists()).toBe(false)
    })

    it('hides HotelMap and shows address-only when lng is null', () => {
        const w = mount(HotelProfilePage, {
            props: { ...baseProps, hotel: { ...baseHotel, lng: null } },
        })
        expect(w.find('[data-testid="hotel-map"]').exists()).toBe(false)
    })

    it('shows HotelMap when lat is 0 (equator — D1 regression guard)', () => {
        const w = mount(HotelProfilePage, {
            props: { ...baseProps, hotel: { ...baseHotel, lat: 0 } },
        })
        expect(w.find('[data-testid="hotel-map"]').exists()).toBe(true)
    })

    it('Google Maps link has the correct href', () => {
        const w = mount(HotelProfilePage, { props: baseProps })
        const link = w.find('a[rel="noopener"]')
        expect(link.attributes('href')).toBe('https://maps.google.com/?q=1.3521,103.8198')
    })
})

describe('HotelProfilePage — photo gallery', () => {
    const photo = (id, url) => ({ id, photo_url: url, sort_order: id })

    it('renders gallery photos from photo_url', () => {
        const w = mount(HotelProfilePage, {
            props: {
                ...baseProps,
                hotel: { ...baseHotel, photos: [photo(1, 'https://cdn.example.com/a.jpg')] },
            },
        })
        expect(w.find('img').attributes('src')).toBe('https://cdn.example.com/a.jpg')
    })

    it('puts the cover photo first', () => {
        const w = mount(HotelProfilePage, {
            props: {
                ...baseProps,
                hotel: {
                    ...baseHotel,
                    cover_photo_url: 'https://cdn.example.com/cover.jpg',
                    photos: [photo(1, 'https://cdn.example.com/a.jpg')],
                },
            },
        })
        expect(w.find('img').attributes('src')).toBe('https://cdn.example.com/cover.jpg')
    })

    it('shows no gallery when the hotel has no photos', () => {
        const w = mount(HotelProfilePage, { props: baseProps })
        expect(w.find('img').exists()).toBe(false)
    })

    // A photo row whose file could not be resolved would otherwise render an
    // <img> with src="undefined".
    it('skips photos with no resolvable url', () => {
        const w = mount(HotelProfilePage, {
            props: { ...baseProps, hotel: { ...baseHotel, photos: [photo(1, null)] } },
        })
        expect(w.find('img').exists()).toBe(false)
    })
})
