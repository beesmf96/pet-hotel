<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import FormField from '@/Components/Ui/FormField.vue';
import TextInput from '@/Components/Ui/TextInput.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import SectionTitle from '@/Components/Ui/SectionTitle.vue';

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

        <div class="card-hard p-6 max-w-lg">
            <form class="space-y-4" @submit.prevent="submit">
                <FormField label="Name" :error="form.errors.name">
                    <TextInput v-model="form.name" type="text" />
                </FormField>

                <FormField label="Email">
                    <TextInput :model-value="user.email" type="email" disabled />
                </FormField>

                <FormField label="Phone" :error="form.errors.phone">
                    <TextInput v-model="form.phone" type="tel" />
                </FormField>

                <FormField label="Preferred Location" :error="form.errors.preferred_location">
                    <TextInput v-model="form.preferred_location" type="text" placeholder="e.g. Kuala Lumpur" />
                </FormField>

                <div class="flex items-center gap-3 pt-2">
                    <UiButton type="submit" :disabled="form.processing"> Save Changes </UiButton>
                    <span v-if="form.recentlySuccessful" class="text-sm font-bold text-teal">Saved!</span>
                </div>
            </form>
        </div>

        <div class="card-hard p-6 max-w-lg mt-6">
            <SectionTitle size="sm">
                {{ hasPassword ? 'Change Password' : 'Set a Password' }}
            </SectionTitle>
            <p class="mt-1 text-sm text-moss">
                {{
                    hasPassword
                        ? 'Choose a new password of at least 8 characters.'
                        : 'You signed in with Google. Set a password to also sign in with your email address.'
                }}
            </p>

            <form class="space-y-4 mt-4" @submit.prevent="submitPassword">
                <FormField v-if="hasPassword" label="Current Password" :error="passwordForm.errors.current_password">
                    <TextInput
                        v-model="passwordForm.current_password"
                        type="password"
                        autocomplete="current-password"
                    />
                </FormField>

                <FormField label="New Password" :error="passwordForm.errors.password">
                    <TextInput v-model="passwordForm.password" type="password" autocomplete="new-password" />
                </FormField>

                <FormField label="Confirm New Password">
                    <TextInput
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                    />
                </FormField>

                <div class="flex items-center gap-3 pt-2">
                    <UiButton type="submit" :disabled="passwordForm.processing">
                        {{ hasPassword ? 'Update Password' : 'Set Password' }}
                    </UiButton>
                    <span v-if="passwordForm.recentlySuccessful" class="text-sm font-bold text-teal">Saved!</span>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
