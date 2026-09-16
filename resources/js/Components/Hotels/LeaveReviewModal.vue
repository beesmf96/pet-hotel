<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SectionTitle from '@/Components/Ui/SectionTitle.vue';
import TextInput from '@/Components/Ui/TextInput.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import FormField from '@/Components/Ui/FormField.vue';

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
            <div class="card-hard-lg w-full max-w-md mx-4 p-6">
                <div class="flex items-center justify-between mb-5">
                    <SectionTitle>Leave a Review</SectionTitle>
                    <button type="button" class="text-moss hover:text-ink text-2xl leading-none" @click="emit('close')">
                        ×
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <!-- Star picker -->
                    <FormField label="Rating" :error="form.errors.rating">
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
                            >
                                ★
                            </button>
                        </div>
                    </FormField>

                    <!-- Comment -->
                    <FormField label="Comment" :error="form.errors.comment">
                        <template #label-suffix> <span class="text-moss font-medium">(optional)</span></template>
                        <TextInput
                            v-model="form.comment"
                            as="textarea"
                            rows="4"
                            placeholder="Share your experience..."
                        />
                    </FormField>

                    <div class="flex gap-3 pt-1">
                        <UiButton variant="white" class="flex-1" type="button" @click="emit('close')">
                            Cancel
                        </UiButton>
                        <UiButton class="flex-1" type="submit" :disabled="form.processing || form.rating === 0">
                            Submit Review
                        </UiButton>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
