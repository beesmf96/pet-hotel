import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}))
vi.mock('@inertiajs/vue3', () => ({
    router: { patch: vi.fn() },
    usePage: vi.fn(),
}))

import { usePage } from '@inertiajs/vue3'
import BookingDetailPage from '@/Pages/Bookings/BookingDetailPage.vue'

const baseBooking = {
    id: 1,
    status: 'pending',
    check_in: '2025-06-01',
    check_out: '2025-06-03',
    total_price: '120.00',
    notes: null,
    hotel: { id: 1, name: 'Paws Inn', slug: 'paws-inn', address: '1 Main St', city: 'Singapore' },
    pet: { id: 1, name: 'Buddy', species: 'Dog' },
}

function mountPage(booking, flash = {}) {
    usePage.mockReturnValue({ props: { flash } })
    return mount(BookingDetailPage, { props: { booking } })
}

describe('BookingDetailPage — flash banner', () => {
    it('shows success banner when flash.success is set', () => {
        const w = mountPage(baseBooking, { success: 'Booking cancelled.' })
        expect(w.text()).toContain('Booking cancelled.')
    })

    it('hides success banner when flash is empty', () => {
        const w = mountPage(baseBooking, {})
        expect(w.text()).not.toContain('Booking cancelled.')
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
