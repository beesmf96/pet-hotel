<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LeaveReviewModal from '@/Components/Hotels/LeaveReviewModal.vue';
import { useFormatDate } from '@/composables/useFormatDate.js';

defineProps({
    bookings: { type: Array, default: () => [] },
});

const statusConfig = {
    pending:   { label: 'Pending',   classes: 'bg-mustard text-ink' },
    confirmed: { label: 'Confirmed', classes: 'bg-teal text-cream' },
    completed: { label: 'Completed', classes: 'bg-teal-light text-ink' },
    cancelled: { label: 'Cancelled', classes: 'bg-white text-moss' },
};

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

        <div v-if="bookings.length === 0" class="bg-white border-3 border-ink rounded-2xl shadow-hard p-10 text-center flex flex-col items-center gap-4">
            <span class="w-14 h-14 rounded-2xl bg-mustard border-3 border-ink flex items-center justify-center">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <circle cx="5.5" cy="9" r="2" />
                    <circle cx="9.5" cy="5" r="2" />
                    <circle cx="14.5" cy="5" r="2" />
                    <circle cx="18.5" cy="9" r="2" />
                    <path d="M12 11c-3 0-6 3-6 6 0 1.7 1.3 3 3 3 1 0 2-.5 3-.5s2 .5 3 .5c1.7 0 3-1.3 3-3 0-3-3-6-6-6z" />
                </svg>
            </span>
            <p class="text-moss">You have no bookings yet.</p>
            <Link
                href="/hotels"
                class="text-sm font-bold bg-teal text-cream border-3 border-ink px-5 py-3 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition"
            >
                Find a Hotel
            </Link>
        </div>

        <div v-else class="space-y-5 max-w-3xl">
            <div
                v-for="booking in bookings"
                :key="booking.id"
                class="bg-white border-3 border-ink rounded-2xl shadow-hard hover:-translate-y-0.5 transition"
            >
                <Link
                    :href="`/bookings/${booking.id}`"
                    class="block p-5"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex flex-col gap-1">
                            <p class="font-display font-bold text-xl leading-tight line-clamp-2">{{ booking.hotel.name }}</p>
                            <p class="text-sm font-semibold text-moss">{{ booking.pet.name }}</p>
                            <p class="text-sm mt-1">
                                {{ formatDate(booking.check_in) }} → {{ formatDate(booking.check_out) }}
                                <span class="text-moss">({{ nights(booking.check_in, booking.check_out) }} nights)</span>
                            </p>
                        </div>

                        <div class="shrink-0 text-right flex flex-col items-end gap-2">
                            <span
                                class="inline-block text-xs font-bold px-2.5 py-1 rounded-full border-2 border-ink"
                                :class="statusConfig[booking.status]?.classes"
                            >
                                {{ statusConfig[booking.status]?.label ?? booking.status }}
                            </span>
                            <p class="font-display font-bold text-lg">RM {{ Number(booking.total_price).toFixed(2) }}</p>
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
        <LeaveReviewModal
            v-if="reviewBooking"
            :booking="reviewBooking"
            :show="!!reviewBooking"
            @close="closeReview"
        />
    </AppLayout>
</template>
