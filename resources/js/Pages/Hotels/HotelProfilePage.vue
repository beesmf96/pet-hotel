<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AvailabilityCalendar from '@/Components/Hotels/AvailabilityCalendar.vue';
import HotelMap from '@/Components/Hotels/HotelMap.vue';
import ReviewList from '@/Components/Hotels/ReviewList.vue';
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import PawIcon from '@/Components/Ui/PawIcon.vue';
import SectionTitle from '@/Components/Ui/SectionTitle.vue';
import UiButton from '@/Components/Ui/UiButton.vue';

const props = defineProps({
    hotel: { type: Object, required: true },
    reviews: { type: Array, default: () => [] },
    reviewsCount: { type: Number, default: 0 },
    averageRating: { type: Number, default: null },
});

const facilityLabels = {
    grooming: 'Grooming',
    play_area: 'Play Area',
    vet_care: 'Vet Care',
    swimming_pool: 'Swimming Pool',
    training: 'Training',
    outdoor_walks: 'Outdoor Walks',
    webcam: 'Live Webcam',
    '24h_care': '24h Care',
};

const petTypeLabels = {
    dog: 'Dog',
    cat: 'Cat',
    rabbit: 'Rabbit',
    bird: 'Bird',
    other: 'Other',
};

// Normalised to one shape so the template binds a single key, rather than
// reading .url off gallery rows and a hand-built cover object that only
// happened to share it.
const allPhotos = computed(() => {
    const photos = props.hotel.photos.map((photo) => ({ id: photo.id, src: photo.photo_url }));
    if (props.hotel.cover_photo_url) {
        photos.unshift({ id: 0, src: props.hotel.cover_photo_url });
    }
    return photos.filter((photo) => photo.src);
});

const activePhotoIndex = ref(0);

function prevPhoto() {
    if (allPhotos.value.length === 0) return;
    activePhotoIndex.value = (activePhotoIndex.value - 1 + allPhotos.value.length) % allPhotos.value.length;
}

