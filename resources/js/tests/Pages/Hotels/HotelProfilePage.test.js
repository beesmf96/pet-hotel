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

describe('HotelProfilePage — gallery navigation', () => {
    const photos = [
        { id: 1, photo_url: 'https://cdn.example.com/a.jpg', sort_order: 1 },
        { id: 2, photo_url: 'https://cdn.example.com/b.jpg', sort_order: 2 },
        { id: 3, photo_url: 'https://cdn.example.com/c.jpg', sort_order: 3 },
    ]
    const mountGallery = (list = photos) =>
        mount(HotelProfilePage, { props: { ...baseProps, hotel: { ...baseHotel, photos: list } } })
    const src = (w) => w.find('img').attributes('src')
    const arrow = (w, glyph) => w.findAll('button').find((b) => b.text() === glyph)

    it('hides the arrows and dots for a single photo', () => {
        const w = mountGallery([photos[0]])
        expect(arrow(w, '›')).toBeUndefined()
        expect(arrow(w, '‹')).toBeUndefined()
    })

    it('next advances to the following photo', async () => {
        const w = mountGallery()
        await arrow(w, '›').trigger('click')
        expect(src(w)).toBe('https://cdn.example.com/b.jpg')
    })

    it('next wraps from the last photo back to the first', async () => {
        const w = mountGallery()
        await arrow(w, '›').trigger('click')
        await arrow(w, '›').trigger('click')
        await arrow(w, '›').trigger('click')
        expect(src(w)).toBe('https://cdn.example.com/a.jpg')
    })

    it('previous wraps from the first photo to the last', async () => {
        const w = mountGallery()
        await arrow(w, '‹').trigger('click')
        expect(src(w)).toBe('https://cdn.example.com/c.jpg')
    })

    it('a dot jumps straight to that photo', async () => {
        const w = mountGallery()
        const dots = w.findAll('button.rounded-full.w-2')
        expect(dots).toHaveLength(3)
        await dots[2].trigger('click')
        expect(src(w)).toBe('https://cdn.example.com/c.jpg')
        expect(dots[2].classes()).toContain('bg-white')
        expect(dots[0].classes()).toContain('bg-white/50')
    })
})

describe('HotelProfilePage — facilities, policies, and pricing', () => {
    it('lists facilities by label', () => {
        const hotel = { ...baseHotel, facilities: [{ id: 1, type: 'grooming' }, { id: 2, type: '24h_care' }] }
        const w = mount(HotelProfilePage, { props: { ...baseProps, hotel } })
        expect(w.text()).toContain('Facilities')
        expect(w.text()).toContain('Grooming')
        expect(w.text()).toContain('24h Care')
    })

    it('hides the facilities card when there are none', () => {
        const w = mount(HotelProfilePage, { props: baseProps })
        expect(w.text()).not.toContain('Facilities')
    })

    it('shows check-in and check-out times from the policy', () => {
        const hotel = { ...baseHotel, policy: { check_in_time: '14:00', check_out_time: '11:00', cancellation_policy: null } }
        const w = mount(HotelProfilePage, { props: { ...baseProps, hotel } })
        expect(w.text()).toContain('14:00')
        expect(w.text()).toContain('11:00')
        expect(w.text()).not.toContain('Cancellation')
    })

    it('shows the cancellation policy when set', () => {
        const hotel = {
            ...baseHotel,
            policy: { check_in_time: '14:00', check_out_time: '11:00', cancellation_policy: 'Free up to 48h before.' },
        }
        const w = mount(HotelProfilePage, { props: { ...baseProps, hotel } })
        expect(w.text()).toContain('Cancellation')
        expect(w.text()).toContain('Free up to 48h before.')
    })

    it('hides the policies card when the hotel has no policy', () => {
        const w = mount(HotelProfilePage, { props: baseProps })
        expect(w.text()).not.toContain('Policies')
    })

    it('formats pricing per pet type to two decimals', () => {
        const hotel = { ...baseHotel, pricing: [{ id: 1, pet_type: 'dog', price_per_night: '45.5' }] }
        const w = mount(HotelProfilePage, { props: { ...baseProps, hotel } })
        expect(w.text()).toContain('Dog')
        expect(w.text()).toContain('RM 45.50')
        expect(w.text()).toContain('/ night')
    })

    it('hides the pricing card when there is no pricing', () => {
        const w = mount(HotelProfilePage, { props: baseProps })
        expect(w.text()).not.toContain('Pricing')
    })
})

describe('HotelProfilePage — reviews link', () => {
    it('offers "View all" when there are more than five reviews', () => {
        const w = mount(HotelProfilePage, { props: { ...baseProps, reviewsCount: 8 } })
        expect(w.text()).toContain('View all 8')
    })

    it('hides "View all" at five reviews or fewer', () => {
        const w = mount(HotelProfilePage, { props: { ...baseProps, reviewsCount: 5 } })
        expect(w.text()).not.toContain('View all')
    })

    it('links to the booking page for this hotel', () => {
        const w = mount(HotelProfilePage, { props: baseProps })
        const book = w.findAll('a').find((a) => a.text() === 'Book Now')
        expect(book).toBeDefined()
    })
})
