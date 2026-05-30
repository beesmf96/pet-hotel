# Plan: Fix Landing Page Nav Auth State

## Context

After login (email/password or Google OAuth), navigating back to `/` shows "Sign in / Register" in the top-right nav. The root cause: `Landing.vue` has a hardcoded guest-only nav and never reads the `auth.user` prop that `HandleInertiaRequests` shares with every Inertia page.

## Root Cause

`resources/js/Pages/Landing.vue` lines 20–23:
```html
<div class="flex items-center gap-3">
    <a href="/login" ...>Sign in</a>
    <a href="/register" ...>Register</a>
</div>
```
These are static — no `v-if`, no auth check. The `auth.user` prop is available (shared by middleware) but never consumed.

## Fix

**File to change: `resources/js/Pages/Landing.vue`**

### 1. Import `usePage` and derive auth user

```js
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
```

### 2. Replace hardcoded nav with conditional rendering

When authenticated → show user's name + "Dashboard" link + "Sign out" button.  
When guest → show existing "Sign in" + "Register" links.

```html
<div class="flex items-center gap-3">
    <template v-if="user">
        <span class="text-sm text-stone-700">{{ user.name }}</span>
        <a href="/dashboard" class="text-sm font-medium text-stone-700 hover:text-stone-900 transition">Dashboard</a>
        <a href="/logout" method="post" as="button"
           class="text-sm font-medium bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-full transition"
           @click.prevent="router.post('/logout')">
            Sign out
        </a>
    </template>
    <template v-else>
        <a href="/login" class="text-sm font-medium text-stone-700 hover:text-stone-900 transition">Sign in</a>
        <a href="/register" class="text-sm font-medium bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-full transition">Register</a>
    </template>
</div>
```

### 3. Fix footer links (minor)

The footer also hardcodes "Sign In" and "Register" links (lines 112–113). When authenticated, replace them with "Dashboard" and "Sign out" to be consistent.

## Verification

1. Run `composer dev` to start the app
2. Log in (email/password or Google OAuth) → redirected to `/dashboard`
3. Navigate to `/` → top-right should show user name + Dashboard + Sign out
4. Click "Sign out" → redirected to `/` → nav shows "Sign in / Register" again
5. Repeat test for Google OAuth flow
