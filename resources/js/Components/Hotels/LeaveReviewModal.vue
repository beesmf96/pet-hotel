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
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/60"
            @click.self="emit('close')"
        >
            <div class="bg-white border-3 border-ink rounded-2xl shadow-hard-lg w-full max-w-md mx-4 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-display font-bold text-2xl">Leave a Review</h2>
                    <button
                        type="button"
                        class="text-moss hover:text-ink text-2xl leading-none"
                        @click="emit('close')"
                    >×</button>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <!-- Star picker -->
                    <div>
                        <label class="block text-sm font-bold mb-2">Rating</label>
                        <div class="flex items-center gap-1">
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                class="text-3xl leading-none transition-colors focus:outline-none"
                                :class="star <= (hoverRating || form.rating) ? 'text-mustard' : 'text-ink/15'"
                                @mouseenter="hoverRating = star"
                                @mouseleave="hoverRating = 0"
                                @click="form.rating = star"
                            >★</button>
                        </div>
                        <p v-if="form.errors.rating" class="text-sm font-semibold text-coral mt-1">{{ form.errors.rating }}</p>
                    </div>

                    <!-- Comment -->
                    <div>
                        <label class="block text-sm font-bold mb-1.5">
                            Comment <span class="text-moss font-medium">(optional)</span>
                        </label>
                        <textarea
                            v-model="form.comment"
                            rows="4"
                            placeholder="Share your experience..."
                            class="w-full border-2 border-ink rounded-xl px-3.5 py-3 text-base focus:outline-none focus:ring-2 focus:ring-teal resize-none"
                        />
                        <p v-if="form.errors.comment" class="text-sm font-semibold text-coral mt-1">{{ form.errors.comment }}</p>
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button
                            type="button"
                            class="flex-1 bg-white text-ink border-3 border-ink text-sm font-bold px-4 py-3 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition"
                            @click="emit('close')"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing || form.rating === 0"
                            class="flex-1 bg-teal text-cream border-3 border-ink text-sm font-bold px-4 py-3 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
