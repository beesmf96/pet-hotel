<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    booking: { type: Object, required: true },
});

function formatDate(dateStr) {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}
</script>

<template>
    <AppLayout>
        <div class="max-w-lg mx-auto text-center py-10 space-y-6">
            <div class="text-5xl">🐾</div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Booking Request Sent!</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Your request is <span class="font-medium text-amber-600">pending review</span>. The hotel will confirm shortly.
                </p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-left space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Hotel</span>
                    <span class="font-medium text-gray-900">{{ booking.hotel.name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Pet</span>
                    <span class="font-medium text-gray-900">{{ booking.pet.name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Check-in</span>
                    <span class="font-medium text-gray-900">{{ formatDate(booking.check_in) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Check-out</span>
                    <span class="font-medium text-gray-900">{{ formatDate(booking.check_out) }}</span>
                </div>
                <div class="border-t border-gray-100 pt-3 flex justify-between">
                    <span class="text-gray-500">Total</span>
                    <span class="font-semibold text-gray-900">${{ Number(booking.total_price).toFixed(2) }}</span>
                </div>
            </div>

            <div class="flex justify-center gap-4">
                <a
                    href="/bookings"
                    class="bg-gray-900 text-white text-sm px-5 py-2 rounded-lg hover:bg-gray-700"
                >
                    View My Bookings
                </a>
                <a
                    href="/hotels"
                    class="text-sm text-gray-600 hover:text-gray-900 px-5 py-2 rounded-lg border border-gray-300"
                >
                    Browse Hotels
                </a>
            </div>
        </div>
    </AppLayout>
</template>
