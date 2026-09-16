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

import { router } from '@inertiajs/vue3'
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

describe('Pets page — modal and actions', () => {
    const modalStub = {
        props: ['show', 'pet'],
        emits: ['close'],
        template: '<div data-testid="pet-form-modal" :data-show="show" :data-pet="pet ? pet.id : \'\'" />',
    }

    // The mock above renders a bare div; this test group needs the real props, so it
    // swaps in a stub that exposes them as data attributes.
    const mountWithStub = (pets) =>
        mount(PetsPage, { props: { pets }, global: { stubs: { PetFormModal: modalStub } } })

    const modal = (w) => w.find('[data-testid="pet-form-modal"]')
    const button = (w, label) => w.findAll('button').find((b) => b.text() === label)

    it('starts with the modal hidden', () => {
        const w = mountWithStub([basePet])
        expect(modal(w).attributes('data-show')).toBe('false')
    })

    it('"+ Add Pet" opens the modal with no pet', async () => {
        const w = mountWithStub([basePet])
        await button(w, '+ Add Pet').trigger('click')
        expect(modal(w).attributes('data-show')).toBe('true')
        expect(modal(w).attributes('data-pet')).toBe('')
    })

    it('"Add your first pet" in the empty state opens the modal', async () => {
        const w = mountWithStub([])
        await button(w, 'Add your first pet').trigger('click')
        expect(modal(w).attributes('data-show')).toBe('true')
    })

    it('"Edit" opens the modal with that pet', async () => {
        const w = mountWithStub([basePet])
        await button(w, 'Edit').trigger('click')
        expect(modal(w).attributes('data-show')).toBe('true')
        expect(modal(w).attributes('data-pet')).toBe('1')
    })

    it('close event hides the modal and clears the pet', async () => {
        const w = mountWithStub([basePet])
        await button(w, 'Edit').trigger('click')
        await w.findComponent(modalStub).vm.$emit('close')
        expect(modal(w).attributes('data-show')).toBe('false')
        expect(modal(w).attributes('data-pet')).toBe('')
    })

    it('"Remove" deletes the pet when confirmed', async () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true)
        const w = mountWithStub([basePet])
        await button(w, 'Remove').trigger('click')
        expect(window.confirm).toHaveBeenCalledWith('Remove Buddy?')
        expect(router.delete).toHaveBeenCalledWith('/pets/1')
    })

    it('"Remove" does nothing when the confirm is dismissed', async () => {
        vi.spyOn(window, 'confirm').mockReturnValue(false)
        router.delete.mockClear()
        const w = mountWithStub([basePet])
        await button(w, 'Remove').trigger('click')
        expect(router.delete).not.toHaveBeenCalled()
    })
})
