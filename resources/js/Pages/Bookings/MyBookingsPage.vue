<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LeaveReviewModal from '@/Components/Hotels/LeaveReviewModal.vue';
import { useFormatDate } from '@/composables/useFormatDate.js';
import UiButton from '@/Components/Ui/UiButton.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import EmptyState from '@/Components/Ui/EmptyState.vue';

defineProps({
    bookings: { type: Array, default: () => [] },
});

const { formatDate } = useFormatDate();

function nights(checkIn, checkOut) {
    return Math.round((new Date(checkOut) - new Date(checkIn)) / 86400000);
}

const reviewBooking = ref(null);

function openReview(booking) {
    reviewBooking.value = booking;
}

function closeReview() {
    reviewBooking.value = null;
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-3xl sm:text-4xl">My bookings</h1>
        </template>

        <EmptyState v-if="bookings.length === 0" message="You have no bookings yet.">
            <UiButton as="link" href="/hotels"> Find a Hotel </UiButton>
        </EmptyState>

        <div v-else class="space-y-5 max-w-3xl">
            <div v-for="booking in bookings" :key="booking.id" class="card-hard hover:-translate-y-0.5 transition">
                <Link :href="`/bookings/${booking.id}`" class="block p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex flex-col gap-1">
                            <p class="font-display font-bold text-xl leading-tight line-clamp-2">
                                {{ booking.hotel.name }}
                            </p>
                            <p class="text-sm font-semibold text-moss">{{ booking.pet.name }}</p>
                            <p class="text-sm mt-1">
                                {{ formatDate(booking.check_in) }} → {{ formatDate(booking.check_out) }}
                                <span class="text-moss"
                                    >({{ nights(booking.check_in, booking.check_out) }} nights)</span
                                >
                            </p>
                        </div>

                        <div class="shrink-0 text-right flex flex-col items-end gap-2">
                            <StatusPill :status="booking.status" />
                            <p class="font-display font-bold text-lg">
                                RM {{ Number(booking.total_price).toFixed(2) }}
                            </p>
                        </div>
                    </div>
                </Link>

                <!-- Leave a review prompt -->
                <div
                    v-if="booking.status === 'completed' && !booking.has_review"
                    class="px-5 pb-4 border-t-2 border-ink/10 pt-3"
                >
                    <button
                        class="text-sm font-bold text-teal hover:underline underline-offset-2"
                        @click="openReview(booking)"
                    >
                        Leave a review →
                    </button>
                </div>
                <div
                    v-else-if="booking.status === 'completed' && booking.has_review"
                    class="px-5 pb-4 border-t-2 border-ink/10 pt-3"
                >
                    <span class="text-xs font-semibold text-moss">Review submitted</span>
                </div>
            </div>
        </div>

        <!-- Review modal -->
        <LeaveReviewModal v-if="reviewBooking" :booking="reviewBooking" :show="!!reviewBooking" @close="closeReview" />
    </AppLayout>
</template>
