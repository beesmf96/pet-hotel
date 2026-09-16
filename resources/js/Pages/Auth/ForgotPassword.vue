<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useForm } from '@inertiajs/vue3';
import FormField from '@/Components/Ui/FormField.vue';
import TextInput from '@/Components/Ui/TextInput.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import Notice from '@/Components/Ui/Notice.vue';

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

        <Notice v-if="status" class="mb-4">{{ status }}</Notice>

        <p class="mb-5 text-sm text-moss">Enter your email and we'll send you a password reset link.</p>

        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Email" :error="form.errors.email">
                <TextInput v-model="form.email" type="email" autocomplete="email" />
            </FormField>

            <UiButton block type="submit" :disabled="form.processing">
                {{ form.processing ? 'Sending...' : 'Send reset link' }}
            </UiButton>

            <p class="text-center text-sm text-moss">
                <a href="/login" class="font-medium text-ink hover:underline">Back to sign in</a>
            </p>
        </form>
    </AuthLayout>
</template>
