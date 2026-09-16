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

        <div v-if="status" class="mb-4 text-sm font-semibold bg-teal-light border-2 border-ink rounded-xl px-4 py-3">
            {{ status }}
        </div>

        <p class="mb-5 text-sm text-moss">Enter your email and we'll send you a password reset link.</p>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-bold mb-1.5">Email</label>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                />
                <p v-if="form.errors.email" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.email }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-teal text-cream border-3 border-ink py-3 rounded-xl text-sm font-bold shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition disabled:opacity-50"
            >
                {{ form.processing ? 'Sending...' : 'Send reset link' }}
            </button>

            <p class="text-center text-sm text-moss">
                <a href="/login" class="font-medium text-ink hover:underline">Back to sign in</a>
            </p>
        </form>
    </AuthLayout>
</template>
