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
                    :class="starType(Math.round(averageRating), i) === 'filled' ? 'text-mustard' : 'text-ink/15'"
                    >★</span
                >
            </div>
            <span class="font-display font-bold text-lg">{{ averageRating }}</span>
            <span class="text-sm text-moss">
                ({{ reviewsCount }} {{ reviewsCount === 1 ? 'review' : 'reviews' }})
            </span>
        </div>
        <p v-else class="text-sm text-moss italic mb-4">No reviews yet.</p>

        <!-- Review list -->
        <div class="space-y-4">
            <div
                v-for="review in reviews"
                :key="review.id"
                class="border-t-2 border-ink/10 pt-4 first:border-t-0 first:pt-0"
            >
                <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center gap-0.5">
                        <span
                            v-for="i in 5"
                            :key="i"
                            class="text-sm leading-none"
                            :class="starType(review.rating, i) === 'filled' ? 'text-mustard' : 'text-ink/15'"
                            >★</span
                        >
                    </div>
                    <span class="text-xs text-moss">{{ formatDate(review.created_at) }}</span>
                </div>
                <p class="text-sm font-bold">{{ review.user_name }}</p>
                <p v-if="review.comment" class="text-sm text-moss mt-1 leading-relaxed">{{ review.comment }}</p>
            </div>
        </div>
    </div>
</template>
