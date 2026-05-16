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
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-gray-900 text-white py-2 rounded-lg text-sm font-medium hover:bg-gray-700 disabled:opacity-50"
            >
                {{ form.processing ? 'Creating account...' : 'Create account' }}
            </button>

            <p class="text-center text-sm text-gray-600">
                Already have an account?
                <a href="/login" class="font-medium text-gray-900 hover:underline">Sign in</a>
            </p>
        </form>
    </AuthLayout>
</template>
