<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useForm } from '@inertiajs/vue3';
import UiButton from '@/Components/Ui/UiButton.vue';
import Notice from '@/Components/Ui/Notice.vue';

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

        <Notice v-if="status === 'verification-link-sent'" class="mb-4"
            >A new verification link has been sent to your email address.</Notice
        >

        <p class="mb-6 text-sm text-moss">
            Thanks for registering! Before getting started, please verify your email address by clicking on the link we
            just sent you. If you didn't receive the email, we'll gladly send another.
        </p>

        <div class="flex flex-col gap-3">
            <UiButton block :disabled="resendForm.processing" @click="resend">
                {{ resendForm.processing ? 'Sending...' : 'Resend verification email' }}
            </UiButton>

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
