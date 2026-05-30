import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}))
vi.mock('@/Components/Hotels/ReviewList.vue', () => ({
    default: { template: '<div data-testid="review-list" />' },
}))
vi.mock('@inertiajs/vue3', () => ({
    Link: { template: '<a :href="$attrs.href" class="inertia-link"><slot /></a>' },
}))

import ReviewsPage from '@/Pages/Hotels/ReviewsPage.vue'

const hotel = { id: 1, name: 'Paws Inn', slug: 'paws-inn' }

function makeReviews({ lastPage = 1, prevUrl = null, nextUrl = null } = {}) {
    return {
        data: [],
        last_page: lastPage,
        current_page: 1,
        prev_page_url: prevUrl,
        next_page_url: nextUrl,
    }
}

describe('ReviewsPage — pagination visibility', () => {
    it('shows pagination when last_page > 1', () => {
        const reviews = makeReviews({ lastPage: 3, prevUrl: null, nextUrl: '/hotels/paws-inn/reviews?page=2' })
        const w = mount(ReviewsPage, { props: { hotel, reviews, averageRating: null, reviewsCount: 0 } })
        expect(w.text()).toContain('Next →')
    })

    it('hides pagination when last_page is 1', () => {
        const w = mount(ReviewsPage, { props: { hotel, reviews: makeReviews(), averageRating: null, reviewsCount: 0 } })
        expect(w.text()).not.toContain('Page')
    })
})

describe('ReviewsPage — Previous link vs span', () => {
    it('renders Previous as a link when prev_page_url is set', () => {
        const reviews = makeReviews({ lastPage: 3, prevUrl: '/hotels/paws-inn/reviews?page=1', nextUrl: null })
        const w = mount(ReviewsPage, { props: { hotel, reviews, averageRating: null, reviewsCount: 0 } })
        const link = w.find('a.inertia-link')
        expect(link.exists()).toBe(true)
        expect(link.text()).toContain('← Previous')
    })

    it('renders Previous as a plain span when prev_page_url is null', () => {
        const reviews = makeReviews({ lastPage: 3, prevUrl: null, nextUrl: '/hotels/paws-inn/reviews?page=2' })
        const w = mount(ReviewsPage, { props: { hotel, reviews, averageRating: null, reviewsCount: 0 } })
        const spans = w.findAll('span')
        const prevSpan = spans.find(s => s.text().includes('← Previous'))
        expect(prevSpan).toBeTruthy()
    })
})

describe('ReviewsPage — Next link vs span', () => {
    it('renders Next as a link when next_page_url is set', () => {
        const reviews = makeReviews({ lastPage: 3, prevUrl: null, nextUrl: '/hotels/paws-inn/reviews?page=2' })
        const w = mount(ReviewsPage, { props: { hotel, reviews, averageRating: null, reviewsCount: 0 } })
        const links = w.findAll('a.inertia-link')
        const nextLink = links.find(a => a.text().includes('Next →'))
        expect(nextLink).toBeTruthy()
    })

    it('renders Next as a plain span when next_page_url is null', () => {
        const reviews = makeReviews({ lastPage: 3, prevUrl: '/hotels/paws-inn/reviews?page=1', nextUrl: null })
        const w = mount(ReviewsPage, { props: { hotel, reviews, averageRating: null, reviewsCount: 0 } })
        const spans = w.findAll('span')
        const nextSpan = spans.find(s => s.text().includes('Next →'))
        expect(nextSpan).toBeTruthy()
    })
})
