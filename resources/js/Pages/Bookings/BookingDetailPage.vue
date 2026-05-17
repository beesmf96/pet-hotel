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
    pending:   { label: 'Pending',   classes: 'bg-amber-100 text-amber-700' },
    confirmed: { label: 'Confirmed', classes: 'bg-green-100 text-green-700' },
    cancelled: { label: 'Cancelled', classes: 'bg-gray-100 text-gray-500' },
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Booking #{{ booking.id }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ booking.hotel.name }}</p>
                </div>
                <span
                    class="text-xs font-medium px-3 py-1.5 rounded-full"
                    :class="statusConfig[booking.status]?.classes"
                >
                    {{ statusConfig[booking.status]?.label ?? booking.status }}
                </span>
            </div>
        </template>

        <div class="max-w-2xl space-y-6">
            <!-- Flash message -->
            <div v-if="flash.success" class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-800">
                {{ flash.success }}
            </div>

            <!-- Details card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Booking Details</h2>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Hotel</dt>
                        <dd class="font-medium text-gray-900">
                            <a :href="`/hotels/${booking.hotel.slug}`" class="hover:underline">
                                {{ booking.hotel.name }}
                            </a>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Address</dt>
                        <dd class="text-gray-700">{{ booking.hotel.address }}, {{ booking.hotel.city }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Pet</dt>
                        <dd class="font-medium text-gray-900">{{ booking.pet.name }} ({{ booking.pet.species }})</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Check-in</dt>
                        <dd class="font-medium text-gray-900">{{ formatDate(booking.check_in) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Check-out</dt>
                        <dd class="font-medium text-gray-900">{{ formatDate(booking.check_out) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Duration</dt>
                        <dd class="text-gray-700">{{ nights() }} night{{ nights() !== 1 ? 's' : '' }}</dd>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between">
                        <dt class="font-semibold text-gray-900">Total</dt>
                        <dd class="font-semibold text-gray-900">${{ Number(booking.total_price).toFixed(2) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Notes -->
            <div v-if="booking.notes" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-2">Notes</h2>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ booking.notes }}</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <a href="/bookings" class="text-sm text-gray-600 hover:text-gray-900 underline">
                    Back to My Bookings
                </a>

                <button
                    v-if="booking.status === 'pending'"
                    class="text-sm text-red-600 hover:text-red-800 px-4 py-2 rounded-lg border border-red-200 hover:border-red-400 transition-colors"
                    @click="cancelBooking"
                >
                    Cancel Booking
                </button>
            </div>
        </div>
    </AppLayout>
</template>
