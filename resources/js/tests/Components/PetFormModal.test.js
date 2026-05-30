import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

const formState = {
    name: '',
    species: '',
    breed: '',
    age: '',
    special_needs: '',
    photo: null,
    processing: false,
    errors: {},
    post: vi.fn(),
    patch: vi.fn(),
    reset: vi.fn(),
    clearErrors: vi.fn(),
}

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn(() => formState),
}))

import PetFormModal from '@/Components/PetFormModal.vue'

describe('PetFormModal — visibility', () => {
    it('does not render modal content when show is false', () => {
        const w = mount(PetFormModal, {
            props: { show: false, pet: null },
            attachTo: document.body,
        })
        expect(document.body.querySelector('form')).toBeNull()
    })

    it('renders modal content when show is true', () => {
        const w = mount(PetFormModal, {
            props: { show: true, pet: null },
            attachTo: document.body,
        })
        expect(document.body.querySelector('form')).not.toBeNull()
        w.unmount()
    })
})

describe('PetFormModal — title and submit button', () => {
    it('shows "Add Pet" title when pet prop is null', () => {
        const w = mount(PetFormModal, {
            props: { show: true, pet: null },
            attachTo: document.body,
        })
        expect(document.body.textContent).toContain('Add Pet')
        w.unmount()
    })

    it('shows "Edit Pet" title when pet prop is an object', () => {
        const pet = { id: 1, name: 'Buddy', species: 'Dog', breed: '', age: 3, special_needs: '' }
        const w = mount(PetFormModal, {
            props: { show: true, pet },
            attachTo: document.body,
        })
        expect(document.body.textContent).toContain('Edit Pet')
        w.unmount()
    })

    it('shows "Add Pet" submit button when pet is null', () => {
        const w = mount(PetFormModal, {
            props: { show: true, pet: null },
            attachTo: document.body,
        })
        const buttons = Array.from(document.body.querySelectorAll('button'))
        const submitBtn = buttons.find(b => b.type === 'submit')
        expect(submitBtn.textContent.trim()).toBe('Add Pet')
        w.unmount()
    })

    it('shows "Save Changes" submit button when pet is set', () => {
        const pet = { id: 1, name: 'Buddy', species: 'Dog', breed: '', age: 3, special_needs: '' }
        const w = mount(PetFormModal, {
            props: { show: true, pet },
            attachTo: document.body,
        })
        const buttons = Array.from(document.body.querySelectorAll('button'))
        const submitBtn = buttons.find(b => b.type === 'submit')
        expect(submitBtn.textContent.trim()).toBe('Save Changes')
        w.unmount()
    })
})
