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

        <div v-if="status" class="mb-4 text-sm font-semibold bg-teal-light border-2 border-ink rounded-xl px-4 py-3">
            {{ status }}
        </div>

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

            <div>
                <div class="flex justify-between mb-1">
                    <label class="block text-sm font-bold">Password</label>
                    <a href="/forgot-password" class="text-xs font-semibold text-teal hover:underline">Forgot password?</a>
                </div>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                />
                <p v-if="form.errors.password" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.password }}</p>
            </div>

            <div class="flex items-center gap-2">
                <input id="remember" v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border-2 border-ink accent-teal" />
                <label for="remember" class="text-sm text-moss">Remember me</label>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-teal text-cream border-3 border-ink py-3 rounded-xl text-sm font-bold shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition disabled:opacity-50"
            >
                {{ form.processing ? 'Signing in...' : 'Sign in' }}
            </button>

            <div class="relative flex items-center">
                <div class="flex-grow border-t-2 border-ink/10"></div>
                <span class="mx-3 text-xs font-bold text-moss uppercase tracking-wide">or</span>
                <div class="flex-grow border-t-2 border-ink/10"></div>
            </div>

            <a
                href="/auth/google"
                class="w-full flex items-center justify-center gap-3 bg-white border-3 border-ink rounded-xl px-3 py-3 text-sm font-bold shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition"
            >
                <img src="/images/google-logo.svg" alt="Google" class="w-[18px] h-[18px]" />
                Continue with Google
            </a>

            <p class="text-center text-sm text-moss">
                Don't have an account?
                <a href="/register" class="font-medium text-ink hover:underline">Register</a>
            </p>
        </form>
    </AuthLayout>
</template>
