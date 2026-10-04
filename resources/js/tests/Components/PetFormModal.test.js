import { mount, DOMWrapper } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'

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

const pet = { id: 7, name: 'Buddy', species: 'dog', breed: 'Labrador', age: 3, special_needs: 'Insulin' }

// The dialog is teleported to <body>, so query the document rather than the wrapper.
const body = () => new DOMWrapper(document.body)

beforeEach(() => {
    vi.clearAllMocks()
    Object.assign(formState, {
        name: '', species: '', breed: '', age: '', special_needs: '', photo: null, errors: {},
    })
})

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
        const pet = { id: 1, name: 'Buddy', species: 'dog', breed: '', age: 3, special_needs: '' }
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
        const pet = { id: 1, name: 'Buddy', species: 'dog', breed: '', age: 3, special_needs: '' }
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

describe('PetFormModal — species select', () => {
    it('offers the fixed pet type list instead of free text', () => {
        const w = mount(PetFormModal, { props: { show: true, pet: null }, attachTo: document.body })
        const select = body().find('select')
        expect(select.exists()).toBe(true)
        const options = select.findAll('option').map((o) => o.text())
        expect(options).toEqual(['Choose a species…', 'Dog', 'Cat', 'Rabbit', 'Bird', 'Other'])
        expect(select.findAll('option').map((o) => o.element.value)).toEqual(['', 'dog', 'cat', 'rabbit', 'bird', 'other'])
        w.unmount()
    })
})

describe('PetFormModal — filling the form from the pet prop', () => {
    it('copies the pet fields into the form when editing', () => {
        const w = mount(PetFormModal, { props: { show: true, pet }, attachTo: document.body })
        expect(formState.name).toBe('Buddy')
        expect(formState.species).toBe('dog')
        expect(formState.breed).toBe('Labrador')
        expect(formState.age).toBe(3)
        expect(formState.special_needs).toBe('Insulin')
        expect(formState.photo).toBeNull()
        w.unmount()
    })

    it('blanks the form when the pet prop goes back to null', async () => {
        const w = mount(PetFormModal, { props: { show: true, pet }, attachTo: document.body })
        await w.setProps({ pet: null })
        expect(formState.name).toBe('')
        expect(formState.species).toBe('')
        expect(formState.age).toBe('')
        w.unmount()
    })
})

describe('PetFormModal — submit', () => {
    it('posts to /pets when adding and closes on success', async () => {
        const w = mount(PetFormModal, { props: { show: true, pet: null }, attachTo: document.body })
        await body().find('form').trigger('submit')
        expect(formState.post).toHaveBeenCalledWith('/pets', expect.any(Object))
        expect(formState.patch).not.toHaveBeenCalled()

        formState.post.mock.calls[0][1].onSuccess()
        expect(w.emitted('close')).toHaveLength(1)
        w.unmount()
    })

    it('patches /pets/{id} when editing and closes on success', async () => {
        const w = mount(PetFormModal, { props: { show: true, pet }, attachTo: document.body })
        await body().find('form').trigger('submit')
        expect(formState.patch).toHaveBeenCalledWith('/pets/7', expect.any(Object))
        expect(formState.post).not.toHaveBeenCalled()

        formState.patch.mock.calls[0][1].onSuccess()
        expect(w.emitted('close')).toHaveLength(1)
        w.unmount()
    })

    it('disables the submit button while processing', () => {
        formState.processing = true
        const w = mount(PetFormModal, { props: { show: true, pet: null }, attachTo: document.body })
        expect(body().find('button[type="submit"]').attributes('disabled')).toBeDefined()
        formState.processing = false
        w.unmount()
    })
})

describe('PetFormModal — closing', () => {
    it('Cancel resets the form, clears errors, and emits close', async () => {
        const w = mount(PetFormModal, { props: { show: true, pet: null }, attachTo: document.body })
        const cancel = body().findAll('button').find((b) => b.text() === 'Cancel')
        await cancel.trigger('click')
        expect(formState.reset).toHaveBeenCalledTimes(1)
        expect(formState.clearErrors).toHaveBeenCalledTimes(1)
        expect(w.emitted('close')).toHaveLength(1)
        w.unmount()
    })

    it('clicking the backdrop closes the modal', async () => {
        const w = mount(PetFormModal, { props: { show: true, pet: null }, attachTo: document.body })
        await body().find('.fixed.inset-0').trigger('click')
        expect(w.emitted('close')).toHaveLength(1)
        w.unmount()
    })

    it('clicking inside the dialog does not close it', async () => {
        const w = mount(PetFormModal, { props: { show: true, pet: null }, attachTo: document.body })
        await body().find('form').trigger('click')
        expect(w.emitted('close')).toBeUndefined()
        w.unmount()
    })
})

describe('PetFormModal — photo and errors', () => {
    it('stores the chosen file on the form', async () => {
        const w = mount(PetFormModal, { props: { show: true, pet: null }, attachTo: document.body })
        const file = new File(['x'], 'buddy.jpg', { type: 'image/jpeg' })
        const input = body().find('input[type="file"]')
        Object.defineProperty(input.element, 'files', { value: [file] })
        await input.trigger('change')
        expect(formState.photo).toBe(file)
        w.unmount()
    })

    it('renders a validation error under each field that has one', () => {
        formState.errors = {
            name: 'Name is required.',
            species: 'Species is required.',
            breed: 'Breed is too long.',
            age: 'Age must be a number.',
            special_needs: 'Too long.',
            photo: 'Must be an image.',
        }
        const w = mount(PetFormModal, { props: { show: true, pet: null }, attachTo: document.body })
        for (const msg of Object.values(formState.errors)) {
            expect(document.body.textContent).toContain(msg)
        }
        w.unmount()
    })
})
