import { mount, DOMWrapper } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { reactive } from 'vue';

// Reactive like the real useForm, so star clicks re-render the template.
const form = reactive({ booking_id: null, rating: 0, comment: '', processing: false, errors: {}, post: vi.fn() });
const useForm = vi.fn((fields) => Object.assign(form, fields));

vi.mock('@inertiajs/vue3', () => ({ useForm: (fields) => useForm(fields) }));

import LeaveReviewModal from '@/Components/Hotels/LeaveReviewModal.vue';

const booking = { id: 42, hotel: { slug: 'pawsome-stay' } };

// The dialog is teleported to <body>, so query the document rather than the wrapper.
const body = () => new DOMWrapper(document.body);
const stars = () =>
    body()
        .findAll('button')
        .filter((b) => b.text() === '★');
const submitBtn = () => body().find('button[type="submit"]');

const mountOpen = () => mount(LeaveReviewModal, { props: { booking, show: true }, attachTo: document.body });

beforeEach(() => {
    vi.clearAllMocks();
    Object.assign(form, { rating: 0, comment: '', processing: false, errors: {} });
});

describe('LeaveReviewModal — visibility', () => {
    it('renders nothing when show is false', () => {
        const w = mount(LeaveReviewModal, { props: { booking, show: false }, attachTo: document.body });
        expect(body().find('form').exists()).toBe(false);
        w.unmount();
    });

    it('seeds the form with the booking id', () => {
        const w = mountOpen();
        expect(useForm).toHaveBeenCalledWith(expect.objectContaining({ booking_id: 42, rating: 0 }));
        w.unmount();
    });
});

describe('LeaveReviewModal — star picker', () => {
    it('renders five grey stars and disables submit until one is chosen', () => {
        const w = mountOpen();
        expect(stars()).toHaveLength(5);
        expect(stars().every((s) => s.classes().includes('text-ink/15'))).toBe(true);
        expect(submitBtn().attributes('disabled')).toBeDefined();
        w.unmount();
    });

    it('clicking a star sets the rating and lights the stars up to it', async () => {
        const w = mountOpen();
        await stars()[3].trigger('click');
        expect(form.rating).toBe(4);
        const lit = stars().filter((s) => s.classes().includes('text-mustard'));
        expect(lit).toHaveLength(4);
        expect(submitBtn().attributes('disabled')).toBeUndefined();
        w.unmount();
    });

    it('hovering previews a rating and leaving restores the chosen one', async () => {
        const w = mountOpen();
        await stars()[0].trigger('click');
        await stars()[4].trigger('mouseenter');
        expect(stars().filter((s) => s.classes().includes('text-mustard'))).toHaveLength(5);
        await stars()[4].trigger('mouseleave');
        expect(stars().filter((s) => s.classes().includes('text-mustard'))).toHaveLength(1);
        w.unmount();
    });
});

describe('LeaveReviewModal — submit and close', () => {
    it('posts to the hotel reviews endpoint and closes on success', async () => {
        const w = mountOpen();
        await stars()[4].trigger('click');
        await body().find('textarea').setValue('Great stay');
        await body().find('form').trigger('submit');
        expect(form.comment).toBe('Great stay');
        expect(form.post).toHaveBeenCalledWith('/hotels/pawsome-stay/reviews', expect.any(Object));
        form.post.mock.calls[0][1].onSuccess();
        expect(w.emitted('close')).toHaveLength(1);
        w.unmount();
    });

    it('disables submit while processing even with a rating', async () => {
        const w = mountOpen();
        form.rating = 3;
        await w.vm.$nextTick();
        expect(submitBtn().attributes('disabled')).toBeUndefined();
        form.processing = true;
        await w.vm.$nextTick();
        expect(submitBtn().attributes('disabled')).toBeDefined();
        w.unmount();
    });

    it('shows rating and comment errors', async () => {
        const w = mountOpen();
        form.errors = { rating: 'Pick a rating.', comment: 'Too long.' };
        await w.vm.$nextTick();
        expect(document.body.textContent).toContain('Pick a rating.');
        expect(document.body.textContent).toContain('Too long.');
        w.unmount();
    });

    it('closes from the × button, Cancel, and the backdrop, but not from inside', async () => {
        const w = mountOpen();
        await body()
            .findAll('button')
            .find((b) => b.text() === '×')
            .trigger('click');
        await body()
            .findAll('button')
            .find((b) => b.text() === 'Cancel')
            .trigger('click');
        await body().find('.fixed.inset-0').trigger('click');
        await body().find('form').trigger('click');
        expect(w.emitted('close')).toHaveLength(3);
        w.unmount();
    });
});
