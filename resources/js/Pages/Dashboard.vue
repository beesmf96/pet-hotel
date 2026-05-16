<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-2">
                Welcome back, {{ user.name }}!
            </h2>
            <p class="text-sm text-gray-600">
                You're logged in as {{ user.email }}.
            </p>
        </div>
    </AppLayout>
</template>
