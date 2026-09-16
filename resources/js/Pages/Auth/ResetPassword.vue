<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useForm } from '@inertiajs/vue3';
import FormField from '@/Components/Ui/FormField.vue';
import TextInput from '@/Components/Ui/TextInput.vue';
import UiButton from '@/Components/Ui/UiButton.vue';

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <AuthLayout>
        <template #subtitle>Choose a new password</template>

        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Email" :error="form.errors.email">
                <TextInput v-model="form.email" type="email" autocomplete="email" />
            </FormField>

            <FormField label="New Password" :error="form.errors.password">
                <TextInput v-model="form.password" type="password" autocomplete="new-password" />
            </FormField>

            <FormField label="Confirm New Password">
                <TextInput v-model="form.password_confirmation" type="password" autocomplete="new-password" />
            </FormField>

            <UiButton block type="submit" :disabled="form.processing">
                {{ form.processing ? 'Resetting...' : 'Reset password' }}
            </UiButton>
        </form>
    </AuthLayout>
</template>
