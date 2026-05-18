<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    booking: { type: Object, required: true }, // { id, hotel: { slug } }
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const form = useForm({
    booking_id: props.booking.id,
    rating: 0,
    comment: '',
});

const hoverRating = ref(0);

function submit() {
    form.post(`/hotels/${props.booking.hotel.slug}/reviews`, {
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="emit('close')"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-base font-semibold text-gray-900">Leave a Review</h2>
                    <button
                        type="button"
                        class="text-gray-400 hover:text-gray-600 text-2xl leading-none"
                        @click="emit('close')"
                    >×</button>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <!-- Star picker -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                        <div class="flex items-center gap-1">
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                class="text-3xl leading-none transition-colors focus:outline-none"
                                :class="star <= (hoverRating || form.rating) ? 'text-amber-400' : 'text-gray-200'"
                                @mouseenter="hoverRating = star"
                                @mouseleave="hoverRating = 0"
                                @click="form.rating = star"
                            >★</button>
                        </div>
                        <p v-if="form.errors.rating" class="text-xs text-red-600 mt-1">{{ form.errors.rating }}</p>
                    </div>

                    <!-- Comment -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Comment <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <textarea
                            v-model="form.comment"
                            rows="4"
                            placeholder="Share your experience..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent resize-none"
                        />
                        <p v-if="form.errors.comment" class="text-xs text-red-600 mt-1">{{ form.errors.comment }}</p>
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button
                            type="button"
                            class="flex-1 border border-gray-300 text-gray-700 text-sm px-4 py-2.5 rounded-lg hover:bg-gray-50"
                            @click="emit('close')"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing || form.rating === 0"
                            class="flex-1 bg-gray-900 text-white text-sm px-4 py-2.5 rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
