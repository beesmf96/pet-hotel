# Plan: Add Vitest Spec for Landing.vue Conditional Nav

## Context

The auth-aware nav added to `Landing.vue` has two rendering branches (`v-if="user"` / `v-else`). The PHP feature tests in `LandingPageTest.php` only verify that the server delivers the correct `auth.user` prop — they do not exercise the Vue template. A swapped `v-if`/`v-else` condition would go undetected.

## File to create

`resources/js/tests/Pages/LandingPage.test.js`

## Test cases

### 1. Guest branch — `auth.user` is `null`

Mount `Landing.vue` with `usePage()` returning `{ auth: { user: null } }`.

Assert:
- "Sign in" anchor is rendered
- "Register" anchor is rendered
- "Dashboard" anchor is NOT rendered
- "Sign out" button is NOT rendered

### 2. Authenticated branch — `auth.user` is a user object

Mount `Landing.vue` with `usePage()` returning `{ auth: { user: { id: 1, name: 'Alice' } } }`.

Assert:
- User name "Alice" is rendered
- "Dashboard" anchor is rendered
- "Sign out" button is rendered
- "Sign in" anchor is NOT rendered
- "Register" anchor is NOT rendered

## Setup notes

- Stub `usePage` from `@inertiajs/vue3` to return the desired props shape.
- Stub `router` from `@inertiajs/vue3` (used in `@click` handlers) to avoid errors.
- Stub child components `SearchBar` and `HotelCard` — they are not under test.
- Follow the shape of existing specs in `resources/js/tests/` (see `HotelMap.test.js` and `HotelProfilePage.test.js`).

## Example skeleton

```js
import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import Landing from '@/Pages/Landing.vue'

vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(),
    router: { post: vi.fn(), get: vi.fn() },
    useForm: vi.fn(() => ({ post: vi.fn(), processing: false })),
}))

vi.mock('@/Components/Hotels/SearchBar.vue', () => ({ default: { template: '<div />' } }))
vi.mock('@/Components/Hotels/HotelCard.vue', () => ({ default: { template: '<div />' } }))

import { usePage } from '@inertiajs/vue3'

describe('Landing nav', () => {
    it('shows guest links when unauthenticated', () => {
        usePage.mockReturnValue({ props: { auth: { user: null } } })
        const wrapper = mount(Landing, { props: { featuredHotels: [] } })
        expect(wrapper.find('a[href="/login"]').exists()).toBe(true)
        expect(wrapper.find('a[href="/register"]').exists()).toBe(true)
        expect(wrapper.find('a[href="/dashboard"]').exists()).toBe(false)
        expect(wrapper.find('button').exists()).toBe(false)
    })

    it('shows auth links when authenticated', () => {
        usePage.mockReturnValue({ props: { auth: { user: { id: 1, name: 'Alice' } } } })
        const wrapper = mount(Landing, { props: { featuredHotels: [] } })
        expect(wrapper.text()).toContain('Alice')
        expect(wrapper.find('a[href="/dashboard"]').exists()).toBe(true)
        expect(wrapper.find('button').exists()).toBe(true)
        expect(wrapper.find('a[href="/login"]').exists()).toBe(false)
        expect(wrapper.find('a[href="/register"]').exists()).toBe(false)
    })
})
```

## Verification

Run `bun run test` (Vitest) — both specs should pass.
