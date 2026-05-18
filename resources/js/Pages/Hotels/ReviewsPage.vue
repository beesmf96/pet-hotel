<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ReviewList from '@/Components/Hotels/ReviewList.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    hotel: { type: Object, required: true },
    reviews: { type: Object, required: true }, // paginated
    averageRating: { type: Number, default: null },
    reviewsCount: { type: Number, default: 0 },
});
</script>

<template>
    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Reviews — {{ hotel.name }}</h1>
                <Link
                    :href="`/hotels/${hotel.slug}`"
                    class="text-sm text-gray-500 hover:text-gray-700 mt-0.5 inline-block"
                >
                    ← Back to hotel
                </Link>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <ReviewList
                :reviews="reviews.data"
                :average-rating="averageRating"
                :reviews-count="reviewsCount"
            />

            <!-- Pagination -->
            <div v-if="reviews.last_page > 1" class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100">
                <Link
                    v-if="reviews.prev_page_url"
                    :href="reviews.prev_page_url"
                    class="text-sm text-gray-700 hover:text-gray-900 font-medium"
                >
                    ← Previous
                </Link>
                <span v-else class="text-sm text-gray-300">← Previous</span>

                <span class="text-xs text-gray-400">
                    Page {{ reviews.current_page }} of {{ reviews.last_page }}
                </span>

                <Link
                    v-if="reviews.next_page_url"
                    :href="reviews.next_page_url"
                    class="text-sm text-gray-700 hover:text-gray-900 font-medium"
                >
                    Next →
                </Link>
                <span v-else class="text-sm text-gray-300">Next →</span>
            </div>
        </div>
    </AppLayout>
</template>
