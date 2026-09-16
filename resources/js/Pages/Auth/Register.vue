<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useForm } from '@inertiajs/vue3';
import FormField from '@/Components/Ui/FormField.vue';
import TextInput from '@/Components/Ui/TextInput.vue';
import UiButton from '@/Components/Ui/UiButton.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <AuthLayout>
        <template #subtitle>Create your account</template>

        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Name" :error="form.errors.name">
                <TextInput v-model="form.name" type="text" autocomplete="name" />
            </FormField>

            <FormField label="Email" :error="form.errors.email">
                <TextInput v-model="form.email" type="email" autocomplete="email" />
            </FormField>

            <FormField label="Password" :error="form.errors.password">
                <TextInput v-model="form.password" type="password" autocomplete="new-password" />
            </FormField>

            <FormField label="Confirm Password">
                <TextInput v-model="form.password_confirmation" type="password" autocomplete="new-password" />
            </FormField>

            <UiButton block type="submit" :disabled="form.processing">
                {{ form.processing ? 'Creating account...' : 'Create account' }}
            </UiButton>

            <div class="relative flex items-center">
                <div class="flex-grow border-t-2 border-ink/10"></div>
                <span class="mx-3 text-xs font-bold text-moss uppercase tracking-wide">or</span>
                <div class="flex-grow border-t-2 border-ink/10"></div>
            </div>

            <UiButton as="a" variant="white" block href="/auth/google">
                <img src="/images/google-logo.svg" alt="Google" class="w-[18px] h-[18px]" />
                Continue with Google
            </UiButton>

            <p class="text-center text-sm text-moss">
                Already have an account?
                <a href="/login" class="font-medium text-ink hover:underline">Sign in</a>
            </p>
        </form>
    </AuthLayout>
</template>
