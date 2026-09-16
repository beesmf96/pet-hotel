<script setup>
import { ref } from 'vue';

const props = defineProps({
    filters: Object,
    variant: { type: String, default: 'default' },
});

const bold = props.variant === 'bold';

const wrapperClass = bold ? '' : 'bg-white rounded-xl shadow-sm border border-gray-200 p-4';
const labelClass = bold ? 'block text-sm font-bold text-ink mb-1.5' : 'block text-xs text-gray-500 mb-1';
const fieldClass = bold
    ? 'w-full border-2 border-ink rounded-xl px-3.5 py-3 text-base bg-white focus:outline-none focus:ring-2 focus:ring-teal'
    : 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900';
const buttonClass = bold
    ? 'bg-teal text-cream border-3 border-ink px-6 py-3 rounded-xl text-base font-bold shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition'
    : 'bg-gray-900 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors';

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
    <div :class="wrapperClass">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label :class="labelClass">City</label>
                <input
                    v-model="city"
                    type="text"
                    placeholder="e.g. Kuala Lumpur"
                    :class="fieldClass"
                    @keyup.enter="search"
                />
            </div>

            <div class="flex-1 min-w-40">
                <label :class="labelClass">Check-in</label>
                <input
                    v-model="checkIn"
                    type="date"
                    :class="fieldClass"
                />
            </div>

            <div class="flex-1 min-w-40">
                <label :class="labelClass">Check-out</label>
                <input
                    v-model="checkOut"
                    type="date"
                    :class="fieldClass"
                />
            </div>

            <div class="flex-1 min-w-40">
                <label :class="labelClass">Pet type</label>
                <select
                    v-model="petType"
                    :class="fieldClass"
                >
                    <option v-for="type in PET_TYPES" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
            </div>

            <button :class="buttonClass" @click="search">
                {{ bold ? "Let's go" : 'Search' }}
            </button>
        </div>
    </div>
</template>
