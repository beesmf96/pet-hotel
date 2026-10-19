<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import NotificationBell from '@/Components/NotificationBell.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import BrandMark from '@/Components/Ui/BrandMark.vue';
import Notice from '@/Components/Ui/Notice.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const unreadCount = computed(() => page.props.unread_notifications_count ?? 0);

// One list for both nav rows: inline next to the brand from sm up, and on a
// second row on phones, where the first row only has room for the account buttons.
const navLinks = computed(() => [
    { href: '/hotels', label: 'Find hotels' },
    ...(user.value
        ? [
              { href: '/pets', label: 'My pets' },
              { href: '/bookings', label: 'My bookings' },
              { href: '/profile', label: 'Profile' },
          ]
        : []),
]);

// A flash from the last action, shown over the page so it is seen even when
// the visit kept the scroll position. Watching the flash object, not its text,
// shows the same message again after a repeated action.
const message = ref(null);
let hideTimer;

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash?.success) return;
        message.value = flash.success;
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => (message.value = null), 5000);
    },
    { immediate: true },
);

function dismiss() {
    clearTimeout(hideTimer);
    message.value = null;
}

onBeforeUnmount(() => clearTimeout(hideTimer));

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-cream text-ink font-sans">
        <nav class="bg-cream border-b-3 border-ink">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center gap-4">
                    <BrandMark size="sm" />

                    <div class="flex items-center gap-3 sm:gap-5 text-sm font-semibold">
                        <slot name="nav" />

                        <Link
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            class="hidden sm:inline whitespace-nowrap hover:text-teal transition"
                            >{{ link.label }}</Link
                        >

                        <template v-if="user">
                            <NotificationBell :unread-count="unreadCount" />
                            <button class="whitespace-nowrap hover:text-teal transition" @click="logout">Sign out</button>
                        </template>
                        <template v-else>
                            <Link href="/login" class="whitespace-nowrap hover:text-teal transition">Sign in</Link>
                            <UiButton as="link" variant="mustard" size="sm" href="/register"> Register </UiButton>
                        </template>
                    </div>
                </div>

                <div data-testid="phone-nav" class="sm:hidden flex justify-between gap-3 pb-3 text-sm font-semibold overflow-x-auto">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="whitespace-nowrap hover:text-teal transition"
                        >{{ link.label }}</Link
                    >
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-cream">
            <div class="max-w-7xl mx-auto pt-8 pb-2 px-4 sm:px-6 lg:px-8 font-display font-extrabold tracking-tight leading-tight [&_h1]:text-3xl sm:[&_h1]:text-4xl">
                <slot name="header" />
            </div>
        </header>

        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <slot />
        </main>

        <div v-if="message" class="fixed top-20 inset-x-4 sm:inset-x-auto sm:right-6 sm:w-96 z-50" role="status">
            <Notice class="flex items-start justify-between gap-3">
                <span>{{ message }}</span>
                <button type="button" class="leading-none text-lg" aria-label="Dismiss" @click="dismiss">
                    &times;
                </button>
            </Notice>
        </div>
    </div>
</template>
