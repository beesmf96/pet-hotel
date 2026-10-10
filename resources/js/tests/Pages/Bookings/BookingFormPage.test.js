import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import { reactive } from 'vue'

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}))

vi.mock('@/Components/Hotels/AvailabilityCalendar.vue', () => ({
    default: { template: '<div data-testid="availability-calendar" />' },
}))

const formState = reactive({
    pet_id: '',
    check_in: '',
    check_out: '',
    notes: '',
    processing: false,
    errors: {},
    post: vi.fn(),
    reset: vi.fn(),
    clearErrors: vi.fn(),
})

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn(() => formState),
}))

import BookingFormPage from '@/Pages/Bookings/BookingFormPage.vue'

const baseHotel = {
    id: 1,
    name: 'Paws Inn',
    slug: 'paws-inn',
    pricing: [{ pet_type: 'dog', price_per_night: '40.00' }],
}

function mountPage(pets = [], hotel = baseHotel) {
    formState.pet_id = ''
    formState.check_in = ''
    formState.check_out = ''
    return mount(BookingFormPage, { props: { hotel, pets } })
}

describe('BookingFormPage — availability calendar', () => {
    it('renders the availability calendar stub when pets are present', () => {
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'dog' }])
        expect(w.find('[data-testid="availability-calendar"]').exists()).toBe(true)
    })
})

describe('BookingFormPage — no pets warning', () => {
    it('shows warning when pets array is empty', () => {
        const w = mountPage([])
        expect(w.text()).toContain('add a pet')
    })

    it('hides warning and shows form when pets are present', () => {
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'dog' }])
        expect(w.find('form').exists()).toBe(true)
        expect(w.text()).not.toContain('add a pet')
    })
})

describe('BookingFormPage — price summary', () => {
    it('shows placeholder text when no pet or dates selected', () => {
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'dog' }])
        expect(w.text()).toContain('Select a pet and dates to see the price')
    })

    it('shows price calculation when pet and dates are selected', async () => {
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'dog' }])
        formState.pet_id = 1
        formState.check_in = '2025-06-01'
        formState.check_out = '2025-06-03'
        await w.vm.$nextTick()
        expect(w.text()).toContain('Total')
        expect(w.text()).not.toContain('Select a pet and dates to see the price')
    })
})

describe('BookingFormPage — no pricing warning', () => {
    it('shows no-pricing warning when selected pet species has no matching pricing', async () => {
        const hotel = { ...baseHotel, pricing: [] }
        const w = mountPage([{ id: 1, name: 'Whiskers', species: 'cat' }], hotel)
        formState.pet_id = 1
        formState.check_in = ''
        formState.check_out = ''
        await w.vm.$nextTick()
        expect(w.text()).toContain('no pricing listed for')
        expect(w.text()).toContain('Whiskers cannot be booked here')
        expect(w.find('button[type="submit"]').attributes('disabled')).toBeDefined()
    })

    it('keeps the submit button enabled when the pet has a price', async () => {
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'dog' }])
        formState.pet_id = 1
        await w.vm.$nextTick()
        expect(w.find('button[type="submit"]').attributes('disabled')).toBeUndefined()
    })

    it('shows a total with thousands separators', async () => {
        const hotel = { ...baseHotel, pricing: [{ pet_type: 'dog', price_per_night: '650.00' }] }
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'dog' }], hotel)
        formState.pet_id = 1
        formState.check_in = '2030-06-01'
        formState.check_out = '2030-06-03'
        await w.vm.$nextTick()
        expect(w.text()).toContain('RM 650.00 × 2 nights')
        expect(w.text()).toContain('RM 1,300.00')
    })

    it('does not show no-pricing warning when no pet is selected', () => {
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'dog' }])
        expect(w.text()).not.toContain('no pricing listed for')
    })
})

describe('BookingFormPage — check-in and check-out times', () => {
    it('shows the hotel times when the hotel has them', () => {
        const w = mount(BookingFormPage, {
            props: { hotel: baseHotel, pets: [{ id: 1, name: 'Buddy', species: 'dog' }], times: { check_in: '2:00 PM', check_out: '12:00 PM' } },
        })
        expect(w.text()).toContain('Check-in from 2:00 PM · check-out by 12:00 PM')
    })

    it('shows no times line when the hotel has none', () => {
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'dog' }])
        expect(w.text()).not.toContain('Check-in from')
    })
})

describe('BookingFormPage — date errors', () => {
    it('shows the server message for a date error', () => {
        formState.errors = { check_in: 'The hotel is full or closed on at least one night of this stay.' }
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'dog' }])
        expect(w.text()).toContain('The hotel is full or closed on at least one night of this stay.')
        formState.errors = {}
    })

    it('shows the check-out error on its own', () => {
        formState.errors = { check_out: 'Please select a check-out date.' }
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'dog' }])
        expect(w.text()).toContain('Please select a check-out date.')
        formState.errors = {}
    })
})
