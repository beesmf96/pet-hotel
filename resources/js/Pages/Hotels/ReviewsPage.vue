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
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl sm:text-4xl leading-tight">Reviews: {{ hotel.name }}</h1>
                <Link
                    :href="`/hotels/${hotel.slug}`"
                    class="font-sans font-medium tracking-normal text-sm text-teal hover:underline inline-block"
                >
                    ← Back to hotel
                </Link>
            </div>
        </template>

        <div class="card-hard p-6 max-w-3xl">
            <ReviewList :reviews="reviews.data" :average-rating="averageRating" :reviews-count="reviewsCount" />

            <!-- Pagination -->
            <div
                v-if="reviews.last_page > 1"
                class="flex items-center justify-between mt-6 pt-4 border-t-2 border-ink/10"
            >
                <Link
                    v-if="reviews.prev_page_url"
                    :href="reviews.prev_page_url"
                    class="text-sm font-bold text-teal hover:underline"
                >
                    ← Previous
                </Link>
                <span v-else class="text-sm text-moss/40">← Previous</span>

                <span class="text-xs text-moss"> Page {{ reviews.current_page }} of {{ reviews.last_page }} </span>

                <Link
                    v-if="reviews.next_page_url"
                    :href="reviews.next_page_url"
                    class="text-sm font-bold text-teal hover:underline"
                >
                    Next →
                </Link>
                <span v-else class="text-sm text-moss/40">Next →</span>
            </div>
        </div>
    </AppLayout>
</template>
