<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useFormatDate } from '@/composables/useFormatDate.js';
import UiButton from '@/Components/Ui/UiButton.vue';

defineProps({
    booking: { type: Object, required: true },
});

const { formatDate } = useFormatDate();
</script>

<template>
    <AppLayout>
        <div class="max-w-lg mx-auto text-center py-8 sm:py-12 space-y-8">
            <div class="flex flex-col items-center gap-5">
                <span
                    class="w-20 h-20 rounded-3xl bg-mustard border-3 border-ink shadow-hard flex items-center justify-center text-ink"
                >
                    <svg
                        width="40"
                        height="40"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                </span>
                <div class="flex flex-col gap-2">
                    <h1 class="font-display font-extrabold text-3xl sm:text-4xl tracking-tight">
                        Booking Request Sent!
                    </h1>
                    <p class="text-base text-moss" style="text-wrap: pretty">
                        Your request is
                        <span class="font-bold text-ink bg-mustard px-1.5 py-0.5 rounded-md">pending review</span>. The
                        hotel will confirm shortly and we'll let you know.
                    </p>
                </div>
            </div>

            <div class="card-hard-lg p-6 text-left space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <span class="font-semibold text-moss">Hotel</span>
                    <span class="font-bold text-right">{{ booking.hotel.name }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="font-semibold text-moss">Pet</span>
                    <span class="font-bold">{{ booking.pet.name }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="font-semibold text-moss">Check-in</span>
                    <span class="font-bold">{{ formatDate(booking.check_in, { weekday: true }) }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="font-semibold text-moss">Check-out</span>
                    <span class="font-bold">{{ formatDate(booking.check_out, { weekday: true }) }}</span>
                </div>
                <div class="border-t-2 border-ink/10 pt-3 flex justify-between items-baseline">
                    <span class="font-semibold text-moss">Total</span>
                    <span class="font-display font-extrabold text-2xl"
                        >RM {{ Number(booking.total_price).toFixed(2) }}</span
                    >
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-center gap-3">
                <UiButton as="a" href="/bookings"> View My Bookings </UiButton>
                <UiButton as="a" variant="white" href="/hotels"> Browse Hotels </UiButton>
            </div>
        </div>
    </AppLayout>
</template>
