import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'

import FilterSidebar from '@/Components/Hotels/FilterSidebar.vue'

const button = (w, label) => w.findAll('button').find((b) => b.text() === label)
const lastApply = (w) => w.emitted('apply').at(-1)[0]
const checked = (w) => w.findAll('input[type="checkbox"]').filter((c) => c.element.checked).map((c) => c.element.value)

describe('FilterSidebar — initial state', () => {
    it('seeds price and facilities from the filters prop', () => {
        const w = mount(FilterSidebar, {
            props: { filters: { price_min: '20', price_max: '80', facilities: ['grooming', 'webcam'] } },
        })
        const [min, max] = w.findAll('input[type="number"]')
        expect(min.element.value).toBe('20')
        expect(max.element.value).toBe('80')
        expect(checked(w)).toEqual(['grooming', 'webcam'])
    })

    it('accepts facilities as a comma-separated string from the query', () => {
        const w = mount(FilterSidebar, { props: { filters: { facilities: 'vet_care,training' } } })
        expect(checked(w)).toEqual(['vet_care', 'training'])
    })

    it('starts with nothing checked when there are no facility filters', () => {
        const w = mount(FilterSidebar, { props: { filters: {} } })
        expect(checked(w)).toEqual([])
        expect(w.findAll('input[type="checkbox"]')).toHaveLength(8)
    })
})

describe('FilterSidebar — Apply Filters', () => {
    it('emits the search context plus price and facilities', async () => {
        const w = mount(FilterSidebar, {
            props: { filters: { city: 'Bedok', pet_type: 'dog', check_in: '2026-10-01', check_out: '2026-10-03' } },
        })
        const [min, max] = w.findAll('input[type="number"]')
        await min.setValue('10')
        await max.setValue('50')
        await w.find('input[value="grooming"]').setValue(true)
        await button(w, 'Apply Filters').trigger('click')
        expect(lastApply(w)).toEqual({
            city: 'Bedok', pet_type: 'dog', check_in: '2026-10-01', check_out: '2026-10-03',
            price_min: 10, price_max: 50, facilities: ['grooming'],
        })
    })

    it('sends undefined for empty price and facilities', async () => {
        const w = mount(FilterSidebar, { props: { filters: {} } })
        await button(w, 'Apply Filters').trigger('click')
        expect(lastApply(w)).toMatchObject({ price_min: undefined, price_max: undefined, facilities: undefined })
    })
})

describe('FilterSidebar — Clear Filters', () => {
    it('resets the inputs and emits only the search context', async () => {
        const w = mount(FilterSidebar, {
            props: { filters: { city: 'Bedok', price_min: '10', facilities: ['grooming'] } },
        })
        await button(w, 'Clear Filters').trigger('click')
        expect(w.findAll('input[type="number"]')[0].element.value).toBe('')
        expect(checked(w)).toEqual([])
        expect(lastApply(w)).toEqual({ city: 'Bedok', pet_type: undefined, check_in: undefined, check_out: undefined })
    })
})
