<script setup>
import { ref } from 'vue';
import SectionTitle from '@/Components/Ui/SectionTitle.vue';
import UiButton from '@/Components/Ui/UiButton.vue';

const props = defineProps({
    filters: Object,
});

const emit = defineEmits(['apply']);

const priceMin = ref(props.filters.price_min || '');
const priceMax = ref(props.filters.price_max || '');

const parsedFacilities = (() => {
    if (!props.filters.facilities) return [];
    return Array.isArray(props.filters.facilities) ? props.filters.facilities : props.filters.facilities.split(',');
})();

const selectedFacilities = ref(parsedFacilities);

const FACILITIES = [
    { value: 'grooming', label: 'Grooming' },
    { value: 'play_area', label: 'Play Area' },
    { value: 'vet_care', label: 'Vet Care' },
    { value: 'swimming_pool', label: 'Swimming Pool' },
    { value: 'training', label: 'Training' },
    { value: 'outdoor_walks', label: 'Outdoor Walks' },
    { value: 'webcam', label: 'Live Webcam' },
    { value: '24h_care', label: '24h Care' },
];

function applyFilters() {
    emit('apply', {
        city: props.filters.city,
        pet_type: props.filters.pet_type,
        check_in: props.filters.check_in,
        check_out: props.filters.check_out,
        price_min: priceMin.value || undefined,
        price_max: priceMax.value || undefined,
        facilities: selectedFacilities.value.length > 0 ? selectedFacilities.value : undefined,
    });
}

function clearFilters() {
    priceMin.value = '';
    priceMax.value = '';
    selectedFacilities.value = [];
    emit('apply', {
        city: props.filters.city,
        pet_type: props.filters.pet_type,
        check_in: props.filters.check_in,
        check_out: props.filters.check_out,
    });
}
</script>

<template>
    <div class="card-hard p-4 space-y-5">
        <SectionTitle size="sm">Filters</SectionTitle>

        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-moss mb-2">Price per night</p>
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-moss mb-1">Min RM</label>
                    <input
                        v-model="priceMin"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="w-full border-2 border-ink rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal"
                    />
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-moss mb-1">Max RM</label>
                    <input
                        v-model="priceMax"
                        type="number"
                        min="0"
                        placeholder="Any"
                        class="w-full border-2 border-ink rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal"
                    />
                </div>
            </div>
        </div>

        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-moss mb-2">Facilities</p>
            <div class="space-y-2">
                <label
                    v-for="facility in FACILITIES"
                    :key="facility.value"
                    class="flex items-center gap-2 cursor-pointer"
                >
                    <input
                        v-model="selectedFacilities"
                        type="checkbox"
                        :value="facility.value"
                        class="w-4 h-4 rounded border-2 border-ink accent-teal focus:ring-teal"
                    />
                    <span class="text-sm font-medium">{{ facility.label }}</span>
                </label>
            </div>
        </div>

        <div class="space-y-2 pt-1">
            <UiButton size="sm" block @click="applyFilters"> Apply Filters </UiButton>
            <button
                class="w-full text-sm font-semibold text-moss py-2 rounded-xl hover:bg-cream transition-colors"
                @click="clearFilters"
            >
                Clear Filters
            </button>
        </div>
    </div>
</template>
