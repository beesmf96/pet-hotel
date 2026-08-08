<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AvailabilityCalendar from '@/Components/Hotels/AvailabilityCalendar.vue';
import HotelMap from '@/Components/Hotels/HotelMap.vue';
import ReviewList from '@/Components/Hotels/ReviewList.vue';
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

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
    activePhotoIndex.value =
        (activePhotoIndex.value - 1 + allPhotos.value.length) % allPhotos.value.length;
}

function nextPhoto() {
    if (allPhotos.value.length === 0) return;
    activePhotoIndex.value = (activePhotoIndex.value + 1) % allPhotos.value.length;
}
</script>

<template>
    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ hotel.name }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ hotel.address }}, {{ hotel.city }}</p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Photo Gallery -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div v-if="allPhotos.length > 0" class="relative">
                    <img
                        :src="allPhotos[activePhotoIndex].src"
                        :alt="hotel.name"
                        class="w-full h-80 object-cover"
                    />
                    <template v-if="allPhotos.length > 1">
                        <button
                            class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white w-9 h-9 rounded-full flex items-center justify-center text-lg"
                            @click="prevPhoto"
                        >
                            ‹
                        </button>
                        <button
                            class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white w-9 h-9 rounded-full flex items-center justify-center text-lg"
                            @click="nextPhoto"
                        >
                            ›
                        </button>
                        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
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
                <div v-else class="w-full h-80 bg-gray-100 flex items-center justify-center text-5xl">
                    🏨
                </div>
            </div>

            <!-- Description + Ratings placeholder -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <!-- Description -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-3">About</h2>
                        <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ hotel.description }}</p>
                    </div>

                    <!-- Facilities -->
                    <div v-if="hotel.facilities.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-4">Facilities</h2>
                        <div class="flex flex-wrap gap-3">
                            <span
                                v-for="facility in hotel.facilities"
                                :key="facility.id"
                                class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-700 text-sm px-3 py-1.5 rounded-full"
                            >
                                <span>{{ facilityIcons[facility.type] }}</span>
                                <span>{{ facilityLabels[facility.type] }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Policies -->
                    <div v-if="hotel.policy" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-4">Policies</h2>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Check-in</p>
                                <p class="text-sm font-medium text-gray-900">{{ hotel.policy.check_in_time }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Check-out</p>
                                <p class="text-sm font-medium text-gray-900">{{ hotel.policy.check_out_time }}</p>
                            </div>
                        </div>
                        <div v-if="hotel.policy.cancellation_policy">
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Cancellation</p>
                            <p class="text-sm text-gray-600">{{ hotel.policy.cancellation_policy }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: Pricing + Ratings -->
                <div class="space-y-6">
                    <!-- Pricing -->
                    <div v-if="hotel.pricing.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-4">Pricing</h2>
                        <ul class="divide-y divide-gray-100">
                            <li
                                v-for="price in hotel.pricing"
                                :key="price.id"
                                class="flex justify-between py-2.5 text-sm"
                            >
                                <span class="text-gray-700">{{ petTypeLabels[price.pet_type] }}</span>
                                <span class="font-medium text-gray-900">
                                    ${{ Number(price.price_per_night).toFixed(2) }}<span class="text-gray-400 font-normal"> / night</span>
                                </span>
                            </li>
                        </ul>
                    </div>

                    <!-- Book Now CTA -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <Link
                            :href="`/hotels/${hotel.slug}/book`"
                            class="block w-full text-center bg-gray-900 text-white text-sm font-medium px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors"
                        >
                            Book Now
                        </Link>
                    </div>

                    <!-- Availability Calendar -->
                    <AvailabilityCalendar :hotel-slug="hotel.slug" />

                    <!-- Location -->
                    <div v-if="hotel.lat != null && hotel.lng != null"
                         class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-3">Location</h2>
                        <HotelMap :lat="hotel.lat" :lng="hotel.lng" :name="hotel.name" class="mb-3" />
                        <p class="text-sm text-gray-600">{{ hotel.address }}, {{ hotel.city }}</p>
                        <a
                            :href="`https://maps.google.com/?q=${hotel.lat},${hotel.lng}`"
                            target="_blank"
                            rel="noopener"
                            class="mt-2 inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"
                        >
                            Open in Google Maps ↗
                        </a>
                    </div>
                    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-2">Location</h2>
                        <p class="text-sm text-gray-600">{{ hotel.address }}, {{ hotel.city }}</p>
                    </div>

                    <!-- Reviews -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-base font-semibold text-gray-900">Reviews</h2>
                            <Link
                                v-if="reviewsCount > 5"
                                :href="`/hotels/${hotel.slug}/reviews`"
                                class="text-xs text-gray-500 hover:text-gray-700 underline underline-offset-2"
                            >
                                View all {{ reviewsCount }}
                            </Link>
                        </div>
                        <ReviewList
                            :reviews="reviews"
                            :average-rating="averageRating"
                            :reviews-count="reviewsCount"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
