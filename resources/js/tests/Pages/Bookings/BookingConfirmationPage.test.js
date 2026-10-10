import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import BookingConfirmationPage from '@/Pages/Bookings/BookingConfirmationPage.vue';

const booking = {
    id: 1,
    hotel: { name: 'Pawsome Stay' },
    pet: { name: 'Buddy' },
    check_in: '2026-10-01',
    check_out: '2026-10-04',
    total_price: '135',
};

describe('BookingConfirmationPage', () => {
    it('summarises the booking with formatted dates and a two-decimal total', () => {
        const w = mount(BookingConfirmationPage, { props: { booking } });
        expect(w.text()).toContain('Booking Request Sent!');
        expect(w.text()).toContain('pending review');
        expect(w.text()).toContain('Pawsome Stay');
        expect(w.text()).toContain('Buddy');
        expect(w.text()).toContain('Thu, Oct 1, 2026');
        expect(w.text()).toContain('Sun, Oct 4, 2026');
        expect(w.text()).toContain('RM 135.00');
    });

    it('links to my bookings and back to the hotel search', () => {
        const w = mount(BookingConfirmationPage, { props: { booking } });
        const hrefs = w.findAll('a').map((a) => a.attributes('href'));
        expect(hrefs).toEqual(expect.arrayContaining(['/bookings', '/hotels']));
    });
});

describe('BookingConfirmationPage — ISO timestamp dates', () => {
    it('formats dates that arrive as full timestamps from the date cast', () => {
        const w = mount(BookingConfirmationPage, {
            props: {
                booking: {
                    ...booking,
                    check_in: '2026-10-01T00:00:00.000000Z',
                    check_out: '2026-10-04T00:00:00.000000Z',
                },
            },
        });
        expect(w.text()).toContain('Thu, Oct 1, 2026');
        expect(w.text()).toContain('Sun, Oct 4, 2026');
        expect(w.text()).not.toContain('Invalid Date');
    });
});

describe('BookingConfirmationPage — check-in and check-out times', () => {
    it('adds the hotel times to the dates', () => {
        const w = mount(BookingConfirmationPage, {
            props: { booking, times: { check_in: '2:00 PM', check_out: '12:00 PM' } },
        });
        expect(w.text()).toContain('Thu, Oct 1, 2026, from 2:00 PM');
        expect(w.text()).toContain('Sun, Oct 4, 2026, by 12:00 PM');
    });

    it('shows the dates alone when the hotel has no times', () => {
        const w = mount(BookingConfirmationPage, { props: { booking } });
        expect(w.text()).not.toContain(', from ');
    });
});
