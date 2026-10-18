import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}))
vi.mock('@inertiajs/vue3', () => ({
    router: { patch: vi.fn() },
}))

import BookingDetailPage from '@/Pages/Bookings/BookingDetailPage.vue'

const baseBooking = {
    id: 1,
    status: 'pending',
    check_in: '2025-06-01',
    check_out: '2025-06-03',
    total_price: '120.00',
    notes: null,
    hotel: { id: 1, name: 'Paws Inn', slug: 'paws-inn', address: '1 Main St', city: 'Singapore' },
    pet: { id: 1, name: 'Buddy', species: 'dog' },
}

function mountPage(booking) {
    return mount(BookingDetailPage, { props: { booking } })
}

describe('BookingDetailPage — answer deadline', () => {
    it('says when the hotel answers by while the request is pending', () => {
        const w = mount(BookingDetailPage, {
            props: { booking: baseBooking, answerBy: 'Tue, 20 Oct 2026, 10:00 AM' },
        })
        expect(w.find('[data-testid="answer-by"]').text()).toContain(
            'The hotel answers by Tue, 20 Oct 2026, 10:00 AM.',
        )
    })

    it('shows no deadline without one', () => {
        const w = mountPage(baseBooking)
        expect(w.find('[data-testid="answer-by"]').exists()).toBe(false)
    })
})

describe('BookingDetailPage — notes section', () => {
    it('shows notes when booking.notes is non-empty', () => {
        const w = mountPage({ ...baseBooking, notes: 'Give extra treats.' })
        expect(w.text()).toContain('Give extra treats.')
    })

    it('hides notes section when booking.notes is null', () => {
        const w = mountPage({ ...baseBooking, notes: null })
        expect(w.text()).not.toContain('Notes')
    })
})

describe('BookingDetailPage — cancel button', () => {
    it('shows Cancel Booking button when status is pending', () => {
        const w = mountPage({ ...baseBooking, status: 'pending' })
        expect(w.text()).toContain('Cancel Booking')
    })

    it('hides Cancel Booking button when status is confirmed', () => {
        const w = mountPage({ ...baseBooking, status: 'confirmed' })
        expect(w.text()).not.toContain('Cancel Booking')
    })

    it('hides Cancel Booking button when status is cancelled', () => {
        const w = mountPage({ ...baseBooking, status: 'cancelled' })
        expect(w.text()).not.toContain('Cancel Booking')
    })
})

describe('BookingDetailPage — status badge', () => {
    it('shows Pending badge', () => {
        const w = mountPage({ ...baseBooking, status: 'pending' })
        expect(w.text()).toContain('Pending')
    })

    it('shows Confirmed badge', () => {
        const w = mountPage({ ...baseBooking, status: 'confirmed' })
        expect(w.text()).toContain('Confirmed')
    })

    it('shows Cancelled badge', () => {
        const w = mountPage({ ...baseBooking, status: 'cancelled' })
        expect(w.text()).toContain('Cancelled')
    })
})

describe('BookingDetailPage — check-in and check-out times', () => {
    it('adds the hotel times to the dates', () => {
        const w = mount(BookingDetailPage, {
            props: { booking: baseBooking, times: { check_in: '2:00 PM', check_out: '12:00 PM' } },
        })
        expect(w.text()).toContain(', from 2:00 PM')
        expect(w.text()).toContain(', by 12:00 PM')
    })

    it('shows the dates alone when the hotel has no times', () => {
        const w = mountPage(baseBooking)
        expect(w.text()).not.toContain(', from ')
        expect(w.text()).not.toContain(', by ')
    })
})
