<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import NotificationBell from '@/Components/NotificationBell.vue';

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
                    <a href="/" class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-teal flex items-center justify-center text-cream">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <circle cx="5.5" cy="9" r="2" />
                                <circle cx="9.5" cy="5" r="2" />
                                <circle cx="14.5" cy="5" r="2" />
                                <circle cx="18.5" cy="9" r="2" />
                                <path d="M12 11c-3 0-6 3-6 6 0 1.7 1.3 3 3 3 1 0 2-.5 3-.5s2 .5 3 .5c1.7 0 3-1.3 3-3 0-3-3-6-6-6z" />
                            </svg>
                        </span>
                        <span class="font-display font-extrabold text-xl">PetHotel</span>
                    </a>

                    <div class="flex items-center gap-3 sm:gap-5 text-sm font-semibold">
                        <slot name="nav" />

                        <a href="/hotels" class="hidden sm:inline whitespace-nowrap hover:text-teal transition">Find Hotels</a>

                        <template v-if="user">
                            <a href="/pets" class="hidden sm:inline hover:text-teal transition">My Pets</a>
                            <a href="/bookings" class="whitespace-nowrap hover:text-teal transition">My Bookings</a>
                            <a href="/profile" class="hidden sm:inline hover:text-teal transition">Profile</a>
                            <NotificationBell :unread-count="unreadCount" />
                            <button class="hover:text-teal transition" @click="logout">Sign out</button>
                        </template>
                        <template v-else>
                            <a href="/login" class="whitespace-nowrap hover:text-teal transition">Sign in</a>
                            <a
                                href="/register"
                                class="bg-mustard border-3 border-ink px-4 py-1.5 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition"
                            >
                                Register
                            </a>
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
