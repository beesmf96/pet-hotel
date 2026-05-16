<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: null },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <AuthLayout>
        <template #subtitle>Sign in to your account</template>

        <div v-if="status" class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
            </div>

            <div>
                <div class="flex justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <a href="/forgot-password" class="text-xs text-gray-500 hover:text-gray-900">Forgot password?</a>
                </div>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
            </div>

            <div class="flex items-center gap-2">
                <input id="remember" v-model="form.remember" type="checkbox" class="rounded border-gray-300" />
                <label for="remember" class="text-sm text-gray-600">Remember me</label>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-gray-900 text-white py-2 rounded-lg text-sm font-medium hover:bg-gray-700 disabled:opacity-50"
            >
                {{ form.processing ? 'Signing in...' : 'Sign in' }}
            </button>

            <p class="text-center text-sm text-gray-600">
                Don't have an account?
                <a href="/register" class="font-medium text-gray-900 hover:underline">Register</a>
            </p>
        </form>
    </AuthLayout>
</template>
