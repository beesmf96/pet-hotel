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
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900">My Pets</h1>
                <button class="bg-gray-900 text-white text-sm px-4 py-2 rounded-lg hover:bg-gray-700" @click="openAdd">
                    + Add Pet
                </button>
            </div>
        </template>

        <div v-if="pets.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center">
            <p class="text-gray-500 text-sm">You haven't added any pets yet.</p>
            <button class="mt-4 bg-gray-900 text-white text-sm px-5 py-2 rounded-lg hover:bg-gray-700" @click="openAdd">
                Add your first pet
            </button>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="pet in pets"
                :key="pet.id"
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex gap-4"
            >
                <div class="shrink-0">
                    <img
                        v-if="pet.photo_url"
                        :src="pet.photo_url"
                        :alt="pet.name"
                        class="w-16 h-16 rounded-full object-cover border border-gray-200"
                    />
                    <div v-else class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center text-2xl">
                        🐾
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-900">{{ pet.name }}</h3>
                    <p class="text-sm text-gray-600">
                        {{ pet.species }}<span v-if="pet.breed"> · {{ pet.breed }}</span>
                    </p>
                    <p v-if="pet.age != null" class="text-sm text-gray-500">
                        {{ pet.age }} yr{{ pet.age !== 1 ? 's' : '' }}
                    </p>
                    <p v-if="pet.special_needs" class="text-xs text-amber-700 mt-1 truncate">{{ pet.special_needs }}</p>

                    <div class="flex gap-3 mt-3">
                        <button class="text-xs text-gray-600 hover:text-gray-900 underline" @click="openEdit(pet)">
                            Edit
                        </button>
                        <button class="text-xs text-red-500 hover:text-red-700 underline" @click="deletePet(pet)">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <PetFormModal :show="showModal" :pet="editingPet" @close="closeModal" />
    </AppLayout>
</template>
