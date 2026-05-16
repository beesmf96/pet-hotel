<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const user = computed(() => usePage().props.auth?.user);

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

                        <template v-if="user">
                            <a href="/dashboard" class="text-sm text-gray-600 hover:text-gray-900">Dashboard</a>
                            <a href="/pets" class="text-sm text-gray-600 hover:text-gray-900">My Pets</a>
                            <a href="/profile" class="text-sm text-gray-600 hover:text-gray-900">Profile</a>
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
