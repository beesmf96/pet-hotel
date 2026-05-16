<script setup>
import { ref } from 'vue';

const props = defineProps({
    filters: Object,
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
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs text-gray-500 mb-1">City</label>
                <input
                    v-model="city"
                    type="text"
                    placeholder="e.g. Kuala Lumpur"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                    @keyup.enter="search"
                />
            </div>

            <div class="flex-1 min-w-40">
                <label class="block text-xs text-gray-500 mb-1">Check-in</label>
                <input
                    v-model="checkIn"
                    type="date"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                />
            </div>

            <div class="flex-1 min-w-40">
                <label class="block text-xs text-gray-500 mb-1">Check-out</label>
                <input
                    v-model="checkOut"
                    type="date"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                />
            </div>

            <div class="flex-1 min-w-40">
                <label class="block text-xs text-gray-500 mb-1">Pet type</label>
                <select
                    v-model="petType"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 bg-white"
                >
                    <option v-for="type in PET_TYPES" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
            </div>

            <button
                class="bg-gray-900 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors"
                @click="search"
            >
                Search
            </button>
        </div>
    </div>
</template>
