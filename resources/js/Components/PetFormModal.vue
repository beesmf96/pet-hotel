<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    pet: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    species: '',
    breed: '',
    age: '',
    special_needs: '',
    photo: null,
});

watch(
    () => props.pet,
    (pet) => {
        form.name = pet?.name ?? '';
        form.species = pet?.species ?? '';
        form.breed = pet?.breed ?? '';
        form.age = pet?.age ?? '';
        form.special_needs = pet?.special_needs ?? '';
        form.photo = null;
    },
    { immediate: true },
);

function submit() {
    if (props.pet) {
        form.patch(`/pets/${props.pet.id}`, {
            onSuccess: () => emit('close'),
        });
    } else {
        form.post('/pets', {
            onSuccess: () => emit('close'),
        });
    }
}

function close() {
    form.reset();
    form.clearErrors();
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="close">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ pet ? 'Edit Pet' : 'Add Pet' }}
                </h2>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Species *</label>
                        <input
                            v-model="form.species"
                            type="text"
                            placeholder="e.g. Dog, Cat, Rabbit"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                        />
                        <p v-if="form.errors.species" class="mt-1 text-xs text-red-600">{{ form.errors.species }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Breed</label>
                            <input
                                v-model="form.breed"
                                type="text"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                            />
                            <p v-if="form.errors.breed" class="mt-1 text-xs text-red-600">{{ form.errors.breed }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Age (years)</label>
                            <input
                                v-model="form.age"
                                type="number"
                                min="0"
                                max="100"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                            />
                            <p v-if="form.errors.age" class="mt-1 text-xs text-red-600">{{ form.errors.age }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Special Needs</label>
                        <textarea
                            v-model="form.special_needs"
                            rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 resize-none"
                        />
                        <p v-if="form.errors.special_needs" class="mt-1 text-xs text-red-600">
                            {{ form.errors.special_needs }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                        <input
                            type="file"
                            accept="image/*"
                            class="text-sm text-gray-600"
                            @change="form.photo = $event.target.files[0]"
                        />
                        <p v-if="form.errors.photo" class="mt-1 text-xs text-red-600">{{ form.errors.photo }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            class="text-sm text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg border border-gray-300"
                            @click="close"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-gray-900 text-white text-sm px-5 py-2 rounded-lg hover:bg-gray-700 disabled:opacity-50"
                        >
                            {{ pet ? 'Save Changes' : 'Add Pet' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
