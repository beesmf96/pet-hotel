<script setup>
import { router } from '@inertiajs/vue3';
import PawIcon from '@/Components/Ui/PawIcon.vue';

const props = defineProps({
    hotel: Object,
});

const facilityLabels = {
    grooming: 'Grooming',
    play_area: 'Play area',
    vet_care: 'Vet care',
    swimming_pool: 'Pool',
    training: 'Training',
    outdoor_walks: 'Outdoor walks',
    webcam: 'Webcam',
    '24h_care': '24h care',
};

const photoTints = ['bg-coral', 'bg-teal text-cream', 'bg-mustard', 'bg-teal-light'];

function visitHotel() {
    router.visit(`/hotels/${props.hotel.slug}`);
}
</script>

<template>
    <div
        class="card-hard-lg overflow-hidden cursor-pointer flex flex-col hover:-translate-y-1 transition"
        @click="visitHotel"
    >
        <img
            v-if="hotel.cover_photo_url"
            :src="hotel.cover_photo_url"
            :alt="hotel.name"
            class="w-full h-48 object-cover border-b-3 border-ink"
        />
        <div
            v-else
            class="w-full h-48 border-b-3 border-ink flex items-center justify-center"
            :class="photoTints[(hotel.id ?? 0) % photoTints.length]"
        >
            <PawIcon :size="40" />
        </div>

        <div class="p-5 flex flex-col gap-2.5">
            <div class="flex items-start justify-between gap-2">
                <h3 class="font-display font-bold text-xl text-ink leading-tight line-clamp-2">{{ hotel.name }}</h3>
                <span
                    v-if="hotel.reviews_avg_rating"
                    class="shrink-0 inline-flex items-center gap-1 bg-mustard border-2 border-ink rounded-full px-2.5 py-0.5 text-sm font-bold text-ink"
                >
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2l3 7 7 .6-5.3 4.6 1.7 7L12 17.5 5.6 21.2l1.7-7L2 9.6 9 9z" />
                    </svg>
                    {{ Number(hotel.reviews_avg_rating).toFixed(1) }}
                </span>
                <span v-else class="shrink-0 text-xs font-semibold text-moss">New</span>
            </div>

            <p class="text-sm text-moss">{{ hotel.city }}</p>

            <div v-if="hotel.facilities?.length > 0" class="flex flex-wrap gap-1.5">
                <span
                    v-for="facility in hotel.facilities.slice(0, 3)"
                    :key="facility.id"
                    class="border-2 border-ink rounded-full px-2.5 py-0.5 text-xs font-semibold text-ink"
                >
                    {{ facilityLabels[facility.type] ?? facility.type }}
                </span>
                <span v-if="hotel.facilities.length > 3" class="text-xs text-moss self-center">
                    +{{ hotel.facilities.length - 3 }} more
                </span>
            </div>

            <p v-if="hotel.price_from" class="text-base font-bold text-ink">
                RM {{ Number(hotel.price_from).toFixed(0) }} <span class="font-medium text-moss">/ night</span>
            </p>
            <p v-else class="text-sm text-moss">Pricing on request</p>
        </div>
    </div>
</template>
