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
            <h1 class="text-3xl sm:text-4xl">My profile</h1>
        </template>

        <div class="bg-white border-3 border-ink rounded-2xl shadow-hard p-6 max-w-lg">
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-bold mb-1.5">Name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                    />
                    <p v-if="form.errors.name" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1.5">Email</label>
                    <input
                        :value="user.email"
                        type="email"
                        disabled
                        class="w-full border-2 border-ink/30 rounded-xl px-3.5 py-2.5 text-sm bg-cream text-moss"
                    />
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1.5">Phone</label>
                    <input
                        v-model="form.phone"
                        type="tel"
                        class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                    />
                    <p v-if="form.errors.phone" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.phone }}</p>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1.5">Preferred Location</label>
                    <input
                        v-model="form.preferred_location"
                        type="text"
                        placeholder="e.g. Kuala Lumpur"
                        class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                    />
                    <p v-if="form.errors.preferred_location" class="mt-1.5 text-sm font-semibold text-coral">
                        {{ form.errors.preferred_location }}
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-teal text-cream border-3 border-ink text-sm font-bold px-5 py-2.5 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition disabled:opacity-50"
                    >
                        Save Changes
                    </button>
                    <span v-if="form.recentlySuccessful" class="text-sm font-bold text-teal">Saved!</span>
                </div>
            </form>
        </div>

        <div class="bg-white border-3 border-ink rounded-2xl shadow-hard p-6 max-w-lg mt-6">
            <h2 class="font-display font-bold text-xl">
                {{ hasPassword ? 'Change Password' : 'Set a Password' }}
            </h2>
            <p class="mt-1 text-sm text-moss">
                {{
                    hasPassword
                        ? 'Choose a new password of at least 8 characters.'
                        : 'You signed in with Google. Set a password to also sign in with your email address.'
                }}
            </p>

            <form class="space-y-4 mt-4" @submit.prevent="submitPassword">
                <div v-if="hasPassword">
                    <label class="block text-sm font-bold mb-1.5">Current Password</label>
                    <input
                        v-model="passwordForm.current_password"
                        type="password"
                        autocomplete="current-password"
                        class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                    />
                    <p v-if="passwordForm.errors.current_password" class="mt-1.5 text-sm font-semibold text-coral">
                        {{ passwordForm.errors.current_password }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1.5">New Password</label>
                    <input
                        v-model="passwordForm.password"
                        type="password"
                        autocomplete="new-password"
                        class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                    />
                    <p v-if="passwordForm.errors.password" class="mt-1.5 text-sm font-semibold text-coral">
                        {{ passwordForm.errors.password }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1.5">Confirm New Password</label>
                    <input
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                    />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="passwordForm.processing"
                        class="bg-teal text-cream border-3 border-ink text-sm font-bold px-5 py-2.5 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition disabled:opacity-50"
                    >
                        {{ hasPassword ? 'Update Password' : 'Set Password' }}
                    </button>
                    <span v-if="passwordForm.recentlySuccessful" class="text-sm font-bold text-teal">Saved!</span>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
