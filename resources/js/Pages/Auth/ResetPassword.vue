<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { useForm } from '@inertiajs/vue3';

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
                <label class="block text-sm font-bold mb-1.5">New Password</label>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                />
                <p v-if="form.errors.password" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="block text-sm font-bold mb-1.5">Confirm New Password</label>
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
                {{ form.processing ? 'Resetting...' : 'Reset password' }}
            </button>
        </form>
    </AuthLayout>
</template>
