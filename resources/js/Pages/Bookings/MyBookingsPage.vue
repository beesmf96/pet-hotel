<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    bookings: { type: Array, default: () => [] },
});

const statusConfig = {
    pending:   { label: 'Pending',   classes: 'bg-amber-100 text-amber-700' },
    confirmed: { label: 'Confirmed', classes: 'bg-green-100 text-green-700' },
    cancelled: { label: 'Cancelled', classes: 'bg-gray-100 text-gray-500' },
};

function formatDate(dateStr) {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function nights(checkIn, checkOut) {
    return Math.round((new Date(checkOut) - new Date(checkIn)) / 86400000);
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900">My Bookings</h1>
        </template>

        <div v-if="bookings.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center">
            <p class="text-gray-500 text-sm">You have no bookings yet.</p>
            <a
                href="/hotels"
                class="inline-block mt-4 bg-gray-900 text-white text-sm px-5 py-2 rounded-lg hover:bg-gray-700"
            >
                Find a Hotel
            </a>
        </div>

        <div v-else class="space-y-4">
            <a
                v-for="booking in bookings"
                :key="booking.id"
                :href="`/bookings/${booking.id}`"
                class="block bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:border-gray-300 transition-colors"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ booking.hotel.name }}</p>
                        <p class="text-sm text-gray-500 mt-0.5">{{ booking.pet.name }}</p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ formatDate(booking.check_in) }} → {{ formatDate(booking.check_out) }}
                            <span class="text-gray-400">({{ nights(booking.check_in, booking.check_out) }} nights)</span>
                        </p>
                    </div>

                    <div class="shrink-0 text-right space-y-2">
                        <span
                            class="inline-block text-xs font-medium px-2.5 py-1 rounded-full"
                            :class="statusConfig[booking.status]?.classes"
                        >
                            {{ statusConfig[booking.status]?.label ?? booking.status }}
                        </span>
                        <p class="text-sm font-semibold text-gray-900">${{ Number(booking.total_price).toFixed(2) }}</p>
                    </div>
                </div>
            </a>
        </div>
    </AppLayout>
</template>
