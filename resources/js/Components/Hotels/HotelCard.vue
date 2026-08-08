<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    hotel: Object,
});

const facilityIcons = {
    grooming: '✂️',
    play_area: '🎾',
    vet_care: '🩺',
    swimming_pool: '🏊',
    training: '🎓',
    outdoor_walks: '🦮',
    webcam: '📷',
    '24h_care': '🕐',
};

const facilityLabels = {
    grooming: 'Grooming',
    play_area: 'Play Area',
    vet_care: 'Vet Care',
    swimming_pool: 'Swimming Pool',
    training: 'Training',
    outdoor_walks: 'Outdoor Walks',
    webcam: 'Webcam',
    '24h_care': '24h Care',
};

function visitHotel() {
    router.visit(`/hotels/${props.hotel.slug}`);
}
</script>

<template>
    <div
        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden cursor-pointer hover:shadow-md hover:border-gray-300 transition-all"
        @click="visitHotel"
    >
        <img
            v-if="hotel.cover_photo_url"
            :src="hotel.cover_photo_url"
            :alt="hotel.name"
            class="w-full h-44 object-cover"
        />
        <div v-else class="w-full h-44 bg-gray-100 flex items-center justify-center text-4xl">🏨</div>

        <div class="p-4">
            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ hotel.name }}</h3>
            <p class="text-xs text-gray-500 mt-0.5 mb-3">{{ hotel.city }}</p>

            <div v-if="hotel.facilities?.length > 0" class="flex flex-wrap gap-1.5 mb-3">
                <span
                    v-for="facility in hotel.facilities.slice(0, 3)"
                    :key="facility.id"
                    class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full"
                >
                    <span>{{ facilityIcons[facility.type] }}</span>
                    <span>{{ facilityLabels[facility.type] }}</span>
                </span>
                <span v-if="hotel.facilities.length > 3" class="text-xs text-gray-400 self-center">
                    +{{ hotel.facilities.length - 3 }} more
                </span>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <span v-if="hotel.price_from" class="text-sm font-semibold text-gray-900">
                        From ${{ Number(hotel.price_from).toFixed(2) }}
                    </span>
                    <span v-else class="text-sm text-gray-400">Pricing unavailable</span>
                    <span v-if="hotel.price_from" class="text-xs text-gray-400"> / night</span>
                </div>
                <div class="flex items-center gap-1 text-xs text-gray-400">
                    <span>⭐</span>
                    <span>{{ hotel.reviews_avg_rating ? Number(hotel.reviews_avg_rating).toFixed(1) : '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
