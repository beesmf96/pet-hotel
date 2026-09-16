<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-3xl sm:text-4xl">Dashboard</h1>
        </template>

        <div class="bg-white border-3 border-ink rounded-2xl shadow-hard p-6">
            <h2 class="font-display font-bold text-2xl mb-2">Welcome back, {{ user.name }}!</h2>
            <p class="text-moss">You're logged in as {{ user.email }}.</p>
        </div>
    </AppLayout>
</template>
