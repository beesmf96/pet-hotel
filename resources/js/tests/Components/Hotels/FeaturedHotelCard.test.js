import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    router: { visit: vi.fn() },
}))

import { router } from '@inertiajs/vue3'
import FeaturedHotelCard from '@/Components/Hotels/FeaturedHotelCard.vue'

const baseHotel = { id: 3, name: 'The Bark Lodge', slug: 'the-bark-lodge', city: 'Bangsar' }

describe('FeaturedHotelCard — photo', () => {
    it('renders the cover photo when present', () => {
        const w = mount(FeaturedHotelCard, { props: { hotel: { ...baseHotel, cover_photo_url: '/p.jpg' } } })
        expect(w.find('img').attributes('src')).toBe('/p.jpg')
    })

    it('renders a tinted placeholder without a photo', () => {
        const w = mount(FeaturedHotelCard, { props: { hotel: baseHotel } })
        expect(w.find('img').exists()).toBe(false)
        expect(w.find('svg').exists()).toBe(true)
    })
})

describe('FeaturedHotelCard — rating and price', () => {
    it('shows the rating to one decimal', () => {
        const w = mount(FeaturedHotelCard, { props: { hotel: { ...baseHotel, reviews_avg_rating: '4.8333' } } })
        expect(w.text()).toContain('4.8')
    })

    it('marks an unrated hotel as New', () => {
        const w = mount(FeaturedHotelCard, { props: { hotel: baseHotel } })
        expect(w.text()).toContain('New')
    })

    it('shows the nightly price without decimals', () => {
        const w = mount(FeaturedHotelCard, { props: { hotel: { ...baseHotel, price_from: '70.00' } } })
        expect(w.text()).toContain('RM 70')
        expect(w.text()).toContain('/ night')
    })

    it('says pricing on request without a price', () => {
        const w = mount(FeaturedHotelCard, { props: { hotel: baseHotel } })
        expect(w.text()).toContain('Pricing on request')
    })
})

describe('FeaturedHotelCard — facilities', () => {
    it('renders no chips without facilities', () => {
        const w = mount(FeaturedHotelCard, { props: { hotel: { ...baseHotel, facilities: [] } } })
        expect(w.text()).not.toContain('Grooming')
    })

    it('shows up to three labelled chips and a count for the rest', () => {
        const facilities = [
            { id: 1, type: 'grooming' },
            { id: 2, type: 'play_area' },
            { id: 3, type: 'webcam' },
            { id: 4, type: 'vet_care' },
        ]
        const w = mount(FeaturedHotelCard, { props: { hotel: { ...baseHotel, facilities } } })
        expect(w.text()).toContain('Grooming')
        expect(w.text()).toContain('Play area')
        expect(w.text()).toContain('Webcam')
        expect(w.text()).not.toContain('Vet care')
        expect(w.text()).toContain('+1 more')
    })
})

describe('FeaturedHotelCard — navigation', () => {
    it('visits the hotel page on click', async () => {
        const w = mount(FeaturedHotelCard, { props: { hotel: baseHotel } })
        await w.trigger('click')
        expect(router.visit).toHaveBeenCalledWith('/hotels/the-bark-lodge')
    })
})
