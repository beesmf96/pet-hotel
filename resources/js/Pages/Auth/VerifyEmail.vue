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
            class="mb-4 text-sm font-semibold bg-teal-light border-2 border-ink rounded-xl px-4 py-3"
        >
            A new verification link has been sent to your email address.
        </div>

        <p class="mb-6 text-sm text-moss">
            Thanks for registering! Before getting started, please verify your email address by clicking on the link we
            just sent you. If you didn't receive the email, we'll gladly send another.
        </p>

        <div class="flex flex-col gap-3">
            <button
                :disabled="resendForm.processing"
                class="w-full bg-teal text-cream border-3 border-ink py-3 rounded-xl text-sm font-bold shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition disabled:opacity-50"
                @click="resend"
            >
                {{ resendForm.processing ? 'Sending...' : 'Resend verification email' }}
            </button>

            <button
                :disabled="logoutForm.processing"
                class="w-full text-center text-sm font-semibold text-moss hover:text-teal disabled:opacity-50"
                @click="logout"
            >
                Sign out
            </button>
        </div>
    </AuthLayout>
</template>
