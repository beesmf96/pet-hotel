import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}));
vi.mock('@/Components/Hotels/HotelCard.vue', () => ({
    default: { template: '<div data-testid="hotel-card" />' },
}));
vi.mock('@/Components/Hotels/SearchBar.vue', () => ({
    default: { name: 'SearchBar', emits: ['search'], template: '<div />' },
}));
vi.mock('@/Components/Hotels/FilterSidebar.vue', () => ({
    default: { name: 'FilterSidebar', emits: ['apply'], template: '<div />' },
}));
vi.mock('@inertiajs/vue3', () => ({
    router: { get: vi.fn(), visit: vi.fn() },
}));

import { router } from '@inertiajs/vue3';
import SearchPage from '@/Pages/Hotels/SearchPage.vue';

beforeEach(() => vi.clearAllMocks());

const makeHotels = (count, lastPage = 1) => ({
    data: Array.from({ length: count }, (_, i) => ({
        id: i + 1,
        name: `Hotel ${i + 1}`,
        slug: `hotel-${i + 1}`,
        city: 'Singapore',
        cover_photo_url: null,
        facilities: [],
        price_from: null,
        reviews_avg_rating: null,
    })),
    total: count,
    last_page: lastPage,
    links: [],
});

const baseFilters = { sort: 'latest' };

describe('SearchPage — hotel grid vs empty state', () => {
    it('renders hotel cards when hotels.data has entries', () => {
        const w = mount(SearchPage, { props: { hotels: makeHotels(3), filters: baseFilters } });
        expect(w.findAll('[data-testid="hotel-card"]').length).toBe(3);
    });

    it('shows empty state when hotels.data is empty', () => {
        const w = mount(SearchPage, { props: { hotels: makeHotels(0), filters: baseFilters } });
        expect(w.text()).toContain('No hotels found');
    });
});

describe('SearchPage — pagination', () => {
    it('renders pagination buttons when last_page > 1', () => {
        const hotels = makeHotels(20, 3);
        hotels.links = [
            { label: '&laquo; Previous', url: null, active: false },
            { label: '1', url: '/hotels?page=1', active: true },
            { label: '2', url: '/hotels?page=2', active: false },
            { label: 'Next &raquo;', url: '/hotels?page=2', active: false },
        ];
        const w = mount(SearchPage, { props: { hotels, filters: baseFilters } });
        expect(w.find('button[disabled]').exists()).toBe(true);
    });

    it('does not render pagination when last_page is 1', () => {
        const hotels = makeHotels(3, 1);
        const w = mount(SearchPage, { props: { hotels, filters: baseFilters } });
        // Pagination buttons are inside the v-if block — check no disabled nav buttons
        const buttons = w.findAll('button');
        const paginationButtons = buttons.filter((b) => ['«', '»', '1', '2'].includes(b.text().trim()));
        expect(paginationButtons.length).toBe(0);
    });
});

describe('SearchPage — search, filters, and sort', () => {
    const filters = { sort: 'latest', city: 'Singapore' };

    it('a search from the SearchBar reloads the page with the new params', async () => {
        const w = mount(SearchPage, { props: { hotels: makeHotels(1), filters } });
        await w.findComponent({ name: 'SearchBar' }).vm.$emit('search', { q: 'paws', city: 'Bedok' });
        expect(router.get).toHaveBeenCalledWith(
            '/hotels',
            { sort: 'latest', city: 'Bedok', q: 'paws' },
            { preserveState: false },
        );
    });

    it('applying filters keeps page state and scroll', async () => {
        const w = mount(SearchPage, { props: { hotels: makeHotels(1), filters } });
        await w.findComponent({ name: 'FilterSidebar' }).vm.$emit('apply', { pet_type: 'cat' });
        expect(router.get).toHaveBeenCalledWith(
            '/hotels',
            { pet_type: 'cat', sort: 'latest' },
            { preserveState: true, preserveScroll: true },
        );
    });

    it('changing the sort reloads with the chosen order', async () => {
        const w = mount(SearchPage, { props: { hotels: makeHotels(1), filters } });
        await w.find('select').setValue('price_desc');
        expect(router.get).toHaveBeenCalledWith(
            '/hotels',
            { sort: 'price_desc', city: 'Singapore' },
            { preserveState: true, preserveScroll: true },
        );
    });

    it('starts from the sort in the filters prop', () => {
        const w = mount(SearchPage, { props: { hotels: makeHotels(1), filters: { sort: 'price_asc' } } });
        expect(w.find('select').element.value).toBe('price_asc');
    });

    it('shows singular "hotel" for a single result', () => {
        const w = mount(SearchPage, { props: { hotels: makeHotels(1), filters } });
        expect(w.text()).toContain('1 hotel found');
    });
});

describe('SearchPage — pagination clicks', () => {
    const paged = () => {
        const hotels = makeHotels(20, 3);
        hotels.links = [
            { label: '&laquo; Previous', url: null, active: false },
            { label: '1', url: '/hotels?page=1', active: true },
            { label: '2', url: '/hotels?page=2', active: false },
            { label: 'Next &raquo;', url: '/hotels?page=2', active: false },
        ];
        return hotels;
    };

    it('visits the link url when a page button is clicked', async () => {
        const w = mount(SearchPage, { props: { hotels: paged(), filters: baseFilters } });
        await w
            .findAll('button')
            .find((b) => b.text() === '2')
            .trigger('click');
        expect(router.visit).toHaveBeenCalledWith('/hotels?page=2');
    });

    it('does not navigate for a link with no url', async () => {
        const w = mount(SearchPage, { props: { hotels: paged(), filters: baseFilters } });
        await w.find('button[disabled]').trigger('click');
        expect(router.visit).not.toHaveBeenCalled();
    });

    it('decodes the laquo/raquo entities in labels', () => {
        const w = mount(SearchPage, { props: { hotels: paged(), filters: baseFilters } });
        const labels = w.findAll('button').map((b) => b.text());
        expect(labels).toContain('« Previous');
        expect(labels).toContain('Next »');
    });
});

describe('SearchPage — empty filters prop', () => {
    it('defaults the sort to latest when filters arrive as an empty array', () => {
        const w = mount(SearchPage, { props: { hotels: makeHotels(1), filters: [] } });
        expect(w.find('select').element.value).toBe('latest');
    });
});
