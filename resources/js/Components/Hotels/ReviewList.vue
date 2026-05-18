<script setup>
import { useFormatDate } from '@/composables/useFormatDate.js';

defineProps({
    reviews: { type: Array, default: () => [] },
    averageRating: { type: Number, default: null },
    reviewsCount: { type: Number, default: 0 },
});

function starType(rating, position) {
    return position <= rating ? 'filled' : 'empty';
}

const { formatDate } = useFormatDate();
</script>

<template>
    <div>
        <!-- Summary row -->
        <div v-if="averageRating" class="flex items-center gap-3 mb-5">
            <div class="flex items-center gap-0.5">
                <span
                    v-for="i in 5"
                    :key="i"
                    class="text-lg leading-none"
                    :class="starType(Math.round(averageRating), i) === 'filled' ? 'text-amber-400' : 'text-gray-200'"
                >★</span>
            </div>
            <span class="font-semibold text-gray-900 text-sm">{{ averageRating }}</span>
            <span class="text-sm text-gray-500">
                ({{ reviewsCount }} {{ reviewsCount === 1 ? 'review' : 'reviews' }})
            </span>
        </div>
        <p v-else class="text-sm text-gray-400 italic mb-4">No reviews yet.</p>

        <!-- Review list -->
        <div class="space-y-4">
            <div
                v-for="review in reviews"
                :key="review.id"
                class="border-t border-gray-100 pt-4 first:border-t-0 first:pt-0"
            >
                <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center gap-0.5">
                        <span
                            v-for="i in 5"
                            :key="i"
                            class="text-sm leading-none"
                            :class="starType(review.rating, i) === 'filled' ? 'text-amber-400' : 'text-gray-200'"
                        >★</span>
                    </div>
                    <span class="text-xs text-gray-400">{{ formatDate(review.created_at) }}</span>
                </div>
                <p class="text-sm font-medium text-gray-900">{{ review.user_name }}</p>
                <p v-if="review.comment" class="text-sm text-gray-600 mt-1 leading-relaxed">{{ review.comment }}</p>
            </div>
        </div>
    </div>
</template>
