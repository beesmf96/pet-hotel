<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    hasPassword: {
        type: Boolean,
        default: true,
    },
});

const form = useForm({
    name: props.user.name,
    phone: props.user.phone ?? '',
    preferred_location: props.user.preferred_location ?? '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.patch('/profile');
}

function submitPassword() {
    passwordForm.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => passwordForm.reset('current_password', 'password', 'password_confirmation'),
    });
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900">My Profile</h1>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-lg">
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input
                        :value="user.email"
                        type="email"
                        disabled
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input
                        v-model="form.phone"
                        type="tel"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                    />
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Location</label>
                    <input
                        v-model="form.preferred_location"
                        type="text"
                        placeholder="e.g. Kuala Lumpur"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                    />
                    <p v-if="form.errors.preferred_location" class="mt-1 text-xs text-red-600">
                        {{ form.errors.preferred_location }}
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-gray-900 text-white text-sm px-5 py-2 rounded-lg hover:bg-gray-700 disabled:opacity-50"
                    >
                        Save Changes
                    </button>
                    <span v-if="form.recentlySuccessful" class="text-sm text-green-600">Saved!</span>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-lg mt-6">
            <h2 class="text-sm font-semibold text-gray-900">
                {{ hasPassword ? 'Change Password' : 'Set a Password' }}
            </h2>
            <p class="mt-1 text-xs text-gray-500">
                {{
                    hasPassword
                        ? 'Choose a new password of at least 8 characters.'
                        : 'You signed in with Google. Set a password to also sign in with your email address.'
                }}
            </p>

            <form class="space-y-4 mt-4" @submit.prevent="submitPassword">
                <div v-if="hasPassword">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input
                        v-model="passwordForm.current_password"
                        type="password"
                        autocomplete="current-password"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                    />
                    <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs text-red-600">
                        {{ passwordForm.errors.current_password }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input
                        v-model="passwordForm.password"
                        type="password"
                        autocomplete="new-password"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                    />
                    <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-red-600">
                        {{ passwordForm.errors.password }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                    />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="passwordForm.processing"
                        class="bg-gray-900 text-white text-sm px-5 py-2 rounded-lg hover:bg-gray-700 disabled:opacity-50"
                    >
                        {{ hasPassword ? 'Update Password' : 'Set Password' }}
                    </button>
                    <span v-if="passwordForm.recentlySuccessful" class="text-sm text-green-600">Saved!</span>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
