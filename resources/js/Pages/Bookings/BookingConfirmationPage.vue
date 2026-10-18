<script setup>
import { formatMoney } from '@/money';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useFormatDate } from '@/composables/useFormatDate.js';
import UiButton from '@/Components/Ui/UiButton.vue';
import SectionTitle from '@/Components/Ui/SectionTitle.vue';

defineProps({
    booking: { type: Object, required: true },
    times: { type: Object, default: null },
    answerBy: { type: String, default: null },
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
                        Booking request sent!
                    </h1>
                    <p class="text-base text-moss" style="text-wrap: pretty">
                        Your request is
                        <span class="font-bold text-ink bg-mustard px-1.5 py-0.5 rounded-md">pending</span>. The hotel
                        will confirm or decline it, and we'll email you either way.
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
                    <span class="font-bold"
                        >{{ formatDate(booking.check_in, { weekday: true })
                        }}<template v-if="times">, from {{ times.check_in }}</template></span
                    >
                </div>
                <div class="flex justify-between gap-4">
                    <span class="font-semibold text-moss">Check-out</span>
                    <span class="font-bold"
                        >{{ formatDate(booking.check_out, { weekday: true })
                        }}<template v-if="times">, by {{ times.check_out }}</template></span
                    >
                </div>
                <div class="border-t-2 border-ink/10 pt-3 flex justify-between items-baseline">
                    <span class="font-semibold text-moss">Total</span>
                    <span class="font-display font-extrabold text-2xl">{{ formatMoney(booking.total_price) }}</span>
                </div>
            </div>

            <div class="card-hard p-6 text-left text-sm" data-testid="next-steps">
                <SectionTitle class="mb-3">What happens next</SectionTitle>
                <ol class="list-decimal pl-5 space-y-2">
                    <li v-if="answerBy">
                        The hotel answers by <strong>{{ answerBy }}</strong
                        >.
                    </li>
                    <li v-else>The hotel reviews your request.</li>
                    <li>We email you when they confirm or decline it.</li>
                    <li>
                        Until the hotel confirms, {{ booking.pet.name }} has no place. If the hotel does not answer in
                        time, the request expires and we tell you, so you can book somewhere else.
                    </li>
                </ol>
            </div>

            <div class="flex flex-col sm:flex-row justify-center gap-3">
                <UiButton as="a" href="/bookings"> View my bookings </UiButton>
                <UiButton as="a" variant="white" href="/hotels"> Browse hotels </UiButton>
            </div>
        </div>
    </AppLayout>
</template>
