import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import { reactive } from 'vue'

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
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
    pricing: [{ pet_type: 'Dog', price_per_night: '40.00' }],
}

function mountPage(pets = [], hotel = baseHotel) {
    formState.pet_id = ''
    formState.check_in = ''
    formState.check_out = ''
    return mount(BookingFormPage, { props: { hotel, pets } })
}

describe('BookingFormPage — no pets warning', () => {
    it('shows warning when pets array is empty', () => {
        const w = mountPage([])
        expect(w.text()).toContain('add a pet')
    })

    it('hides warning and shows form when pets are present', () => {
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'Dog' }])
        expect(w.find('form').exists()).toBe(true)
        expect(w.text()).not.toContain('add a pet')
    })
})

describe('BookingFormPage — price summary', () => {
    it('shows placeholder text when no pet or dates selected', () => {
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'Dog' }])
        expect(w.text()).toContain('Select a pet and dates to see the price')
    })

    it('shows price calculation when pet and dates are selected', async () => {
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'Dog' }])
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
        const w = mountPage([{ id: 1, name: 'Whiskers', species: 'Cat' }], hotel)
        formState.pet_id = 1
        formState.check_in = ''
        formState.check_out = ''
        await w.vm.$nextTick()
        expect(w.text()).toContain('no pricing listed for')
    })

    it('does not show no-pricing warning when no pet is selected', () => {
        const w = mountPage([{ id: 1, name: 'Buddy', species: 'Dog' }])
        expect(w.text()).not.toContain('no pricing listed for')
    })
})
