import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    router: { visit: vi.fn() },
}))

import HotelCard from '@/Components/Hotels/HotelCard.vue'

const baseHotel = {
    id: 1,
    name: 'Paws Inn',
    slug: 'paws-inn',
    city: 'Singapore',
    cover_photo_url: null,
    facilities: [],
    price_from: null,
    reviews_avg_rating: null,
}

describe('HotelCard — cover photo', () => {
    it('shows img when hotel.cover_photo_url is set', () => {
        const hotel = { ...baseHotel, cover_photo_url: 'https://cdn.example.com/paws.jpg' }
        const w = mount(HotelCard, { props: { hotel } })
        expect(w.find('img').exists()).toBe(true)
    })

    it('shows 🏨 placeholder when hotel.cover_photo_url is null', () => {
        const w = mount(HotelCard, { props: { hotel: baseHotel } })
        expect(w.find('img').exists()).toBe(false)
        expect(w.text()).toContain('🏨')
    })
})

describe('HotelCard — facilities', () => {
    it('shows facilities list when hotel.facilities has entries', () => {
        const hotel = {
            ...baseHotel,
            facilities: [{ id: 1, type: 'grooming' }, { id: 2, type: 'play_area' }],
        }
        const w = mount(HotelCard, { props: { hotel } })
        expect(w.text()).toContain('Grooming')
    })

    it('shows +N more badge when facilities > 3', () => {
        const hotel = {
            ...baseHotel,
            facilities: [
                { id: 1, type: 'grooming' },
                { id: 2, type: 'play_area' },
                { id: 3, type: 'vet_care' },
                { id: 4, type: 'webcam' },
            ],
        }
        const w = mount(HotelCard, { props: { hotel } })
        expect(w.text()).toContain('+1 more')
    })

    it('does not show +N more when facilities <= 3', () => {
        const hotel = {
            ...baseHotel,
            facilities: [{ id: 1, type: 'grooming' }, { id: 2, type: 'play_area' }],
        }
        const w = mount(HotelCard, { props: { hotel } })
        expect(w.text()).not.toContain('more')
    })
})

describe('HotelCard — price', () => {
    it('shows From $X.XX when hotel.price_from is set', () => {
        const hotel = { ...baseHotel, price_from: '35.00' }
        const w = mount(HotelCard, { props: { hotel } })
        expect(w.text()).toContain('From $35.00')
    })

    it('shows Pricing unavailable when hotel.price_from is null', () => {
        const w = mount(HotelCard, { props: { hotel: baseHotel } })
        expect(w.text()).toContain('Pricing unavailable')
    })
})

describe('HotelCard — rating', () => {
    it('shows formatted rating when hotel.reviews_avg_rating is set', () => {
        const hotel = { ...baseHotel, reviews_avg_rating: '4.5' }
        const w = mount(HotelCard, { props: { hotel } })
        expect(w.text()).toContain('4.5')
    })

    it('shows — when hotel.reviews_avg_rating is null', () => {
        const w = mount(HotelCard, { props: { hotel: baseHotel } })
        expect(w.text()).toContain('—')
    })
})
