<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    hotel: { type: Object, required: true },
    pets: { type: Array, default: () => [] },
});

const form = useForm({
    pet_id: '',
    check_in: '',
    check_out: '',
    notes: '',
});

const selectedPet = computed(() => props.pets.find((p) => p.id === Number(form.pet_id)));

const pricing = computed(() => {
    if (!selectedPet.value) return null;
    return props.hotel.pricing.find((p) => p.pet_type === selectedPet.value.species);
});

const nights = computed(() => {
    if (!form.check_in || !form.check_out) return 0;
    const diff = (new Date(form.check_out) - new Date(form.check_in)) / 86400000;
    return diff > 0 ? diff : 0;
});

const totalPrice = computed(() => {
    if (!pricing.value || nights.value === 0) return null;
    return (Number(pricing.value.price_per_night) * nights.value).toFixed(2);
});

const todayStr = new Date().toISOString().slice(0, 10);

function submit() {
    form.post(`/hotels/${props.hotel.slug}/bookings`);
}
</script>

<template>
    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Book a Stay</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a :href="`/hotels/${hotel.slug}`" class="hover:underline">{{ hotel.name }}</a>
                </p>
            </div>
        </template>

        <div class="max-w-2xl">
            <div v-if="pets.length === 0" class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-sm text-amber-800">
                You need to
                <a href="/pets" class="underline font-medium">add a pet</a>
                before booking.
            </div>

            <form v-else class="space-y-6" @submit.prevent="submit">
                <!-- Pet Selection -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-gray-900">Your Pet</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select pet *</label>
                        <select
                            v-model="form.pet_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                        >
                            <option value="" disabled>Choose a pet…</option>
                            <option v-for="pet in pets" :key="pet.id" :value="pet.id">
                                {{ pet.name }} ({{ pet.species }})
                            </option>
                        </select>
                        <p v-if="form.errors.pet_id" class="mt-1 text-xs text-red-600">{{ form.errors.pet_id }}</p>
                    </div>
                </div>

                <!-- Dates -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-gray-900">Dates</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Check-in *</label>
                            <input
                                v-model="form.check_in"
                                type="date"
                                :min="todayStr"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                            />
                            <p v-if="form.errors.check_in" class="mt-1 text-xs text-red-600">{{ form.errors.check_in }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Check-out *</label>
                            <input
                                v-model="form.check_out"
                                type="date"
                                :min="form.check_in || todayStr"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                            />
                            <p v-if="form.errors.check_out" class="mt-1 text-xs text-red-600">{{ form.errors.check_out }}</p>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-gray-900">Notes</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Special requests (optional)</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            maxlength="500"
                            placeholder="Any special care instructions or requests…"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 resize-none"
                        />
                        <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">{{ form.errors.notes }}</p>
                    </div>
                </div>

                <!-- Price Summary -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Price Summary</h2>

                    <div v-if="totalPrice !== null" class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>${{ Number(pricing.price_per_night).toFixed(2) }} × {{ nights }} night{{ nights !== 1 ? 's' : '' }}</span>
                            <span>${{ totalPrice }}</span>
                        </div>
                        <div class="border-t border-gray-100 pt-2 flex justify-between font-semibold text-gray-900">
                            <span>Total</span>
                            <span>${{ totalPrice }}</span>
                        </div>
                    </div>

                    <p v-else class="text-sm text-gray-400 italic">
                        Select a pet and dates to see the price.
                    </p>

                    <p v-if="selectedPet && !pricing" class="mt-2 text-sm text-amber-700">
                        This hotel has no pricing listed for <strong>{{ selectedPet.species }}</strong>.
                    </p>
                </div>

                <div class="flex justify-end gap-3">
                    <a
                        :href="`/hotels/${hotel.slug}`"
                        class="text-sm text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg border border-gray-300"
                    >
                        Back
                    </a>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-gray-900 text-white text-sm px-6 py-2 rounded-lg hover:bg-gray-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Submitting…' : 'Request Booking' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
