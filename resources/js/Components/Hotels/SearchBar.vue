<script setup>
import { ref } from 'vue';
import FormField from '@/Components/Ui/FormField.vue';
import TextInput from '@/Components/Ui/TextInput.vue';
import UiButton from '@/Components/Ui/UiButton.vue';

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['search']);

const city = ref(props.filters.city || '');
const checkIn = ref(props.filters.check_in || '');
const checkOut = ref(props.filters.check_out || '');
const petType = ref(props.filters.pet_type || '');

const PET_TYPES = [
    { value: '', label: 'Any pet type' },
    { value: 'dog', label: 'Dog' },
    { value: 'cat', label: 'Cat' },
    { value: 'rabbit', label: 'Rabbit' },
    { value: 'bird', label: 'Bird' },
    { value: 'other', label: 'Other' },
];

function search() {
    emit('search', {
        city: city.value || undefined,
        check_in: checkIn.value || undefined,
        check_out: checkOut.value || undefined,
        pet_type: petType.value || undefined,
    });
}
</script>

<template>
    <div class="flex flex-wrap gap-3 items-end">
        <FormField label="City" class="flex-1 min-w-48">
            <TextInput v-model="city" type="text" placeholder="e.g. Kuala Lumpur" @keyup.enter="search" />
        </FormField>

        <FormField label="Check-in" class="flex-1 min-w-40">
            <TextInput v-model="checkIn" type="date" />
        </FormField>

        <FormField label="Check-out" class="flex-1 min-w-40">
            <TextInput v-model="checkOut" type="date" />
        </FormField>

        <FormField label="Pet type" class="flex-1 min-w-40">
            <TextInput v-model="petType" as="select">
                <option v-for="type in PET_TYPES" :key="type.value" :value="type.value">
                    {{ type.label }}
                </option>
            </TextInput>
        </FormField>

        <UiButton size="lg" @click="search">Let's go</UiButton>
    </div>
</template>
