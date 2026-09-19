<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useFormatDate } from '@/composables/useFormatDate.js';
import SectionTitle from '@/Components/Ui/SectionTitle.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import Notice from '@/Components/Ui/Notice.vue';
import { petTypeLabel } from '@/petTypes';

const props = defineProps({
    booking: { type: Object, required: true },
});

const flash = computed(() => usePage().props.flash ?? {});
const { formatDate } = useFormatDate();

function nights() {
    return Math.round((new Date(props.booking.check_out) - new Date(props.booking.check_in)) / 86400000);
}

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
                    <h1 class="text-3xl sm:text-4xl leading-tight">Booking #{{ booking.id }}</h1>
                    <p class="font-sans font-medium tracking-normal text-sm text-moss">{{ booking.hotel.name }}</p>
                </div>
                <StatusPill :status="booking.status" />
            </div>
        </template>

        <div class="max-w-2xl space-y-6">
            <!-- Flash message -->
            <Notice v-if="flash.success">{{ flash.success }}</Notice>

            <!-- Details card -->
            <div class="card-hard p-6 space-y-4">
                <SectionTitle>Booking Details</SectionTitle>

                <dl class="space-y-3 text-sm [&_dt]:font-semibold [&_dt]:text-moss [&_dd]:font-bold [&_dd]:text-right">
                    <div class="flex justify-between">
                        <dt class="text-moss">Hotel</dt>
                        <dd class="font-medium text-ink">
                            <a :href="`/hotels/${booking.hotel.slug}`" class="hover:underline">
                                {{ booking.hotel.name }}
                            </a>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-moss">Address</dt>
                        <dd class="text-ink">{{ booking.hotel.address }}, {{ booking.hotel.city }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-moss">Pet</dt>
                        <dd class="font-medium text-ink">{{ booking.pet.name }} ({{ petTypeLabel(booking.pet.species) }})</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-moss">Check-in</dt>
                        <dd class="font-medium text-ink">{{ formatDate(booking.check_in, { weekday: true }) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-moss">Check-out</dt>
                        <dd class="font-medium text-ink">{{ formatDate(booking.check_out, { weekday: true }) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-moss">Duration</dt>
                        <dd class="text-ink">{{ nights() }} night{{ nights() !== 1 ? 's' : '' }}</dd>
                    </div>
                    <div class="border-t-2 border-ink/10 pt-3 flex justify-between items-baseline">
                        <dt>Total</dt>
                        <dd class="font-display text-2xl">RM {{ Number(booking.total_price).toFixed(2) }}</dd>
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
                <a href="/bookings" class="text-sm font-bold text-teal hover:underline"> Back to My Bookings </a>

                <UiButton v-if="booking.status === 'pending'" variant="coral" @click="cancelBooking">
                    Cancel Booking
                </UiButton>
            </div>
        </div>
    </AppLayout>
</template>
