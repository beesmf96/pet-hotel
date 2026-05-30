import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import AvailabilityCalendar from '@/Components/Hotels/AvailabilityCalendar.vue'

// Freeze time so "today" and "past" comparisons are deterministic
const TODAY = '2030-06-15'
vi.setSystemTime(new Date(TODAY))

function stubFetch(days = {}) {
    global.fetch = vi.fn(() =>
        Promise.resolve({
            ok: true,
            json: () => Promise.resolve({ days }),
        }),
    )
}

function makeDay(date, status = 'available', available_spots = 10) {
    return { date, status, available_spots }
}

function mountCalendar(modelValue = null, selectable = true) {
    stubFetch()
    return mount(AvailabilityCalendar, {
        props: {
            hotelSlug: 'paws-inn',
            selectable,
            modelValue,
        },
    })
}

async function clickDate(wrapper, dateString) {
    await flushPromises()
    const cells = wrapper.findAll('.grid.grid-cols-7 > div')
    const cell = cells.find((c) => c.text() === String(Number(dateString.slice(8))))
    if (!cell) throw new Error(`Date cell not found for ${dateString}`)
    await cell.trigger('click')
}

describe('AvailabilityCalendar — read-only mode (selectable=false)', () => {
    it('emits no update:modelValue when a date is clicked', async () => {
        const w = mountCalendar(null, false)
        await flushPromises()
        const cells = w.findAll('.grid.grid-cols-7 > div')
        await cells[cells.length - 1].trigger('click')
        expect(w.emitted('update:modelValue')).toBeFalsy()
    })
})

describe('AvailabilityCalendar — selection state machine', () => {
    beforeEach(() => {
        stubFetch()
    })

    it('clicking an available future date sets checkIn when nothing is selected', async () => {
        const w = mountCalendar({ checkIn: '', checkOut: '' })
        await flushPromises()
        // Click day 20 (future date in June 2030)
        const cells = w.findAll('[class*="grid-cols-7"] > div')
        const futureCell = cells.find((c) => c.text() === '20')
        await futureCell.trigger('click')
        const emitted = w.emitted('update:modelValue')
        expect(emitted).toBeTruthy()
        expect(emitted[0][0].checkIn).toBe('2030-06-20')
        expect(emitted[0][0].checkOut).toBe('')
    })

    it('clicking a later date after checkIn sets checkOut', async () => {
        const w = mountCalendar({ checkIn: '2030-06-20', checkOut: '' })
        await flushPromises()
        const cells = w.findAll('[class*="grid-cols-7"] > div')
        const laterCell = cells.find((c) => c.text() === '25')
        await laterCell.trigger('click')
        const emitted = w.emitted('update:modelValue')
        expect(emitted).toBeTruthy()
        expect(emitted[0][0].checkIn).toBe('2030-06-20')
        expect(emitted[0][0].checkOut).toBe('2030-06-25')
    })

    it('clicking same date as checkIn is a no-op (double-click guard)', async () => {
        const w = mountCalendar({ checkIn: '2030-06-20', checkOut: '' })
        await flushPromises()
        const cells = w.findAll('[class*="grid-cols-7"] > div')
        const sameCell = cells.find((c) => c.text() === '20')
        await sameCell.trigger('click')
        expect(w.emitted('update:modelValue')).toBeFalsy()
    })

    it('clicking an earlier date than checkIn resets with the new date as checkIn', async () => {
        const w = mountCalendar({ checkIn: '2030-06-20', checkOut: '' })
        await flushPromises()
        const cells = w.findAll('[class*="grid-cols-7"] > div')
        const earlierCell = cells.find((c) => c.text() === '18')
        await earlierCell.trigger('click')
        const emitted = w.emitted('update:modelValue')
        expect(emitted).toBeTruthy()
        expect(emitted[0][0].checkIn).toBe('2030-06-18')
        expect(emitted[0][0].checkOut).toBe('')
    })

    it('clicking any date when both are set resets with the new date as checkIn', async () => {
        const w = mountCalendar({ checkIn: '2030-06-18', checkOut: '2030-06-22' })
        await flushPromises()
        const cells = w.findAll('[class*="grid-cols-7"] > div')
        const newCell = cells.find((c) => c.text() === '25')
        await newCell.trigger('click')
        const emitted = w.emitted('update:modelValue')
        expect(emitted).toBeTruthy()
        expect(emitted[0][0].checkIn).toBe('2030-06-25')
        expect(emitted[0][0].checkOut).toBe('')
    })

    it('does not emit when a full date is clicked', async () => {
        stubFetch({ '2030-06-20': makeDay('2030-06-20', 'full', 0) })
        const w = mount(AvailabilityCalendar, {
            props: { hotelSlug: 'paws-inn', selectable: true, modelValue: { checkIn: '', checkOut: '' } },
        })
        await flushPromises()
        const cells = w.findAll('[class*="grid-cols-7"] > div')
        const fullCell = cells.find((c) => c.text() === '20')
        await fullCell.trigger('click')
        expect(w.emitted('update:modelValue')).toBeFalsy()
    })

    it('does not emit when a blocked date is clicked', async () => {
        stubFetch({ '2030-06-20': makeDay('2030-06-20', 'blocked') })
        const w = mount(AvailabilityCalendar, {
            props: { hotelSlug: 'paws-inn', selectable: true, modelValue: { checkIn: '', checkOut: '' } },
        })
        await flushPromises()
        const cells = w.findAll('[class*="grid-cols-7"] > div')
        const blockedCell = cells.find((c) => c.text() === '20')
        await blockedCell.trigger('click')
        expect(w.emitted('update:modelValue')).toBeFalsy()
    })

    it('does not emit when a past date is clicked', async () => {
        const w = mountCalendar({ checkIn: '', checkOut: '' })
        await flushPromises()
        // Day 10 is before today (June 15, 2030)
        const cells = w.findAll('[class*="grid-cols-7"] > div')
        const pastCell = cells.find((c) => c.text() === '10')
        await pastCell.trigger('click')
        expect(w.emitted('update:modelValue')).toBeFalsy()
    })
})

describe('AvailabilityCalendar — visual state classes', () => {
    it('applies selected-endpoint class to checkIn date', async () => {
        stubFetch()
        const w = mount(AvailabilityCalendar, {
            props: {
                hotelSlug: 'paws-inn',
                selectable: true,
                modelValue: { checkIn: '2030-06-20', checkOut: '' },
            },
        })
        await flushPromises()
        const cells = w.findAll('[class*="grid-cols-7"] > div')
        const checkInCell = cells.find((c) => c.text() === '20')
        expect(checkInCell.classes()).toContain('bg-gray-900')
        expect(checkInCell.classes()).toContain('text-white')
    })

    it('applies in-range class to dates between checkIn and checkOut', async () => {
        stubFetch()
        const w = mount(AvailabilityCalendar, {
            props: {
                hotelSlug: 'paws-inn',
                selectable: true,
                modelValue: { checkIn: '2030-06-18', checkOut: '2030-06-22' },
            },
        })
        await flushPromises()
        const cells = w.findAll('[class*="grid-cols-7"] > div')
        const rangeCell = cells.find((c) => c.text() === '20')
        expect(rangeCell.classes()).toContain('bg-gray-100')
    })
})
