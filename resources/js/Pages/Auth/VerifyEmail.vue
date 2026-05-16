<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: null },
});

const resendForm = useForm({});
const logoutForm = useForm({});

function resend() {
    resendForm.post('/email/verification-notification');
}

function logout() {
    logoutForm.post('/logout');
}
</script>

<template>
    <AuthLayout>
        <template #subtitle>Verify your email</template>

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3"
        >
            A new verification link has been sent to your email address.
        </div>

        <p class="mb-6 text-sm text-gray-600">
            Thanks for registering! Before getting started, please verify your email address by
            clicking on the link we just sent you. If you didn't receive the email, we'll gladly
            send another.
        </p>

        <div class="flex flex-col gap-3">
            <button
                @click="resend"
                :disabled="resendForm.processing"
                class="w-full bg-gray-900 text-white py-2 rounded-lg text-sm font-medium hover:bg-gray-700 disabled:opacity-50"
            >
                {{ resendForm.processing ? 'Sending...' : 'Resend verification email' }}
            </button>

            <button
                @click="logout"
                :disabled="logoutForm.processing"
                class="w-full text-center text-sm text-gray-600 hover:text-gray-900 disabled:opacity-50"
            >
                Sign out
            </button>
        </div>
    </AuthLayout>
</template>
