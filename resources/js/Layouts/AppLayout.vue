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
    <div class="min-h-screen bg-gray-50">
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <a href="/" class="text-xl font-semibold text-gray-900">Pet Hotel</a>

                    <div class="flex items-center gap-4">
                        <slot name="nav" />

                        <a href="/hotels" class="text-sm text-gray-600 hover:text-gray-900">Find Hotels</a>

                        <template v-if="user">
                            <a href="/pets" class="text-sm text-gray-600 hover:text-gray-900">My Pets</a>
                            <a href="/bookings" class="text-sm text-gray-600 hover:text-gray-900">My Bookings</a>
                            <a href="/profile" class="text-sm text-gray-600 hover:text-gray-900">Profile</a>
                            <NotificationBell :unread-count="unreadCount" />
                            <button class="text-sm text-gray-600 hover:text-gray-900" @click="logout">Sign out</button>
                        </template>
                        <template v-else>
                            <a href="/login" class="text-sm text-gray-600 hover:text-gray-900">Sign in</a>
                            <a
                                href="/register"
                                class="text-sm bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-700"
                            >
                                Register
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
