<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PetFormModal from '@/Components/PetFormModal.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    pets: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const editingPet = ref(null);

function openAdd() {
    editingPet.value = null;
    showModal.value = true;
}

function openEdit(pet) {
    editingPet.value = pet;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingPet.value = null;
}

function deletePet(pet) {
    if (confirm(`Remove ${pet.name}?`)) {
        router.delete(`/pets/${pet.id}`);
    }
}
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-3xl sm:text-4xl">My pets</h1>
                <button class="font-sans tracking-normal bg-mustard text-ink border-3 border-ink text-sm font-bold px-4 py-2.5 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition" @click="openAdd">
                    + Add Pet
                </button>
            </div>
        </template>

        <div v-if="pets.length === 0" class="bg-white border-3 border-ink rounded-2xl shadow-hard p-10 text-center">
            <p class="text-moss">You haven't added any pets yet.</p>
            <button class="mt-4 bg-teal text-cream border-3 border-ink text-sm font-bold px-5 py-2.5 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition disabled:opacity-50" @click="openAdd">
                Add your first pet
            </button>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="pet in pets"
                :key="pet.id"
                class="bg-white border-3 border-ink rounded-2xl shadow-hard p-5 flex gap-4"
            >
                <div class="shrink-0">
                    <img
                        v-if="pet.photo_url"
                        :src="pet.photo_url"
                        :alt="pet.name"
                        class="w-16 h-16 rounded-2xl object-cover border-2 border-ink"
                    />
                    <div v-else class="w-16 h-16 rounded-2xl bg-teal-light border-2 border-ink flex items-center justify-center text-ink">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5.5" cy="9" r="2" /><circle cx="9.5" cy="5" r="2" /><circle cx="14.5" cy="5" r="2" /><circle cx="18.5" cy="9" r="2" /><path d="M12 11c-3 0-6 3-6 6 0 1.7 1.3 3 3 3 1 0 2-.5 3-.5s2 .5 3 .5c1.7 0 3-1.3 3-3 0-3-3-6-6-6z" /></svg>
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="font-display font-bold text-lg">{{ pet.name }}</h3>
                    <p class="text-sm text-moss">
                        {{ pet.species }}<span v-if="pet.breed"> · {{ pet.breed }}</span>
                    </p>
                    <p v-if="pet.age != null" class="text-sm text-moss">
                        {{ pet.age }} yr{{ pet.age !== 1 ? 's' : '' }}
                    </p>
                    <p v-if="pet.special_needs" class="text-xs font-semibold text-coral mt-1 truncate">{{ pet.special_needs }}</p>

                    <div class="flex gap-3 mt-3">
                        <button class="text-sm font-bold text-teal hover:underline" @click="openEdit(pet)">
                            Edit
                        </button>
                        <button class="text-sm font-bold text-coral hover:underline" @click="deletePet(pet)">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <PetFormModal :show="showModal" :pet="editingPet" @close="closeModal" />
    </AppLayout>
</template>
