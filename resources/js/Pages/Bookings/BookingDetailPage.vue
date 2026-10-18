<script setup>
import { formatMoney } from '@/money';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { useFormatDate } from '@/composables/useFormatDate.js';
import SectionTitle from '@/Components/Ui/SectionTitle.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import DetailRow from '@/Components/Ui/DetailRow.vue';
import Notice from '@/Components/Ui/Notice.vue';
import { petTypeLabel } from '@/petTypes';
import { nightsBetween, nightsLabel } from '@/stay';

const props = defineProps({
    booking: { type: Object, required: true },
    times: { type: Object, default: null },
    answerBy: { type: String, default: null },
});

const { formatDate } = useFormatDate();

function cancelBooking() {
    if (!confirm('Are you sure you want to cancel this booking?')) return;
    router.patch(`/bookings/${props.booking.id}/cancel`);
}
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-3xl sm:text-4xl">Booking #{{ booking.id }}</h1>
                    <p class="font-sans font-medium tracking-normal text-sm text-moss">{{ booking.hotel.name }}</p>
                </div>
                <StatusPill :status="booking.status" />
            </div>
        </template>

        <div class="max-w-2xl space-y-6">
            <Notice v-if="answerBy" data-testid="answer-by"
                >The hotel answers by <strong>{{ answerBy }}</strong
                >. If it does not, the request expires and we email you.</Notice
            >

            <!-- Details card -->
            <div class="card-hard p-6 space-y-4">
                <SectionTitle>Booking details</SectionTitle>

                <dl class="space-y-3 text-sm">
                    <DetailRow label="Hotel">
                        <Link :href="`/hotels/${booking.hotel.slug}`" class="hover:underline">
                            {{ booking.hotel.name }}
                        </Link>
                    </DetailRow>
                    <DetailRow label="Address">{{ booking.hotel.address }}, {{ booking.hotel.city }}</DetailRow>
                    <DetailRow label="Pet">{{ booking.pet.name }} ({{ petTypeLabel(booking.pet.species) }})</DetailRow>
                    <DetailRow label="Check-in">
                        {{ formatDate(booking.check_in, { weekday: true })
                        }}<template v-if="times">, from {{ times.check_in }}</template>
                    </DetailRow>
                    <DetailRow label="Check-out">
                        {{ formatDate(booking.check_out, { weekday: true })
                        }}<template v-if="times">, by {{ times.check_out }}</template>
                    </DetailRow>
                    <DetailRow label="Duration">
                        {{ nightsLabel(nightsBetween(booking.check_in, booking.check_out)) }}
                    </DetailRow>
                    <div class="border-t-2 border-ink/10 pt-3 flex justify-between items-baseline">
                        <dt class="font-semibold text-moss">Total</dt>
                        <dd class="font-display font-bold text-2xl">{{ formatMoney(booking.total_price) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Notes -->
            <div v-if="booking.notes" class="card-hard p-6">
                <SectionTitle class="mb-2">Notes</SectionTitle>
                <p class="text-[15px] text-moss leading-relaxed whitespace-pre-line">{{ booking.notes }}</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <Link href="/bookings" class="link-bold text-teal"> Back to my bookings </Link>

                <UiButton v-if="booking.status === 'pending'" variant="coral" @click="cancelBooking">
                    Cancel booking
                </UiButton>
            </div>
        </div>
    </AppLayout>
</template>
