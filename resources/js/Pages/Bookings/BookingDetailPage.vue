<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    booking: { type: Object, required: true },
});

const flash = computed(() => usePage().props.flash ?? {});

const statusConfig = {
    pending:   { label: 'Pending',   classes: 'bg-mustard text-ink' },
    confirmed: { label: 'Confirmed', classes: 'bg-teal text-cream' },
    completed: { label: 'Completed', classes: 'bg-teal-light text-ink' },
    cancelled: { label: 'Cancelled', classes: 'bg-white text-moss' },
};

function formatDate(dateStr) {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function nights() {
    return Math.round(
        (new Date(props.booking.check_out) - new Date(props.booking.check_in)) / 86400000,
    );
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
                <span
                    class="shrink-0 text-xs font-bold px-3 py-1.5 rounded-full border-2 border-ink font-sans tracking-normal"
                    :class="statusConfig[booking.status]?.classes"
                >
                    {{ statusConfig[booking.status]?.label ?? booking.status }}
                </span>
            </div>
        </template>

        <div class="max-w-2xl space-y-6">
            <!-- Flash message -->
            <div v-if="flash.success" class="bg-teal-light border-3 border-ink rounded-2xl shadow-hard p-4 text-sm font-semibold">
                {{ flash.success }}
            </div>

            <!-- Details card -->
            <div class="bg-white border-3 border-ink rounded-2xl shadow-hard p-6 space-y-4">
                <h2 class="font-display font-bold text-2xl">Booking Details</h2>

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
                        <dd class="font-medium text-ink">{{ booking.pet.name }} ({{ booking.pet.species }})</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-moss">Check-in</dt>
                        <dd class="font-medium text-ink">{{ formatDate(booking.check_in) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-moss">Check-out</dt>
                        <dd class="font-medium text-ink">{{ formatDate(booking.check_out) }}</dd>
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
            <div v-if="booking.notes" class="bg-white border-3 border-ink rounded-2xl shadow-hard p-6">
                <h2 class="font-display font-bold text-2xl mb-2">Notes</h2>
                <p class="text-[15px] text-moss leading-relaxed whitespace-pre-line">{{ booking.notes }}</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <a href="/bookings" class="text-sm font-bold text-teal hover:underline">
                    Back to My Bookings
                </a>

                <button
                    v-if="booking.status === 'pending'"
                    class="text-sm font-bold text-ink bg-coral border-3 border-ink px-4 py-2.5 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition"
                    @click="cancelBooking"
                >
                    Cancel Booking
                </button>
            </div>
        </div>
    </AppLayout>
</template>
