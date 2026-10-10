<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PetFormModal from '@/Components/PetFormModal.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PawIcon from '@/Components/Ui/PawIcon.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import EmptyState from '@/Components/Ui/EmptyState.vue';
import { petTypeLabel } from '@/petTypes';

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
                <UiButton variant="mustard" @click="openAdd"> + Add Pet </UiButton>
            </div>
        </template>

        <EmptyState v-if="pets.length === 0" message="You haven't added any pets yet.">
            <UiButton @click="openAdd"> Add your first pet </UiButton>
        </EmptyState>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="pet in pets" :key="pet.id" class="card-hard p-5 flex gap-4">
                <div class="shrink-0">
                    <img
                        v-if="pet.photo_url"
                        :src="pet.photo_url"
                        :alt="pet.name"
                        class="w-16 h-16 rounded-2xl object-cover border-2 border-ink"
                    />
                    <div
                        v-else
                        class="w-16 h-16 rounded-2xl bg-teal-light border-2 border-ink flex items-center justify-center text-ink"
                    >
                        <PawIcon :size="28" />
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="font-display font-bold text-lg">{{ pet.name }}</h3>
                    <p class="text-sm text-moss">
                        {{ petTypeLabel(pet.species) }}<span v-if="pet.breed"> · {{ pet.breed }}</span>
                    </p>
                    <p v-if="pet.age != null" class="text-sm text-moss">
                        {{ pet.age }} yr{{ pet.age !== 1 ? 's' : '' }}
                    </p>
                    <p v-if="pet.special_needs" class="text-xs font-semibold text-coral mt-1 truncate">
                        {{ pet.special_needs }}
                    </p>

                    <div class="flex gap-3 mt-3">
                        <button class="text-sm font-bold text-teal hover:underline" @click="openEdit(pet)">Edit</button>
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
