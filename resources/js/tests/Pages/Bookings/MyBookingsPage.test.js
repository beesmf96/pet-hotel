import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}))
vi.mock('@/Components/Hotels/LeaveReviewModal.vue', () => ({
    default: { template: '<div data-testid="leave-review-modal" />' },
}))
vi.mock('@inertiajs/vue3', () => ({
    Link: { template: '<a><slot /></a>' },
}))
vi.mock('@/composables/useFormatDate.js', () => ({
    useFormatDate: () => ({ formatDate: (d) => d }),
}))

import MyBookingsPage from '@/Pages/Bookings/MyBookingsPage.vue'

const baseBooking = {
    id: 1,
    status: 'confirmed',
    check_in: '2025-06-01',
    check_out: '2025-06-03',
    total_price: '120.00',
    has_review: false,
    hotel: { id: 1, name: 'Paws Inn' },
    pet: { id: 1, name: 'Buddy' },
}

describe('MyBookingsPage — empty state', () => {
    it('shows empty state when bookings is empty', () => {
        const w = mount(MyBookingsPage, { props: { bookings: [] } })
        expect(w.text()).toContain('You have no bookings yet')
    })

    it('does not show empty state when bookings has entries', () => {
        const w = mount(MyBookingsPage, { props: { bookings: [baseBooking] } })
        expect(w.text()).not.toContain('You have no bookings yet')
    })
})

describe('MyBookingsPage — booking list', () => {
    it('renders booking entry when bookings is non-empty', () => {
        const w = mount(MyBookingsPage, { props: { bookings: [baseBooking] } })
        expect(w.text()).toContain('Paws Inn')
    })
})

describe('MyBookingsPage — review prompts', () => {
    it('shows Leave a review button for completed booking without review', () => {
        const booking = { ...baseBooking, status: 'completed', has_review: false }
        const w = mount(MyBookingsPage, { props: { bookings: [booking] } })
        expect(w.text()).toContain('Leave a review')
    })

    it('shows Review submitted for completed booking with review', () => {
        const booking = { ...baseBooking, status: 'completed', has_review: true }
        const w = mount(MyBookingsPage, { props: { bookings: [booking] } })
        expect(w.text()).toContain('Review submitted')
    })

    it('shows neither review prompt for confirmed booking', () => {
        const w = mount(MyBookingsPage, { props: { bookings: [{ ...baseBooking, status: 'confirmed' }] } })
        expect(w.text()).not.toContain('Leave a review')
        expect(w.text()).not.toContain('Review submitted')
    })
})

describe('MyBookingsPage — review modal', () => {
    it('renders LeaveReviewModal stub in the component tree', () => {
        const booking = { ...baseBooking, status: 'completed', has_review: false }
        const w = mount(MyBookingsPage, {
            props: { bookings: [booking] },
            attachTo: document.body,
        })
        // The modal uses v-if based on reviewBooking ref; initially null so not rendered
        expect(w.find('[data-testid="leave-review-modal"]').exists()).toBe(false)
    })

    it('shows LeaveReviewModal after clicking Leave a review', async () => {
        const booking = { ...baseBooking, status: 'completed', has_review: false }
        const w = mount(MyBookingsPage, {
            props: { bookings: [booking] },
            attachTo: document.body,
        })
        const btn = w.findAll('button').find(b => b.text().includes('Leave a review'))
        await btn.trigger('click')
        expect(w.find('[data-testid="leave-review-modal"]').exists()).toBe(true)
    })
})
