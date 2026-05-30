import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}))
vi.mock('@/Components/PetFormModal.vue', () => ({
    default: { template: '<div data-testid="pet-form-modal" />' },
}))
vi.mock('@inertiajs/vue3', () => ({
    router: { delete: vi.fn() },
}))

import PetsPage from '@/Pages/Pets.vue'

const basePet = {
    id: 1,
    name: 'Buddy',
    species: 'Dog',
    breed: 'Labrador',
    age: 3,
    photo_url: null,
    special_needs: null,
}

describe('Pets page — empty state', () => {
    it('shows empty state when pets array is empty', () => {
        const w = mount(PetsPage, { props: { pets: [] } })
        expect(w.text()).toContain("haven't added any pets yet")
    })

    it('hides empty state when pets are present', () => {
        const w = mount(PetsPage, { props: { pets: [basePet] } })
        expect(w.text()).not.toContain("haven't added any pets yet")
    })
})

describe('Pets page — pet card rendering', () => {
    it('shows img when pet.photo_url is set', () => {
        const pet = { ...basePet, photo_url: '/storage/buddy.jpg' }
        const w = mount(PetsPage, { props: { pets: [pet] } })
        expect(w.find('img').exists()).toBe(true)
    })

    it('shows paw placeholder when pet.photo_url is null', () => {
        const w = mount(PetsPage, { props: { pets: [basePet] } })
        expect(w.find('img').exists()).toBe(false)
        expect(w.text()).toContain('🐾')
    })

    it('shows breed when pet.breed is set', () => {
        const w = mount(PetsPage, { props: { pets: [basePet] } })
        expect(w.text()).toContain('Labrador')
    })

    it('hides breed separator when pet.breed is empty', () => {
        const w = mount(PetsPage, { props: { pets: [{ ...basePet, breed: '' }] } })
        expect(w.text()).not.toContain('Labrador')
    })

    it('shows age when pet.age is set', () => {
        const w = mount(PetsPage, { props: { pets: [basePet] } })
        expect(w.text()).toContain('3 yrs')
    })

    it('hides age when pet.age is null', () => {
        const w = mount(PetsPage, { props: { pets: [{ ...basePet, age: null }] } })
        expect(w.text()).not.toContain('yrs')
    })

    it('shows special needs text when set', () => {
        const pet = { ...basePet, special_needs: 'Needs insulin shots' }
        const w = mount(PetsPage, { props: { pets: [pet] } })
        expect(w.text()).toContain('Needs insulin shots')
    })

    it('hides special needs when null', () => {
        const w = mount(PetsPage, { props: { pets: [basePet] } })
        expect(w.text()).not.toContain('Needs insulin shots')
    })
})
