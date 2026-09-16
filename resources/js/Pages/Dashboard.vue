<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SectionTitle from '@/Components/Ui/SectionTitle.vue';

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-3xl sm:text-4xl">Dashboard</h1>
        </template>

        <div class="card-hard p-6">
            <SectionTitle class="mb-2">Welcome back, {{ user.name }}!</SectionTitle>
            <p class="text-moss">You're logged in as {{ user.email }}.</p>
        </div>
    </AppLayout>
</template>
