import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'

import SearchBar from '@/Components/Hotels/SearchBar.vue'

const lastSearch = (w) => w.emitted('search').at(-1)[0]

describe('SearchBar — initial values', () => {
    it('seeds the fields from the filters prop', () => {
        const w = mount(SearchBar, {
            props: { filters: { city: 'Bedok', check_in: '2026-10-01', check_out: '2026-10-03', pet_type: 'cat' } },
        })
        expect(w.find('input[type="text"]').element.value).toBe('Bedok')
        expect(w.findAll('input[type="date"]').map((i) => i.element.value)).toEqual(['2026-10-01', '2026-10-03'])
        expect(w.find('select').element.value).toBe('cat')
    })

    it('starts empty when the filters are empty', () => {
        const w = mount(SearchBar, { props: { filters: {} } })
        expect(w.find('input[type="text"]').element.value).toBe('')
        expect(w.find('select').element.value).toBe('')
    })
})

describe('SearchBar — emitting a search', () => {
    it('emits the filled fields and drops the empty ones as undefined', async () => {
        const w = mount(SearchBar, { props: { filters: {} } })
        await w.find('input[type="text"]').setValue('Bedok')
        await w.find('select').setValue('dog')
        await w.find('button').trigger('click')
        expect(lastSearch(w)).toEqual({ city: 'Bedok', check_in: undefined, check_out: undefined, pet_type: 'dog' })
    })

    it('includes both dates when set', async () => {
        const w = mount(SearchBar, { props: { filters: {} } })
        const [checkIn, checkOut] = w.findAll('input[type="date"]')
        await checkIn.setValue('2026-10-01')
        await checkOut.setValue('2026-10-03')
        await w.find('button').trigger('click')
        expect(lastSearch(w)).toMatchObject({ check_in: '2026-10-01', check_out: '2026-10-03' })
    })

    it('pressing Enter in the city field searches too', async () => {
        const w = mount(SearchBar, { props: { filters: {} } })
        await w.find('input[type="text"]').trigger('keyup.enter')
        expect(w.emitted('search')).toHaveLength(1)
    })

    it('lists every pet type option', () => {
        const w = mount(SearchBar, { props: { filters: {} } })
        expect(w.findAll('option').map((o) => o.text())).toEqual([
            'Any pet type', 'Dog', 'Cat', 'Rabbit', 'Bird', 'Other',
        ])
    })
})
