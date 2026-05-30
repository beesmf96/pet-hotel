# Plan: Fix Landing.vue Lint Warnings

## Context

Two warnings flagged by the linter agent after the auth nav fix in `resources/js/Pages/Landing.vue`.

## Findings

### 1. `router.post('/logout')` → `useForm().post('/logout')`

**Lines:** 30 (nav Sign out button) and 128 (footer Sign Out button)

The codebase convention (see `VerifyEmail.vue:10,17`) routes all HTTP mutations through `useForm()`, which provides a `processing` flag and consistent error handling. `router.post()` bypasses both.

**Fix — `resources/js/Pages/Landing.vue`:**

Add `useForm` to the import:
```js
import { router, usePage, useForm } from '@inertiajs/vue3';
```

Add after the `user` computed:
```js
const logoutForm = useForm({});
```

Replace both Sign out buttons:
```html
<!-- nav -->
<button
    class="text-sm font-medium bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-full transition"
    :disabled="logoutForm.processing"
    @click="logoutForm.post('/logout')"
>Sign out</button>

<!-- footer -->
<button
    class="hover:text-white transition"
    :disabled="logoutForm.processing"
    @click="logoutForm.post('/logout')"
>Sign Out</button>
```

### 2. Missing `defineOptions({ layout: null })`

Landing.vue renders its own full-page shell (nav + footer inline) and intentionally uses no layout. The convention requires explicitly opting out so the omission is deliberate rather than accidental.

**Fix — add inside `<script setup>`:**
```js
defineOptions({ layout: null });
```

## Verification

1. `bun run build` — no compile errors
2. Visit `/` as guest → Sign in / Register shown
3. Visit `/` as authenticated user → user name / Dashboard / Sign out shown
4. Click Sign out → redirected to `/`, nav reverts to guest state
5. Confirm button is disabled while `logoutForm.processing` is true