function nextPhoto() {
    if (allPhotos.value.length === 0) return;
    activePhotoIndex.value = (activePhotoIndex.value + 1) % allPhotos.value.length;
}
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl sm:text-4xl leading-tight">{{ hotel.name }}</h1>
                <p class="font-sans font-medium tracking-normal text-sm text-moss">
                    {{ hotel.address }}, {{ hotel.city }}
                </p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Photo Gallery -->
            <div class="card-hard-lg overflow-hidden">
                <div v-if="allPhotos.length > 0" class="relative">
                    <img
                        :src="allPhotos[activePhotoIndex].src"
                        :alt="hotel.name"
                        class="w-full h-64 sm:h-96 object-cover"
                    />
                    <template v-if="allPhotos.length > 1">
                        <button
                            class="absolute left-3 top-1/2 -translate-y-1/2 bg-cream border-3 border-ink text-ink w-11 h-11 rounded-full flex items-center justify-center text-2xl font-bold shadow-hard hover:bg-mustard transition"
                            aria-label="Previous photo"
                            @click="prevPhoto"
                        >
                            ‹
                        </button>
                        <button
                            class="absolute right-3 top-1/2 -translate-y-1/2 bg-cream border-3 border-ink text-ink w-11 h-11 rounded-full flex items-center justify-center text-2xl font-bold shadow-hard hover:bg-mustard transition"
                            aria-label="Next photo"
                            @click="nextPhoto"
                        >
                            ›
                        </button>
                        <div
                            class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 bg-ink/60 rounded-full px-2 py-1.5"
                        >
                            <button
                                v-for="(_, i) in allPhotos"
                                :key="i"
                                class="w-2 h-2 rounded-full transition-colors"
                                :class="i === activePhotoIndex ? 'bg-white' : 'bg-white/50'"
                                @click="activePhotoIndex = i"
                            />
                        </div>
                    </template>
                </div>
                <div v-else class="w-full h-64 sm:h-96 bg-teal-light flex items-center justify-center text-ink">
                    <PawIcon :size="56" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <!-- Description -->
                    <div class="card-hard p-6">
                        <SectionTitle class="mb-3">About</SectionTitle>
                        <p class="text-[15px] text-moss leading-relaxed whitespace-pre-line">{{ hotel.description }}</p>
                    </div>

                    <!-- Facilities -->
                    <div v-if="hotel.facilities.length > 0" class="card-hard p-6">
                        <SectionTitle class="mb-4">Facilities</SectionTitle>
                        <div class="flex flex-wrap gap-2.5">
                            <span
                                v-for="facility in hotel.facilities"
                                :key="facility.id"
                                class="border-2 border-ink rounded-full px-3.5 py-1.5 text-sm font-semibold"
                            >
                                {{ facilityLabels[facility.type] }}
                            </span>
                        </div>
                    </div>

                    <!-- Policies -->
                    <div v-if="hotel.policy" class="card-hard p-6">
                        <SectionTitle class="mb-4">Policies</SectionTitle>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-cream border-2 border-ink rounded-xl p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-moss mb-1">Check-in</p>
                                <p class="font-display font-bold text-xl">{{ hotel.policy.check_in_time }}</p>
                            </div>
                            <div class="bg-cream border-2 border-ink rounded-xl p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-moss mb-1">Check-out</p>
                                <p class="font-display font-bold text-xl">{{ hotel.policy.check_out_time }}</p>
                            </div>
                        </div>
                        <div v-if="hotel.policy.cancellation_policy">
                            <p class="text-xs font-bold uppercase tracking-wide text-moss mb-1">Cancellation</p>
                            <p class="text-[15px] text-moss leading-relaxed">{{ hotel.policy.cancellation_policy }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: Pricing + Ratings -->
                <div class="space-y-6">
                    <!-- Pricing + Book Now -->
                    <div class="card-hard p-6">
                        <template v-if="hotel.pricing.length > 0">
                            <SectionTitle class="mb-3">Pricing</SectionTitle>
                            <ul class="divide-y-2 divide-ink/10 mb-5">
                                <li
                                    v-for="price in hotel.pricing"
                                    :key="price.id"
                                    class="flex justify-between items-baseline py-2.5 text-sm"
                                >
                                    <span class="font-semibold">{{ petTypeLabels[price.pet_type] }}</span>
                                    <span class="font-display font-bold text-lg">
                                        RM {{ Number(price.price_per_night).toFixed(2)
                                        }}<span class="font-sans text-sm font-medium text-moss"> / night</span>
                                    </span>
                                </li>
                            </ul>
                        </template>
                        <UiButton as="link" size="lg" block :href="`/hotels/${hotel.slug}/book`"> Book Now </UiButton>
                    </div>

                    <!-- Availability Calendar -->
                    <AvailabilityCalendar :hotel-slug="hotel.slug" />

                    <!-- Location -->
                    <div v-if="hotel.lat != null && hotel.lng != null" class="card-hard p-6">
                        <SectionTitle class="mb-3">Location</SectionTitle>
                        <HotelMap
                            :lat="hotel.lat"
                            :lng="hotel.lng"
                            :name="hotel.name"
                            class="mb-3 border-2 border-ink"
                        />
                        <p class="text-sm text-moss">{{ hotel.address }}, {{ hotel.city }}</p>
                        <a
                            :href="`https://maps.google.com/?q=${hotel.lat},${hotel.lng}`"
                            target="_blank"
                            rel="noopener"
                            class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-teal hover:underline"
                        >
                            Open in Google Maps ↗
                        </a>
                    </div>
                    <div v-else class="card-hard p-6">
                        <SectionTitle class="mb-2">Location</SectionTitle>
                        <p class="text-sm text-moss">{{ hotel.address }}, {{ hotel.city }}</p>
                    </div>

                    <!-- Reviews -->
                    <div class="card-hard p-6">
                        <div class="flex items-center justify-between mb-4">
                            <SectionTitle>Reviews</SectionTitle>
                            <Link
                                v-if="reviewsCount > 5"
                                :href="`/hotels/${hotel.slug}/reviews`"
                                class="text-sm font-semibold text-teal hover:underline"
                            >
                                View all {{ reviewsCount }}
                            </Link>
                        </div>
                        <ReviewList :reviews="reviews" :average-rating="averageRating" :reviews-count="reviewsCount" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
