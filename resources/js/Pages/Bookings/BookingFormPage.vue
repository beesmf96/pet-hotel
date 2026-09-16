<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AvailabilityCalendar from '@/Components/Hotels/AvailabilityCalendar.vue';
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

function onDatesSelected(val) {
    form.check_in = val.checkIn;
    form.check_out = val.checkOut ?? '';
}

function submit() {
    form.post(`/hotels/${props.hotel.slug}/bookings`);
}
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl sm:text-4xl leading-tight">Book a stay</h1>
                <p class="font-sans font-medium tracking-normal text-sm text-moss">
                    at <a :href="`/hotels/${hotel.slug}`" class="font-semibold text-teal hover:underline">{{ hotel.name }}</a>
                </p>
            </div>
        </template>

        <div class="max-w-2xl">
            <div v-if="pets.length === 0" class="bg-mustard border-3 border-ink rounded-2xl shadow-hard p-5 text-sm font-semibold">
                You need to
                <a href="/pets" class="underline underline-offset-2 hover:text-teal">add a pet</a>
                before booking.
            </div>

            <form v-else class="space-y-6" @submit.prevent="submit">
                <!-- Pet Selection -->
                <div class="bg-white border-3 border-ink rounded-2xl shadow-hard p-6 space-y-4">
                    <h2 class="font-display font-bold text-2xl">Your pet</h2>

                    <div>
                        <label class="block text-sm font-bold mb-1.5">Select pet *</label>
                        <select
                            v-model="form.pet_id"
                            class="w-full border-2 border-ink rounded-xl px-3.5 py-3 text-base bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                        >
                            <option value="" disabled>Choose a pet…</option>
                            <option v-for="pet in pets" :key="pet.id" :value="pet.id">
                                {{ pet.name }} ({{ pet.species }})
                            </option>
                        </select>
                        <p v-if="form.errors.pet_id" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.pet_id }}</p>
                    </div>
                </div>

                <!-- Availability Calendar -->
                <AvailabilityCalendar
                    :hotel-slug="hotel.slug"
                    :selectable="true"
                    :model-value="{ checkIn: form.check_in, checkOut: form.check_out }"
                    @update:model-value="onDatesSelected"
                />
                <div v-if="form.errors.check_in || form.errors.check_out" class="-mt-4 text-sm font-semibold text-coral">
                    <p v-if="form.errors.check_in">Please select a check-in date.</p>
                    <p v-if="form.errors.check_out">Please select a check-out date.</p>
                </div>

                <!-- Notes -->
                <div class="bg-white border-3 border-ink rounded-2xl shadow-hard p-6 space-y-4">
                    <h2 class="font-display font-bold text-2xl">Notes</h2>

                    <div>
                        <label class="block text-sm font-bold mb-1.5">Special requests (optional)</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            maxlength="500"
                            placeholder="Any special care instructions or requests…"
                            class="w-full border-2 border-ink rounded-xl px-3.5 py-3 text-base focus:outline-none focus:ring-2 focus:ring-teal resize-none"
                        />
                        <p v-if="form.errors.notes" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.notes }}</p>
                    </div>
                </div>

                <!-- Price Summary + actions -->
                <div class="bg-teal text-cream border-3 border-ink rounded-2xl shadow-hard-lg p-6">
                    <h2 class="font-display font-bold text-2xl mb-4">Price summary</h2>

                    <div v-if="totalPrice !== null" class="space-y-2 text-sm">
                        <div class="flex justify-between text-teal-light">
                            <span>RM {{ Number(pricing.price_per_night).toFixed(2) }} × {{ nights }} night{{ nights !== 1 ? 's' : '' }}</span>
                            <span>RM {{ totalPrice }}</span>
                        </div>
                        <div class="border-t-2 border-cream/20 pt-3 flex justify-between items-baseline">
                            <span class="font-bold">Total</span>
                            <span class="font-display font-extrabold text-3xl">RM {{ totalPrice }}</span>
                        </div>
                    </div>

                    <p v-else class="text-sm text-teal-light italic">
                        Select a pet and dates to see the price.
                    </p>

                    <p v-if="selectedPet && !pricing" class="mt-3 text-sm font-semibold bg-mustard text-ink border-2 border-ink rounded-xl px-3 py-2">
                        This hotel has no pricing listed for <strong>{{ selectedPet.species }}</strong>.
                    </p>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 mt-6">
                        <a
                            :href="`/hotels/${hotel.slug}`"
                            class="text-center text-sm font-bold bg-cream text-ink border-3 border-ink px-5 py-3 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition"
                        >
                            Back
                        </a>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="text-sm font-bold bg-mustard text-ink border-3 border-ink px-6 py-3 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition disabled:opacity-50"
                        >
                            {{ form.processing ? 'Submitting…' : 'Request Booking' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
