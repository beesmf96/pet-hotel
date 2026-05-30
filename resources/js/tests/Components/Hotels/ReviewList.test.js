import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'

import ReviewList from '@/Components/Hotels/ReviewList.vue'

const baseReview = {
    id: 1,
    rating: 4,
    comment: 'Great place!',
    user_name: 'Alice',
    created_at: '2025-01-15',
}

describe('ReviewList — summary row', () => {
    it('shows stars and count when averageRating is truthy', () => {
        const w = mount(ReviewList, {
            props: { reviews: [], averageRating: 4.5, reviewsCount: 10 },
        })
        expect(w.text()).toContain('4.5')
        expect(w.text()).toContain('10 reviews')
    })

    it('shows "No reviews yet" when averageRating is null', () => {
        const w = mount(ReviewList, {
            props: { reviews: [], averageRating: null, reviewsCount: 0 },
        })
        expect(w.text()).toContain('No reviews yet')
    })
})

describe('ReviewList — review items', () => {
    it('renders each review in the list', () => {
        const reviews = [baseReview, { ...baseReview, id: 2, user_name: 'Bob', comment: 'Nice stay.' }]
        const w = mount(ReviewList, { props: { reviews, averageRating: 4, reviewsCount: 2 } })
        expect(w.text()).toContain('Alice')
        expect(w.text()).toContain('Bob')
    })

    it('shows comment paragraph when review.comment is non-empty', () => {
        const w = mount(ReviewList, { props: { reviews: [baseReview], averageRating: 4, reviewsCount: 1 } })
        expect(w.text()).toContain('Great place!')
    })

    it('hides comment paragraph when review.comment is null', () => {
        const review = { ...baseReview, comment: null }
        const w = mount(ReviewList, { props: { reviews: [review], averageRating: 4, reviewsCount: 1 } })
        expect(w.text()).not.toContain('Great place!')
    })
})

describe('ReviewList — review count singular vs plural', () => {
    it('shows "1 review" for singular', () => {
        const w = mount(ReviewList, {
            props: { reviews: [], averageRating: 5, reviewsCount: 1 },
        })
        expect(w.text()).toContain('1 review')
        expect(w.text()).not.toContain('1 reviews')
    })

    it('shows "2 reviews" for plural', () => {
        const w = mount(ReviewList, {
            props: { reviews: [], averageRating: 5, reviewsCount: 2 },
        })
        expect(w.text()).toContain('2 reviews')
    })
})
