<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useForm } from '@inertiajs/vue3';

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
            <div>
                <label class="block text-sm font-bold mb-1.5">Name</label>
                <input
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                />
                <p v-if="form.errors.name" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.name }}</p>
            </div>

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
                <label class="block text-sm font-bold mb-1.5">Password</label>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                />
                <p v-if="form.errors.password" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="block text-sm font-bold mb-1.5">Confirm Password</label>
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-teal text-cream border-3 border-ink py-3 rounded-xl text-sm font-bold shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition disabled:opacity-50"
            >
                {{ form.processing ? 'Creating account...' : 'Create account' }}
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
                Already have an account?
                <a href="/login" class="font-medium text-ink hover:underline">Sign in</a>
            </p>
        </form>
    </AuthLayout>
</template>
