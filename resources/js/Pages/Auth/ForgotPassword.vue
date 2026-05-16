<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: null },
});

const form = useForm({ email: '' });

function submit() {
    form.post('/forgot-password');
}
</script>

<template>
    <AuthLayout>
        <template #subtitle>Reset your password</template>

        <div v-if="status" class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
            {{ status }}
        </div>

        <p class="mb-5 text-sm text-gray-600">
            Enter your email and we'll send you a password reset link.
        </p>

        <form @submit.prevent="submit" class="space-y-5">
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

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-gray-900 text-white py-2 rounded-lg text-sm font-medium hover:bg-gray-700 disabled:opacity-50"
            >
                {{ form.processing ? 'Sending...' : 'Send reset link' }}
            </button>

            <p class="text-center text-sm text-gray-600">
                <a href="/login" class="font-medium text-gray-900 hover:underline">Back to sign in</a>
            </p>
        </form>
    </AuthLayout>
</template>
