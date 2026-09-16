<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import NotificationBell from '@/Components/NotificationBell.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import BrandMark from '@/Components/Ui/BrandMark.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const unreadCount = computed(() => page.props.unread_notifications_count ?? 0);

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

                        <a href="/hotels" class="hidden sm:inline whitespace-nowrap hover:text-teal transition"
                            >Find Hotels</a
                        >

                        <template v-if="user">
                            <a href="/pets" class="hidden sm:inline hover:text-teal transition">My Pets</a>
                            <a href="/bookings" class="whitespace-nowrap hover:text-teal transition">My Bookings</a>
                            <a href="/profile" class="hidden sm:inline hover:text-teal transition">Profile</a>
                            <NotificationBell :unread-count="unreadCount" />
                            <button class="hover:text-teal transition" @click="logout">Sign out</button>
                        </template>
                        <template v-else>
                            <a href="/login" class="whitespace-nowrap hover:text-teal transition">Sign in</a>
                            <UiButton as="a" variant="mustard" size="sm" href="/register"> Register </UiButton>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-cream">
            <div class="max-w-7xl mx-auto pt-8 pb-2 px-4 sm:px-6 lg:px-8 font-display font-extrabold tracking-tight">
                <slot name="header" />
            </div>
        </header>

        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
