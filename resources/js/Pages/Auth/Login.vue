<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import FormField from '@/Components/Ui/FormField.vue';
import TextInput from '@/Components/Ui/TextInput.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import Notice from '@/Components/Ui/Notice.vue';

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

        <Notice v-if="status" class="mb-4">{{ status }}</Notice>

        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Email" :error="form.errors.email">
                <TextInput v-model="form.email" type="email" autocomplete="email" />
            </FormField>

            <FormField label="Password" :error="form.errors.password">
                <template #aside>
                    <Link href="/forgot-password" class="link-bold text-teal"
                        >Forgot password?</Link
                    >
                </template>
                <TextInput v-model="form.password" type="password" autocomplete="current-password" />
            </FormField>

            <div class="flex items-center gap-2">
                <input
                    id="remember"
                    v-model="form.remember"
                    type="checkbox"
                    class="w-4 h-4 rounded border-2 border-ink accent-teal"
                />
                <label for="remember" class="text-sm text-moss">Remember me</label>
            </div>

            <UiButton block type="submit" :disabled="form.processing">
                {{ form.processing ? 'Signing in…' : 'Sign in' }}
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
                Don't have an account?
                <Link href="/register" class="link-bold text-teal">Register</Link>
            </p>
        </form>
    </AuthLayout>
</template>
